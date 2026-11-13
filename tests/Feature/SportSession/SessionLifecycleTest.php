<?php

namespace Tests\Feature\SportSession;

use App\Models\NotificationModel;
use App\Models\SessionSlotProposalModel;
use App\Models\SportSessionModel;
use App\Models\SportSessionParticipantModel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\SueTestHelpers;
use Tests\TestCase;

/**
 * Sessions : listes, détail, création, modification, suppression, changement d'organisateur,
 * invitations et réponses (cas non couverts par les suites historiques).
 */
class SessionLifecycleTest extends TestCase
{
    use RefreshDatabase, SueTestHelpers;

    private function futureDate(int $days = 5): string
    {
        return Carbon::now('Europe/Paris')->addDays($days)->format('Y-m-d');
    }

    public function test_routes_des_sessions_exigent_une_authentification(): void
    {
        $id = (string) Str::uuid();
        $routes = [
            ['get', '/api/sessions'], ['get', '/api/sessions/my-created'], ['get', '/api/sessions/my-participations'],
            ['get', '/api/sessions/history'], ['get', "/api/sessions/{$id}"], ['post', '/api/sessions'],
            ['put', "/api/sessions/{$id}"], ['delete', "/api/sessions/{$id}"], ['post', "/api/sessions/{$id}/invite"],
            ['post', '/api/join/abc'], ['patch', "/api/sessions/{$id}/respond"], ['put', "/api/sessions/{$id}/availability"],
            ['post', "/api/sessions/{$id}/confirm-slot"], ['put', "/api/sessions/{$id}/slots"],
            ['post', "/api/sessions/{$id}/slots/propose"], ['delete', "/api/sessions/{$id}/slots/{$id}"],
            ['get', "/api/sessions/{$id}/chat"], ['get', "/api/sessions/{$id}/chat/messages"],
            ['post', "/api/sessions/{$id}/chat/messages"], ['post', "/api/sessions/{$id}/chat/read"],
            ['put', "/api/sessions/{$id}/chat/mute"], ['post', "/api/sessions/{$id}/chat/typing"],
            ['patch', "/api/sessions/{$id}/cancel-participation"], ['patch', "/api/sessions/{$id}/cancel"],
            ['patch', "/api/sessions/{$id}/change-organizer"],
            ['post', "/api/sessions/{$id}/presence/join"], ['post', "/api/sessions/{$id}/presence/leave"],
            ['post', "/api/sessions/{$id}/presence/typing"], ['get', "/api/sessions/{$id}/presence/users"],
        ];
        foreach ($routes as [$method, $url]) {
            $this->json($method, $url)->assertUnauthorized();
        }
    }

    // --- Listes ---------------------------------------------------------------------------

    public function test_liste_de_mes_sessions_a_venir_et_filtres(): void
    {
        $me = $this->makeUser('Me');
        $orga = $this->makeUser('Orga');
        $mine = $this->makeSession($me, [], ['sport' => 'tennis', 'start_date' => now()->addDays(2), 'end_date' => now()->addDays(2)->addHour()]);
        $invited = $this->makeSession($orga, [[$me, 'pending']], ['sport' => 'football', 'start_date' => now()->addDays(4), 'end_date' => now()->addDays(4)->addHour()]);
        $this->makeSession($orga, [[$me, 'declined']]);                         // refusée
        $this->makeSession($orga);                                              // pas invité
        $this->makeSession($orga, [[$me, 'accepted']], ['status' => 'cancelled']); // annulée
        $this->makeSession($orga, [[$me, 'accepted']], ['start_date' => now()->subDays(2), 'end_date' => now()->subDays(2)->addHour()]); // passée

        $response = $this->actingAs($me)->getJson('/api/sessions')->assertOk()->assertJsonPath('pagination.total', 2);
        $this->assertSame([$mine->id, $invited->id], array_column($response->json('data'), 'id'));

        $this->actingAs($me)->getJson('/api/sessions?sport=football')->assertJsonPath('pagination.total', 1)->assertJsonPath('data.0.id', $invited->id);
        $this->actingAs($me)->getJson('/api/sessions?sport=inconnu')->assertJsonPath('pagination.total', 2); // filtre invalide ignoré
        $date = $mine->start_date->format('Y-m-d');
        $this->actingAs($me)->getJson("/api/sessions?date={$date}")->assertJsonPath('data.0.id', $mine->id);
        $this->actingAs($me)->getJson('/api/sessions?limit=1&page=2')->assertJsonCount(1, 'data')->assertJsonPath('pagination.totalPages', 2);
    }

