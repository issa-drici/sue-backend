<?php

namespace Tests\Feature\SportSession;

use App\Models\SessionSlotAvailabilityModel;
use App\Models\SessionSlotProposalModel;
use App\Models\SportSessionModel;
use App\Models\UserModel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\SueTestHelpers;
use Tests\TestCase;

/**
 * « Trouver un créneau » : disponibilités, confirmation, édition des créneaux par l'organisateur,
 * proposition / retrait de créneaux par les invités.
 */
class SlotFindingTest extends TestCase
{
    use RefreshDatabase, SueTestHelpers;

    private UserModel $orga;
    private UserModel $paul;
    private UserModel $dina;
    private SportSessionModel $session;
    /** @var SessionSlotProposalModel[] */
    private array $slots;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orga = $this->makeUser('Orga');
        $this->paul = $this->makeUser('Paul');
        $this->dina = $this->makeUser('Dina');
        $this->session = $this->makeSession($this->orga, [[$this->paul, 'pending'], [$this->dina, 'pending']], [
            'scheduling' => 'finding_slot',
            'max_participants' => null,
        ]);
        $this->slots = [
            $this->addSlot($this->day(3, '18:00')),
            $this->addSlot($this->day(4, '20:00')),
        ];
    }

    private function day(int $days, string $time): Carbon
    {
        return Carbon::parse(Carbon::now('Europe/Paris')->addDays($days)->format('Y-m-d') . " {$time}", 'Europe/Paris')->utc();
    }

    private function addSlot(Carbon $start, ?UserModel $proposer = null, array $available = []): SessionSlotProposalModel
    {
        $slot = SessionSlotProposalModel::create([
            'id' => (string) Str::uuid(),
            'session_id' => $this->session->id,
            'proposed_by' => $proposer?->id,
            'start_date' => $start,
            'end_date' => $start->copy()->addHour(),
        ]);
        foreach ($available as $user) {
            SessionSlotAvailabilityModel::create(['id' => (string) Str::uuid(), 'slot_id' => $slot->id, 'user_id' => $user->id]);
        }
        return $slot;
    }

    private function paris(int $days): string
    {
        return Carbon::now('Europe/Paris')->addDays($days)->format('Y-m-d');
    }

    private function availability(UserModel $user, array $slotIds)
    {
        return $this->actingAs($user)->putJson("/api/sessions/{$this->session->id}/availability", ['slotIds' => $slotIds]);
    }

    // --- Disponibilités -------------------------------------------------------------------

    public function test_donner_ses_disponibilites_previent_l_organisateur(): void
    {
        $this->availability($this->paul, [$this->slots[0]->id])
            ->assertOk()
            ->assertJsonPath('data.slotProposals.0.availableUserIds', [$this->paul->id])
            ->assertJsonPath('data.slotProposals.0.availabilityCount', 1);

        $this->assertSame('Paul a donné ses disponibilités', $this->pushesFor($this->orga)[0]['title']);
        $this->assertSame('1 créneau sur 2 lui convient', $this->pushesFor($this->orga)[0]['body']);
        $this->assertSame('slot_availability', $this->pushesFor($this->orga)[0]['data']['type']);

        // Modification : remplace les dispos précédentes
        $this->availability($this->paul, [$this->slots[1]->id, $this->slots[1]->id])->assertOk();
        $this->assertSame(0, SessionSlotAvailabilityModel::where('slot_id', $this->slots[0]->id)->count());
        $this->assertSame(1, SessionSlotAvailabilityModel::where('slot_id', $this->slots[1]->id)->count());
        $this->assertSame('Paul a modifié ses disponibilités', $this->pushesFor($this->orga)[1]['title']);
    }

    public function test_quand_tout_le_monde_a_repondu_l_organisateur_recoit_le_meilleur_creneau(): void
    {
        $this->availability($this->paul, [$this->slots[0]->id, $this->slots[1]->id])->assertOk();
        $this->availability($this->dina, [$this->slots[1]->id])->assertOk();

        $last = end($this->pushes[$this->tokenOf($this->orga)]);
        $this->assertSame('Tout le monde a répondu', $last['title']);
        $this->assertStringContainsString('2 dispos', $last['body']);
    }

    public function test_quand_personne_n_est_dispo(): void
    {
        $this->availability($this->paul, [])->assertOk();
        $this->availability($this->dina, [])->assertOk();

        $last = end($this->pushes[$this->tokenOf($this->orga)]);
        $this->assertSame('Tout le monde a répondu', $last['title']);
        $this->assertStringContainsString('Aucun créneau ne convient', $last['body']);
    }

    public function test_un_invite_qui_avait_decline_redevient_en_attente(): void
    {
        $this->session->participants()->where('user_id', $this->dina->id)->update(['status' => 'declined']);

        $this->availability($this->dina, [$this->slots[0]->id])->assertOk();
        $this->assertSame('pending', $this->participantStatus($this->session, $this->dina));
    }

    public function test_disponibilites_erreurs(): void
    {
        $outsider = $this->makeUser('Zed');

        $this->availability($this->paul, ['x'])->assertStatus(400)->assertJsonPath('error.code', 'VALIDATION_ERROR');
        $this->actingAs($this->paul)->putJson("/api/sessions/{$this->session->id}/availability", [])->assertStatus(400);
        $this->availability($this->paul, [(string) Str::uuid()])->assertStatus(400)->assertJsonPath('error.message', 'Créneau invalide');
        $this->availability($outsider, [$this->slots[0]->id])->assertForbidden();
        $this->availability($this->orga, [$this->slots[0]->id])->assertStatus(400);
        $this->actingAs($this->paul)->putJson('/api/sessions/' . Str::uuid() . '/availability', ['slotIds' => []])->assertNotFound();

        $this->session->update(['status' => 'cancelled']);
        $this->availability($this->paul, [])->assertStatus(400)->assertJsonPath('error.message', 'Cette session a été annulée');
        $this->session->update(['status' => 'active', 'scheduling' => 'scheduled']);
        $this->availability($this->paul, [])->assertStatus(400)->assertJsonPath('error.message', 'Le créneau de cette session est déjà confirmé');

        $this->assertSame(0, SessionSlotAvailabilityModel::count());
    }

    // --- Confirmation ----------------------------------------------------------------------

    public function test_confirmer_un_creneau_inscrit_les_joueurs_disponibles(): void
    {
        $zoe = $this->makeUser('Zoe');
        $this->addParticipant($this->session, $zoe, 'declined');
        SessionSlotAvailabilityModel::create(['id' => (string) Str::uuid(), 'slot_id' => $this->slots[0]->id, 'user_id' => $this->paul->id]);

        $this->actingAs($this->orga)->postJson("/api/sessions/{$this->session->id}/confirm-slot", ['slotId' => $this->slots[0]->id])
            ->assertOk()->assertJsonPath('data.scheduling', 'scheduled')->assertJsonPath('data.slotProposals', []);

        $session = $this->session->fresh();
        $this->assertSame('scheduled', $session->scheduling);
        $this->assertTrue($session->start_date->eq($this->slots[0]->start_date));
        $this->assertSame('accepted', $this->participantStatus($session, $this->paul));
        $this->assertSame('pending', $this->participantStatus($session, $this->dina));

        $this->assertStringContainsString('tu es inscrit', $this->pushesFor($this->paul)[0]['body']);
        $this->assertStringContainsString('Tu viens ?', $this->pushesFor($this->dina)[0]['body']);
        $this->assertSame([], $this->pushesFor($zoe));
        $this->assertSame([], $this->pushesFor($this->orga));
        // Plus de commentaire système (ancien système supprimé)
        $this->assertDatabaseCount('sport_session_comments', 0);
    }

    public function test_confirmer_respecte_la_limite_de_participants_premier_invite_premier_servi(): void
    {
        $this->session->update(['max_participants' => 2]);
        // Dina a été invitée après Paul : Paul passe en premier
        $this->session->participants()->where('user_id', $this->dina->id)->update(['created_at' => now()->addMinute()]);
        foreach ([$this->paul, $this->dina] as $user) {
            SessionSlotAvailabilityModel::create(['id' => (string) Str::uuid(), 'slot_id' => $this->slots[0]->id, 'user_id' => $user->id]);
        }

        $this->actingAs($this->orga)->postJson("/api/sessions/{$this->session->id}/confirm-slot", ['slotId' => $this->slots[0]->id])->assertOk();

        $this->assertSame('accepted', $this->participantStatus($this->session, $this->paul));
        $this->assertSame('pending', $this->participantStatus($this->session, $this->dina));
    }

    public function test_confirmer_erreurs(): void
    {
        $this->actingAs($this->paul)->postJson("/api/sessions/{$this->session->id}/confirm-slot", ['slotId' => $this->slots[0]->id])->assertForbidden();
        $this->actingAs($this->orga)->postJson("/api/sessions/{$this->session->id}/confirm-slot", [])->assertStatus(400);
        $this->actingAs($this->orga)->postJson("/api/sessions/{$this->session->id}/confirm-slot", ['slotId' => (string) Str::uuid()])
            ->assertStatus(400)->assertJsonPath('error.message', 'Créneau invalide');
        $this->actingAs($this->orga)->postJson('/api/sessions/' . Str::uuid() . '/confirm-slot', ['slotId' => $this->slots[0]->id])->assertNotFound();

        // Créneau déjà passé : n'est plus proposé
        $past = $this->addSlot(now()->subHour());
        $this->actingAs($this->orga)->postJson("/api/sessions/{$this->session->id}/confirm-slot", ['slotId' => $past->id])->assertStatus(400);

        $this->session->update(['status' => 'cancelled']);
        $this->actingAs($this->orga)->postJson("/api/sessions/{$this->session->id}/confirm-slot", ['slotId' => $this->slots[0]->id])->assertStatus(400);
        $this->assertSame('finding_slot', $this->session->fresh()->scheduling);
    }

    // --- Édition par l'organisateur -------------------------------------------------------

    public function test_modifier_les_creneaux_ajoute_retire_et_previent(): void
    {
        SessionSlotAvailabilityModel::create(['id' => (string) Str::uuid(), 'slot_id' => $this->slots[1]->id, 'user_id' => $this->paul->id]);
        $keep = $this->slots[0]->start_date->copy()->setTimezone('Europe/Paris');
        $newDate = $this->paris(6);

        $this->actingAs($this->orga)->putJson("/api/sessions/{$this->session->id}/slots", ['slots' => [
            ['id' => $this->slots[0]->id, 'date' => $keep->format('Y-m-d'), 'startTime' => $keep->format('H:i'), 'endTime' => $keep->copy()->addHour()->format('H:i')],
            ['date' => $newDate, 'startTime' => '10:00', 'endTime' => '11:00'],
        ]])->assertOk()->assertJsonCount(2, 'data.slotProposals');

        $this->assertNotNull(SessionSlotProposalModel::find($this->slots[0]->id));
        $this->assertNull(SessionSlotProposalModel::find($this->slots[1]->id));
        $this->assertSame(2, SessionSlotProposalModel::where('session_id', $this->session->id)->count());

        $paulTitles = array_column($this->pushesFor($this->paul), 'title');
        $this->assertCount(2, $paulTitles);
        $this->assertStringStartsWith('Nouveau créneau proposé', $paulTitles[0]);
        $this->assertStringStartsWith('Créneau retiré', $paulTitles[1]);
        $this->assertStringStartsWith('Nouveau créneau proposé', $this->pushesFor($this->dina)[0]['title']);
        $this->assertCount(1, $this->pushesFor($this->dina)); // n'avait pas coché le créneau retiré
    }

    public function test_modifier_les_creneaux_sans_changement_ne_previent_personne(): void
    {
        $slots = array_map(function ($slot) {
            $start = $slot->start_date->copy()->setTimezone('Europe/Paris');
            return ['id' => $slot->id, 'date' => $start->format('Y-m-d'), 'startTime' => $start->format('H:i'), 'endTime' => $start->copy()->addHour()->format('H:i')];
        }, $this->slots);

        $this->actingAs($this->orga)->putJson("/api/sessions/{$this->session->id}/slots", ['slots' => $slots])->assertOk();
        $this->assertSame([], $this->pushes);
    }

    public function test_modifier_les_creneaux_erreurs(): void
    {
        $slot = ['date' => $this->paris(6), 'startTime' => '10:00', 'endTime' => '11:00'];
        $url = "/api/sessions/{$this->session->id}/slots";

        $this->actingAs($this->paul)->putJson($url, ['slots' => [$slot]])->assertForbidden();
        $this->actingAs($this->orga)->putJson($url, ['slots' => []])->assertStatus(400)->assertJsonPath('error.code', 'VALIDATION_ERROR');
        $this->actingAs($this->orga)->putJson($url, ['slots' => array_map(fn ($i) => ['startTime' => sprintf('%02d:00', $i)] + $slot, range(0, 20))])
            ->assertStatus(400)->assertJsonPath('error.message', 'Pas plus de 20 créneaux');
        $this->actingAs($this->orga)->putJson($url, ['slots' => [$slot, ['endTime' => '12:00'] + $slot]])
            ->assertStatus(400)->assertJsonPath('error.message', 'Deux créneaux commencent à la même heure');
        $this->actingAs($this->orga)->putJson($url, ['slots' => [['endTime' => '10:00'] + $slot]])->assertStatus(400);
        $this->actingAs($this->orga)->putJson($url, ['slots' => [['date' => Carbon::yesterday('Europe/Paris')->format('Y-m-d')] + $slot]])
            ->assertStatus(400)->assertJsonPath('error.message', 'Un créneau proposé est déjà passé');
        $this->actingAs($this->orga)->putJson('/api/sessions/' . Str::uuid() . '/slots', ['slots' => [$slot]])->assertNotFound();

        $this->session->update(['scheduling' => 'scheduled']);
        $this->actingAs($this->orga)->putJson($url, ['slots' => [$slot]])->assertStatus(400);
        $this->assertSame(2, SessionSlotProposalModel::count());
    }

    // --- Proposition / retrait par un invité -------------------------------------------------

    public function test_un_invite_propose_un_creneau(): void
    {
        $date = $this->paris(7);

        $this->actingAs($this->paul)->postJson("/api/sessions/{$this->session->id}/slots/propose", [
            'date' => $date, 'startTime' => '19:00', 'endTime' => '20:30',
        ])->assertOk()->assertJsonCount(3, 'data.slotProposals');

        $slot = SessionSlotProposalModel::where('proposed_by', $this->paul->id)->first();
        $this->assertNotNull($slot);
        // Celui qui propose est dispo et a répondu
        $this->assertSame(1, SessionSlotAvailabilityModel::where('slot_id', $slot->id)->where('user_id', $this->paul->id)->count());
        $this->assertNotNull($this->session->participants()->where('user_id', $this->paul->id)->value('slot_responded_at'));

        $this->assertSame('Paul propose un autre créneau', $this->pushesFor($this->orga)[0]['title']);
        $this->assertStringStartsWith('Nouveau créneau proposé', $this->pushesFor($this->dina)[0]['title']);
        $this->assertSame([], $this->pushesFor($this->paul));
    }

    public function test_proposition_limitee_a_deux_par_invite_et_sans_doublon(): void
    {
        $url = "/api/sessions/{$this->session->id}/slots/propose";
        $this->actingAs($this->paul)->postJson($url, ['date' => $this->paris(7), 'startTime' => '19:00', 'endTime' => '20:00'])->assertOk();
        $this->actingAs($this->paul)->postJson($url, ['date' => $this->paris(8), 'startTime' => '19:00', 'endTime' => '20:00'])->assertOk();
        $this->actingAs($this->paul)->postJson($url, ['date' => $this->paris(9), 'startTime' => '19:00', 'endTime' => '20:00'])
            ->assertStatus(400)->assertJsonPath('error.message', 'Tu as déjà proposé 2 créneaux');

        $existing = $this->slots[0]->start_date->copy()->setTimezone('Europe/Paris');
        $this->actingAs($this->dina)->postJson($url, ['date' => $existing->format('Y-m-d'), 'startTime' => $existing->format('H:i'), 'endTime' => '23:00'])
            ->assertStatus(400)->assertJsonPath('error.message', 'Un créneau commence déjà à cette heure : coche-le plutôt');
    }

    public function test_proposition_plafonnee_a_vingt_creneaux(): void
    {
        for ($i = 0; $i < 18; $i++) {
            $this->addSlot($this->day(10 + $i, '12:00'));
        }
        $this->actingAs($this->paul)->postJson("/api/sessions/{$this->session->id}/slots/propose", [
            'date' => $this->paris(40), 'startTime' => '19:00', 'endTime' => '20:00',
        ])->assertStatus(400)->assertJsonPath('error.message', 'Il y a déjà assez de créneaux proposés');
    }

    public function test_proposition_erreurs(): void
    {
        $url = "/api/sessions/{$this->session->id}/slots/propose";
        $slot = ['date' => $this->paris(7), 'startTime' => '19:00', 'endTime' => '20:00'];

        $this->actingAs($this->paul)->postJson($url, [])->assertStatus(400)->assertJsonPath('error.code', 'VALIDATION_ERROR');
        $this->actingAs($this->orga)->postJson($url, $slot)->assertForbidden();
        $this->actingAs($this->makeUser('Zed'))->postJson($url, $slot)->assertForbidden();
        $this->actingAs($this->paul)->postJson($url, ['endTime' => '19:00'] + $slot)->assertStatus(400);
        $this->actingAs($this->paul)->postJson($url, ['date' => Carbon::yesterday('Europe/Paris')->format('Y-m-d')] + $slot)->assertStatus(400);
        $this->actingAs($this->paul)->postJson('/api/sessions/' . Str::uuid() . '/slots/propose', $slot)->assertNotFound();
        $this->session->update(['status' => 'cancelled']);
        $this->actingAs($this->paul)->postJson($url, $slot)->assertStatus(400);
        $this->assertSame(2, SessionSlotProposalModel::count());
    }

    public function test_proposer_apres_avoir_decline_remet_en_attente(): void
    {
        $this->session->participants()->where('user_id', $this->dina->id)->update(['status' => 'declined']);
        $this->actingAs($this->dina)->postJson("/api/sessions/{$this->session->id}/slots/propose", [
            'date' => $this->paris(7), 'startTime' => '19:00', 'endTime' => '20:00',
        ])->assertOk();
        $this->assertSame('pending', $this->participantStatus($this->session, $this->dina));
    }

    public function test_retirer_son_creneau_propose(): void
    {
        $mine = $this->addSlot($this->day(7, '19:00'), $this->paul, [$this->paul]);

        $this->actingAs($this->dina)->deleteJson("/api/sessions/{$this->session->id}/slots/{$mine->id}")
            ->assertForbidden()->assertJsonPath('error.code', 'FORBIDDEN');
        $this->actingAs($this->orga)->deleteJson("/api/sessions/{$this->session->id}/slots/{$this->slots[0]->id}")->assertForbidden();
        $this->actingAs($this->paul)->deleteJson("/api/sessions/{$this->session->id}/slots/" . Str::uuid())->assertNotFound();
        $this->actingAs($this->paul)->deleteJson('/api/sessions/' . Str::uuid() . "/slots/{$mine->id}")->assertNotFound();

        $this->actingAs($this->paul)->deleteJson("/api/sessions/{$this->session->id}/slots/{$mine->id}")
            ->assertOk()->assertJsonCount(2, 'data.slotProposals');
        $this->assertNull(SessionSlotProposalModel::find($mine->id));
        $this->assertSame(0, SessionSlotAvailabilityModel::where('slot_id', $mine->id)->count());
    }

    public function test_retirer_le_dernier_creneau_est_refuse(): void
    {
        SessionSlotProposalModel::whereIn('id', [$this->slots[0]->id, $this->slots[1]->id])->delete();
        $mine = $this->addSlot($this->day(7, '19:00'), $this->paul);

        $this->actingAs($this->paul)->deleteJson("/api/sessions/{$this->session->id}/slots/{$mine->id}")
            ->assertStatus(400)->assertJsonPath('error.message', 'Il doit rester au moins un créneau');

        $this->session->update(['scheduling' => 'scheduled']);
        $this->actingAs($this->paul)->deleteJson("/api/sessions/{$this->session->id}/slots/{$mine->id}")->assertStatus(400);
        $this->assertNotNull(SessionSlotProposalModel::find($mine->id));
    }
}