    public function test_mes_sessions_creees_et_mes_participations(): void
    {
        $me = $this->makeUser('Me');
        $orga = $this->makeUser('Orga');
        $created = $this->makeSession($me, [], ['sport' => 'tennis']);
        $this->makeSession($me, [], ['status' => 'cancelled']);
        $participation = $this->makeSession($orga, [[$me, 'accepted']]);
        $pending = $this->makeSession($orga, [[$me, 'pending']]);
        $this->makeSession($orga, [[$me, 'declined']]);

        $this->actingAs($me)->getJson('/api/sessions/my-created')
            ->assertOk()->assertJsonPath('pagination.total', 1)->assertJsonPath('data.0.id', $created->id);
        $this->actingAs($me)->getJson('/api/sessions/my-created?sport=football')->assertJsonPath('pagination.total', 0);

        $ids = array_column($this->actingAs($me)->getJson('/api/sessions/my-participations')->assertOk()->json('data'), 'id');
        $this->assertEqualsCanonicalizing([$created->id, $participation->id, $pending->id], $ids);
        $this->actingAs($me)->getJson('/api/sessions/my-participations?sport=tennis')->assertJsonPath('pagination.total', 1);
    }

    public function test_historique_sessions_passees_et_annulees_plus_recentes_en_premier(): void
    {
        $me = $this->makeUser('Me');
        $orga = $this->makeUser('Orga');
        $old = $this->makeSession($orga, [[$me, 'accepted']], ['start_date' => now()->subDays(10), 'end_date' => now()->subDays(10)->addHour()]);
        $recent = $this->makeSession($orga, [[$me, 'accepted']], ['sport' => 'tennis', 'start_date' => now()->subDay(), 'end_date' => now()->subDay()->addHour()]);
        $cancelled = $this->makeSession($orga, [[$me, 'accepted']], ['status' => 'cancelled']);
        $this->makeSession($orga, [[$me, 'accepted']]); // à venir
        $this->makeSession($orga, [[$me, 'declined']], ['start_date' => now()->subDays(3), 'end_date' => now()->subDays(3)->addHour()]);

        $ids = array_column($this->actingAs($me)->getJson('/api/sessions/history')->assertOk()->json('data'), 'id');
        $this->assertSame([$cancelled->id, $recent->id, $old->id], $ids);
        $this->actingAs($me)->getJson('/api/sessions/history?sport=tennis')->assertJsonPath('pagination.total', 1);
    }

    // --- Détail ---------------------------------------------------------------------------

    public function test_detail_d_une_session(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $session = $this->makeSession($orga, [[$paul, 'pending']], ['sport' => 'padel', 'max_participants' => 4, 'price_per_person' => 12.5]);

        $this->actingAs($paul)->getJson("/api/sessions/{$session->id}")
            ->assertOk()
            ->assertJson(['success' => true, 'data' => [
                'id' => $session->id, 'sport' => 'padel', 'maxParticipants' => 4, 'pricePerPerson' => 12.5,
                'status' => 'active', 'scheduling' => 'scheduled', 'organizer' => ['id' => $orga->id, 'fullName' => 'Orga Test'],
            ]])
            ->assertJsonCount(2, 'data.participants');

        $this->actingAs($paul)->getJson('/api/sessions/' . Str::uuid())->assertNotFound()->assertJsonPath('error.code', 'SESSION_NOT_FOUND');
    }

    // --- Création -------------------------------------------------------------------------

    public function test_creation_d_une_session_a_date_fixee(): void
    {
        $me = $this->makeUser('Me');
        $friend = $this->makeUser('Ami');

        $response = $this->actingAs($me)->postJson('/api/sessions', [
            'sport' => 'tennis',
            'date' => $this->futureDate(),
            'startTime' => '23:00',
            'endTime' => '00:30',            // se termine le lendemain
            'location' => 'Club',
            'maxParticipants' => 4,
            'pricePerPerson' => 10,
            'participantIds' => [$friend->id, (string) Str::uuid()], // inconnu ignoré
        ])->assertCreated()->assertJsonPath('data.startTime', '23:00')->assertJsonPath('data.endTime', '00:30');

        $session = SportSessionModel::find($response->json('data.id'));
        $this->assertSame(90, (int) $session->start_date->diffInMinutes($session->end_date));
        $this->assertSame(40, strlen($session->share_token));
        $this->assertSame('accepted', $this->participantStatus($session, $me));
        $this->assertSame('pending', $this->participantStatus($session, $friend));
        $this->assertSame(2, SportSessionParticipantModel::where('session_id', $session->id)->count());
        // Pas de notification à la création (envoyées via /invite)
        $this->assertSame(0, NotificationModel::count());
        $this->assertSame([], $this->pushes);
    }

    public function test_creation_validation(): void
    {
        $me = $this->makeUser('Me');
        $valid = ['sport' => 'tennis', 'date' => $this->futureDate(), 'startTime' => '10:00', 'endTime' => '11:00', 'location' => 'Club'];

        $this->actingAs($me)->postJson('/api/sessions', [])->assertStatus(400)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['error' => ['details' => ['sport', 'date', 'startTime', 'endTime', 'location']]]);
        $this->actingAs($me)->postJson('/api/sessions', ['sport' => 'quidditch'] + $valid)->assertStatus(400);
        $this->actingAs($me)->postJson('/api/sessions', ['date' => Carbon::yesterday()->format('Y-m-d')] + $valid)->assertStatus(400);
        $this->actingAs($me)->postJson('/api/sessions', ['startTime' => '25:00'] + $valid)->assertStatus(400);
        $this->actingAs($me)->postJson('/api/sessions', ['endTime' => '10:00'] + $valid)->assertStatus(400)
            ->assertJsonStructure(['error' => ['details' => ['endTime']]]);
        $this->actingAs($me)->postJson('/api/sessions', ['maxParticipants' => 0] + $valid)->assertStatus(422);
        $this->actingAs($me)->postJson('/api/sessions', ['maxParticipants' => 101] + $valid)->assertStatus(422);
        $this->actingAs($me)->postJson('/api/sessions', ['pricePerPerson' => -1] + $valid)->assertStatus(400);
        $this->actingAs($me)->postJson('/api/sessions', ['location' => str_repeat('a', 256)] + $valid)->assertStatus(400);
        $this->actingAs($me)->postJson('/api/sessions', ['participantIds' => ['pas-un-uuid']] + $valid)->assertStatus(400);
        $this->assertSame(0, SportSessionModel::count());
    }

    public function test_creation_trouver_un_creneau(): void
    {
        $me = $this->makeUser('Me');
        $d1 = $this->futureDate(3);
        $d2 = $this->futureDate(4);

        $response = $this->actingAs($me)->postJson('/api/sessions', [
            'sport' => 'football',
            'scheduling' => 'finding_slot',
            'location' => 'Five',
            'slotProposals' => [
                ['date' => $d2, 'startTime' => '20:00', 'endTime' => '21:00'],
                ['date' => $d1, 'startTime' => '18:00', 'endTime' => '19:30'],
            ],
            // Ignorés en mode créneau à définir
            'date' => 'n/importe', 'startTime' => 'x',
        ])->assertCreated()->assertJsonPath('data.scheduling', 'finding_slot')->assertJsonCount(2, 'data.slotProposals');

        $session = SportSessionModel::find($response->json('data.id'));
        $this->assertSame(2, SessionSlotProposalModel::where('session_id', $session->id)->count());
        // La plage de la session couvre tous les créneaux
        $this->assertSame("{$d1} 18:00", $session->start_date->copy()->setTimezone('Europe/Paris')->format('Y-m-d H:i'));
        $this->assertSame("{$d2} 21:00", $session->end_date->copy()->setTimezone('Europe/Paris')->format('Y-m-d H:i'));
        // Le premier créneau (le plus tôt) est en premier
        $this->assertSame($d1, $response->json('data.slotProposals.0.date'));
    }

    public function test_creation_trouver_un_creneau_validation(): void
    {
        $me = $this->makeUser('Me');
        $base = ['sport' => 'football', 'scheduling' => 'finding_slot', 'location' => 'Five'];
        $slot = ['date' => $this->futureDate(), 'startTime' => '18:00', 'endTime' => '19:00'];

        $this->actingAs($me)->postJson('/api/sessions', $base)->assertStatus(400)->assertJsonStructure(['error' => ['details' => ['slotProposals']]]);
        $this->actingAs($me)->postJson('/api/sessions', $base + ['slotProposals' => []])->assertStatus(400);
        $this->actingAs($me)->postJson('/api/sessions', $base + ['slotProposals' => array_fill(0, 21, $slot)])->assertStatus(400);
        $this->actingAs($me)->postJson('/api/sessions', $base + ['slotProposals' => [['endTime' => '18:00'] + $slot]])->assertStatus(400);
        $this->actingAs($me)->postJson('/api/sessions', ['scheduling' => 'plus-tard', 'slotProposals' => [$slot]] + $base)->assertStatus(400);
        $this->assertSame(0, SportSessionModel::count());
    }

    public function test_creation_regle_metier_refusee_renvoie_une_erreur_client(): void
    {
        $me = $this->makeUser('Me');
        $slot = ['date' => $this->futureDate(), 'startTime' => '18:00', 'endTime' => '19:00'];
        $past = ['date' => Carbon::now('Europe/Paris')->format('Y-m-d'), 'startTime' => '00:00', 'endTime' => '00:30'];

        $this->actingAs($me)->postJson('/api/sessions', [
            'sport' => 'football', 'scheduling' => 'finding_slot', 'location' => 'Five', 'slotProposals' => [$slot, $slot],
        ])->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonPath('error.message', 'Le même créneau est proposé deux fois');
        $this->actingAs($me)->postJson('/api/sessions', [
            'sport' => 'football', 'scheduling' => 'finding_slot', 'location' => 'Five', 'slotProposals' => [$past],
        ])->assertStatus(422)->assertJsonPath('error.message', 'Un créneau proposé est déjà passé');
        $this->assertSame(0, SportSessionModel::count());
    }

    public function test_nombre_de_joueurs_au_moins_2_a_la_creation_et_a_la_modification(): void
    {
        $me = $this->makeUser('Me');
        $valid = ['sport' => 'football', 'date' => $this->futureDate(), 'startTime' => '18:00', 'endTime' => '20:00', 'location' => 'Five'];

        foreach ([0, -3, 1] as $max) {
            $this->actingAs($me)->postJson('/api/sessions', ['maxParticipants' => $max] + $valid)
                ->assertStatus(422)
                ->assertJsonPath('error.code', 'VALIDATION_ERROR')
                ->assertJsonPath('error.message', 'Il faut au moins 2 joueurs dans une session.')
                ->assertJsonStructure(['error' => ['details' => ['maxParticipants']]]);
        }
        $this->assertSame(0, SportSessionModel::count());

        // 2 minimum ; null = illimité
        $this->actingAs($me)->postJson('/api/sessions', ['maxParticipants' => 2] + $valid)->assertCreated()->assertJsonPath('data.maxParticipants', 2);
        $id = $this->actingAs($me)->postJson('/api/sessions', ['maxParticipants' => null] + $valid)->assertCreated()
            ->assertJsonPath('data.maxParticipants', null)->json('data.id');

        foreach ([0, -1, 1] as $max) {
            $this->actingAs($me)->putJson("/api/sessions/{$id}", ['maxParticipants' => $max])
                ->assertStatus(422)->assertJsonPath('error.message', 'Il faut au moins 2 joueurs dans une session.');
        }
        $this->assertNull(SportSessionModel::find($id)->max_participants);
        $this->actingAs($me)->putJson("/api/sessions/{$id}", ['maxParticipants' => 4])->assertOk()->assertJsonPath('data.maxParticipants', 4);
        $this->actingAs($me)->putJson("/api/sessions/{$id}", ['maxParticipants' => null])->assertOk()->assertJsonPath('data.maxParticipants', null);
    }

    public function test_creation_trouver_un_creneau_refuse_les_doublons_et_creneaux_passes(): void
    {
        $me = $this->makeUser('Me');
        $slot = ['date' => $this->futureDate(), 'startTime' => '18:00', 'endTime' => '19:00'];
        $past = ['date' => Carbon::now('Europe/Paris')->format('Y-m-d'), 'startTime' => '00:00', 'endTime' => '00:30'];

        $this->actingAs($me)->postJson('/api/sessions', [
            'sport' => 'football', 'scheduling' => 'finding_slot', 'location' => 'Five', 'slotProposals' => [$slot, $slot],
        ])->assertJsonPath('success', false)->assertJsonPath('error.message', 'Le même créneau est proposé deux fois');
        $this->actingAs($me)->postJson('/api/sessions', [
            'sport' => 'football', 'scheduling' => 'finding_slot', 'location' => 'Five', 'slotProposals' => [$past],
        ])->assertJsonPath('success', false)->assertJsonPath('error.message', 'Un créneau proposé est déjà passé');
        $this->assertSame(0, SportSessionModel::count());
    }

    // --- Modification (compléments) --------------------------------------------------------

    public function test_modification_session_inconnue_et_creneau_a_definir(): void
    {
        $me = $this->makeUser('Me');
        $this->actingAs($me)->putJson('/api/sessions/' . Str::uuid(), ['location' => 'X'])->assertNotFound();

        $session = $this->makeSession($me, [], ['scheduling' => 'finding_slot']);
        $this->actingAs($me)->putJson("/api/sessions/{$session->id}", ['date' => $this->futureDate()])
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.message', 'Confirme un des créneaux proposés pour fixer la date');
        // Le lieu reste modifiable
        $this->actingAs($me)->putJson("/api/sessions/{$session->id}", ['location' => 'Nouveau lieu'])->assertOk();
        $this->assertSame('Nouveau lieu', $session->fresh()->location);
    }

    public function test_modification_notifie_et_pousse_aux_invites(): void
    {
        $orga = $this->makeUser('Orga');
        $accepted = $this->makeUser('Paul');
        $pending = $this->makeUser('Dina');
        $declined = $this->makeUser('Zoe');
        $session = $this->makeSession($orga, [[$accepted, 'accepted'], [$pending, 'pending'], [$declined, 'declined']]);

        $this->actingAs($orga)->putJson("/api/sessions/{$session->id}", ['location' => 'Autre terrain'])->assertOk();

        // Question produit ouverte : les invités en attente sont prévenus aussi (comportement actuel)
        $this->assertNotEmpty($this->pushesFor($accepted));
        $this->assertNotEmpty($this->pushesFor($pending));
        $this->assertSame([], $this->pushesFor($declined));
        $this->assertSame([], $this->pushesFor($orga));
    }

    // --- Suppression ---------------------------------------------------------------------

    public function test_suppression_par_l_organisateur(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $dina = $this->makeUser('Dina');
        $session = $this->makeSession($orga, [[$paul, 'accepted'], [$dina, 'pending']]);

        $this->actingAs($paul)->deleteJson("/api/sessions/{$session->id}")->assertForbidden()->assertJsonPath('error.code', 'FORBIDDEN');
        $this->actingAs($orga)->deleteJson('/api/sessions/' . Str::uuid())->assertNotFound();

        $this->actingAs($orga)->deleteJson("/api/sessions/{$session->id}")->assertOk()->assertJson(['success' => true]);

        $this->assertNull(SportSessionModel::find($session->id));
        $this->assertSame(0, SportSessionParticipantModel::where('session_id', $session->id)->count());
        // Les participants acceptés sont prévenus (in-app + push), pas les invités en attente
        $this->assertSame(1, NotificationModel::where('user_id', $paul->id)->where('title', 'Session annulée')->count());
        $this->assertSame(0, NotificationModel::where('user_id', $dina->id)->count());
        $this->assertCount(1, $this->pushesFor($paul));
        $this->assertSame('Session annulée', $this->pushesFor($paul)[0]['title']);
        $this->assertSame('session_cancelled', $this->pushesFor($paul)[0]['data']['type']);
        $this->assertArrayNotHasKey('session_id', $this->pushesFor($paul)[0]['data']);
        $this->assertSame([], $this->pushesFor($dina));
    }

    public function test_suppression_ne_notifie_pas_l_organisateur_lui_meme(): void
    {
        $orga = $this->makeUser('Orga');
        $session = $this->makeSession($orga);
        $this->actingAs($orga)->deleteJson("/api/sessions/{$session->id}")->assertOk();
        $this->assertSame(0, NotificationModel::where('user_id', $orga->id)->count());
        $this->assertSame([], $this->pushesFor($orga));
    }

    public function test_suppression_d_une_session_passee_refusee(): void
    {
        $orga = $this->makeUser('Orga');
        $session = $this->makeSession($orga, [], ['start_date' => now()->subDays(2), 'end_date' => now()->subDays(2)->addHour()]);

        $response = $this->actingAs($orga)->deleteJson("/api/sessions/{$session->id}");
        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'SESSION_ENDED')
            ->assertJsonPath('error.message', 'Impossible de supprimer une session passée');
        $this->assertNotNull(SportSessionModel::find($session->id));
    }

    // --- Changement d'organisateur --------------------------------------------------------

    public function test_changer_d_organisateur(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $dina = $this->makeUser('Dina');
        $zoe = $this->makeUser('Zoe');
        $session = $this->makeSession($orga, [[$paul, 'pending'], [$dina, 'accepted'], [$zoe, 'declined']]);

        $this->actingAs($orga)->patchJson("/api/sessions/{$session->id}/change-organizer", ['newOrganizerId' => $paul->id])
            ->assertOk()->assertJsonPath('data.organizer.id', $paul->id);

        $this->assertSame($paul->id, $session->fresh()->organizer_id);
        $this->assertSame('accepted', $this->participantStatus($session, $paul));
        $this->assertSame('accepted', $this->participantStatus($session, $orga));

        $this->assertSame("Vous êtes maintenant l'organisateur", $this->pushesFor($paul)[0]['title']);
        $this->assertSame('Session modifiée', $this->pushesFor($dina)[0]['title']);
        $this->assertSame([], $this->pushesFor($zoe));
        $this->assertSame([], $this->pushesFor($orga));
        $this->assertSame(1, NotificationModel::where('user_id', $dina->id)->where('type', 'session_update')->count());

        // L'ancien organisateur n'a plus la main
        $this->actingAs($orga)->patchJson("/api/sessions/{$session->id}/change-organizer", ['newOrganizerId' => $orga->id])->assertForbidden();
    }

    public function test_changer_d_organisateur_vers_un_non_participant_l_ajoute(): void
    {
        $orga = $this->makeUser('Orga');
        $newbie = $this->makeUser('Newbie');
        $session = $this->makeSession($orga);

        $this->actingAs($orga)->patchJson("/api/sessions/{$session->id}/change-organizer", ['newOrganizerId' => $newbie->id])->assertOk();
        $this->assertSame('accepted', $this->participantStatus($session, $newbie));
    }

    public function test_changer_d_organisateur_erreurs(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $session = $this->makeSession($orga, [[$paul, 'accepted']]);

        $this->actingAs($orga)->patchJson("/api/sessions/{$session->id}/change-organizer", [])->assertStatus(400);
        $this->actingAs($orga)->patchJson("/api/sessions/{$session->id}/change-organizer", ['newOrganizerId' => 'x'])->assertStatus(400);
        $this->actingAs($orga)->patchJson('/api/sessions/' . Str::uuid() . '/change-organizer', ['newOrganizerId' => $paul->id])->assertNotFound();
        $this->actingAs($paul)->patchJson("/api/sessions/{$session->id}/change-organizer", ['newOrganizerId' => $paul->id])->assertForbidden();
        $this->actingAs($orga)->patchJson("/api/sessions/{$session->id}/change-organizer", ['newOrganizerId' => $orga->id])
            ->assertStatus(400)->assertJsonPath('error.code', 'ALREADY_ORGANIZER');
        $this->actingAs($orga)->patchJson("/api/sessions/{$session->id}/change-organizer", ['newOrganizerId' => (string) Str::uuid()])
            ->assertNotFound()->assertJsonPath('error.code', 'USER_NOT_FOUND');
        $this->assertSame($orga->id, $session->fresh()->organizer_id);
    }

    // --- Invitations (compléments) ----------------------------------------------------------

    public function test_invitation_notifie_et_pousse_puis_n_est_pas_dupliquee(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $session = $this->makeSession($orga);

        $this->actingAs($orga)->postJson("/api/sessions/{$session->id}/invite", ['userIds' => [$paul->id]])
            ->assertCreated()->assertJsonPath('data.newInvitations', 1);
        $this->assertSame('pending', $this->participantStatus($session, $paul));
        $this->assertSame(1, NotificationModel::where('user_id', $paul->id)->where('type', 'invitation')->count());
        $this->assertCount(1, $this->pushesFor($paul));
        $this->assertSame('session_invitation', $this->pushesFor($paul)[0]['data']['type']);
        $this->assertTrue(NotificationModel::where('user_id', $paul->id)->value('push_sent'));

        // Réinviter quelqu'un déjà en attente : pas de nouvelle notification
        $this->actingAs($orga)->postJson("/api/sessions/{$session->id}/invite", ['userIds' => [$paul->id]])->assertCreated();
        $this->assertSame(1, NotificationModel::where('user_id', $paul->id)->count());
        $this->assertCount(1, $this->pushesFor($paul));
        $this->assertSame(1, SportSessionParticipantModel::where('session_id', $session->id)->where('user_id', $paul->id)->count());
    }

    public function test_invitation_erreurs(): void
    {
        $orga = $this->makeUser('Orga');
        $session = $this->makeSession($orga);

        $this->actingAs($orga)->postJson("/api/sessions/{$session->id}/invite", [])->assertStatus(400)->assertJsonPath('error.code', 'VALIDATION_ERROR');
        $this->actingAs($orga)->postJson("/api/sessions/{$session->id}/invite", ['userIds' => ['x']])->assertStatus(400);
        $this->actingAs($orga)->postJson('/api/sessions/' . Str::uuid() . '/invite', ['userIds' => [(string) Str::uuid()]])
            ->assertNotFound()->assertJsonPath('error.code', 'SESSION_NOT_FOUND');
        $this->actingAs($orga)->postJson("/api/sessions/{$session->id}/invite", ['userIds' => [(string) Str::uuid()]])
            ->assertStatus(400)->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_une_personne_exterieure_ne_peut_pas_inviter(): void
    {
        $orga = $this->makeUser('Orga');
        $outsider = $this->makeUser('Intrus');
        $dina = $this->makeUser('Dina');
        $victim = $this->makeUser('Cible');
        $session = $this->makeSession($orga, [[$dina, 'declined']]);

        foreach ([$outsider, $dina] as $user) {
            $this->actingAs($user)->postJson("/api/sessions/{$session->id}/invite", ['userIds' => [$victim->id]])
                ->assertForbidden()
                ->assertJsonPath('error.code', 'FORBIDDEN')
                ->assertJsonPath('error.message', 'Tu ne fais pas partie de cette session.');
        }
        $this->assertNull($this->participantStatus($session, $victim));
        $this->assertSame([], $this->pushesFor($victim));
    }

    public function test_les_participants_acceptes_ou_en_attente_peuvent_inviter(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $lea = $this->makeUser('Lea');
        $friendOfPaul = $this->makeUser('Ami');
        $friendOfLea = $this->makeUser('Amie');
        $session = $this->makeSession($orga, [[$paul, 'accepted'], [$lea, 'pending']]);

        $this->actingAs($paul)->postJson("/api/sessions/{$session->id}/invite", ['userIds' => [$friendOfPaul->id]])->assertCreated();
        $this->actingAs($lea)->postJson("/api/sessions/{$session->id}/invite", ['userIds' => [$friendOfLea->id]])->assertCreated();

        $this->assertSame('pending', $this->participantStatus($session, $friendOfPaul));
        $this->assertSame('pending', $this->participantStatus($session, $friendOfLea));
    }

    // --- Réponses aux invitations (compléments) ----------------------------------------------

    public function test_accepter_notifie_l_organisateur_sans_message_systeme(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $session = $this->makeSession($orga, [[$paul, 'pending']]);

        $this->actingAs($paul)->patchJson("/api/sessions/{$session->id}/respond", ['response' => 'accept'])
            ->assertOk()->assertJsonPath('message', 'Invitation acceptée');

        $this->assertSame('accepted', $this->participantStatus($session, $paul));
        $this->assertSame('Invitation acceptée', $this->pushesFor($orga)[0]['title']);
        $this->assertSame(1, NotificationModel::where('user_id', $orga->id)->count());
        $this->assertDatabaseCount('sport_session_comments', 0);
    }

    public function test_reponse_erreurs(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $outsider = $this->makeUser('Intrus');
        $session = $this->makeSession($orga, [[$paul, 'pending']]);

        $this->actingAs($paul)->patchJson("/api/sessions/{$session->id}/respond", ['response' => 'maybe'])->assertStatus(400);
        $this->actingAs($paul)->patchJson('/api/sessions/' . Str::uuid() . '/respond', ['response' => 'accept'])->assertNotFound();
        $this->actingAs($outsider)->patchJson("/api/sessions/{$session->id}/respond", ['response' => 'accept'])->assertForbidden();
        $this->assertNull($this->participantStatus($session, $outsider));
    }

    public function test_accepter_une_session_complete_est_refuse(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $dina = $this->makeUser('Dina');
        $session = $this->makeSession($orga, [[$paul, 'accepted'], [$dina, 'pending']], ['max_participants' => 2]);

        $this->actingAs($dina)->patchJson("/api/sessions/{$session->id}/respond", ['response' => 'accept'])
            ->assertStatus(400)->assertJsonPath('error.code', 'PARTICIPANT_LIMIT_REACHED');
        $this->assertSame('pending', $this->participantStatus($session, $dina));

        // Décliner reste possible
        $this->actingAs($dina)->patchJson("/api/sessions/{$session->id}/respond", ['response' => 'decline'])->assertOk();
        $this->assertSame('declined', $this->participantStatus($session, $dina));
    }

    public function test_accepter_une_session_a_creneau_a_definir_est_refuse(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $session = $this->makeSession($orga, [[$paul, 'pending']], ['scheduling' => 'finding_slot']);

        $this->actingAs($paul)->patchJson("/api/sessions/{$session->id}/respond", ['response' => 'accept'])
            ->assertStatus(400)->assertJsonPath('error.code', 'INVALID_RESPONSE');
        $this->assertSame('pending', $this->participantStatus($session, $paul));
    }

    public function test_on_ne_peut_pas_accepter_une_session_annulee(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $session = $this->makeSession($orga, [[$paul, 'pending']], ['status' => 'cancelled']);

        $this->actingAs($paul)->patchJson("/api/sessions/{$session->id}/respond", ['response' => 'accept'])
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'SESSION_CANCELLED')
            ->assertJsonPath('error.message', 'Cette session est annulée.');
        $this->assertSame('pending', $this->participantStatus($session, $paul));
        $this->assertDatabaseMissing('notifications', ['user_id' => $orga->id, 'session_id' => $session->id]);
    }

    public function test_on_ne_peut_pas_repondre_a_une_session_terminee(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $start = now()->subDays(2)->setTime(18, 0);
        $session = $this->makeSession($orga, [[$paul, 'pending']], ['start_date' => $start, 'end_date' => $start->copy()->addHours(2)]);

        foreach (['accept', 'decline'] as $response) {
            $this->actingAs($paul)->patchJson("/api/sessions/{$session->id}/respond", ['response' => $response])
                ->assertStatus(400)
                ->assertJsonPath('error.code', 'SESSION_ENDED')
                ->assertJsonPath('error.message', 'Cette session est terminée.');
        }
        $this->assertSame('pending', $this->participantStatus($session, $paul));
    }
}
