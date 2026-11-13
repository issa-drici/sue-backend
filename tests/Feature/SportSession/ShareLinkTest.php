<?php

namespace Tests\Feature\SportSession;

use App\Models\FriendRequestModel;
use App\Models\NotificationModel;
use App\Models\UserReferralModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\SueTestHelpers;
use Tests\TestCase;

/**
 * Liens de partage (Universal Links) : aperçu public GET /api/join/{token} et
 * rejoindre/décliner POST /api/join/{token}.
 */
class ShareLinkTest extends TestCase
{
    use RefreshDatabase, SueTestHelpers;

    private function sharedSession($organizer, array $participants = [], array $attributes = [])
    {
        return $this->makeSession($organizer, $participants, ['share_token' => Str::random(40)] + $attributes);
    }

    // --- Aperçu public --------------------------------------------------------------------

    public function test_apercu_public_sans_donnees_personnelles(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $session = $this->sharedSession($orga, [[$paul, 'accepted']], ['sport' => 'padel', 'max_participants' => 4]);

        $response = $this->getJson("/api/join/{$session->share_token}")
            ->assertOk()
            ->assertJson(['success' => true, 'data' => [
                'sport' => 'padel', 'maxParticipants' => 4, 'participantsCount' => 2,
                'organizer' => ['fullName' => 'Orga Test'],
            ]])
            ->assertJsonMissingPath('data.sessionId')
            ->assertJsonMissingPath('data.viewerStatus')
            ->assertJsonMissingPath('data.inviter');

        $json = json_encode($response->json());
        foreach ([$session->id, $orga->id, $paul->id, $orga->phone, $paul->phone] as $secret) {
            $this->assertStringNotContainsString($secret, $json);
        }
    }

    public function test_apercu_avec_inviteur_et_spectateur_connecte(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $outsider = $this->makeUser('Zed');
        $session = $this->sharedSession($orga, [[$paul, 'accepted']]);

        // ?from d'un participant : exposé ; d'un inconnu : ignoré
        $this->getJson("/api/join/{$session->share_token}?from={$paul->id}")->assertJsonPath('data.inviter.fullName', 'Paul Test');
        $this->getJson("/api/join/{$session->share_token}?from={$outsider->id}")->assertJsonMissingPath('data.inviter');

        $this->withToken($paul->createToken('t')->plainTextToken)->getJson("/api/join/{$session->share_token}")
            ->assertJsonPath('data.viewerRelationship', 'participant')
            ->assertJsonPath('data.viewerStatus', 'accepted')
            ->assertJsonPath('data.sessionId', $session->id);

        $this->app['auth']->forgetGuards();
        $this->withToken($orga->createToken('t')->plainTextToken)->getJson("/api/join/{$session->share_token}")
            ->assertJsonPath('data.viewerRelationship', 'organizer')->assertJsonPath('data.viewerStatus', 'organizer');

        $this->app['auth']->forgetGuards();
        $this->withToken($outsider->createToken('t')->plainTextToken)->getJson("/api/join/{$session->share_token}")
            ->assertJsonPath('data.viewerStatus', null)->assertJsonMissingPath('data.sessionId');
    }

    public function test_lien_invalide_expire_ou_annule(): void
    {
        $orga = $this->makeUser('Orga');
        $ended = $this->sharedSession($orga, [], ['start_date' => now()->subHours(3), 'end_date' => now()->subHour()]);
        $cancelled = $this->sharedSession($orga, [], ['status' => 'cancelled']);

        foreach (['inconnu', $ended->share_token, $cancelled->share_token] as $token) {
            $this->getJson("/api/join/{$token}")->assertNotFound()->assertJsonPath('error.code', 'INVALID_SHARE_LINK');
        }
    }

    public function test_apercu_rate_limite(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->getJson('/api/join/inconnu')->assertNotFound();
        }
        $this->getJson('/api/join/inconnu')->assertStatus(429);
    }

    // --- Rejoindre ------------------------------------------------------------------------

    public function test_rejoindre_par_lien_accepte_notifie_et_cree_l_amitie(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $newbie = $this->makeUser('Newbie');
        $session = $this->sharedSession($orga, [[$paul, 'accepted']], ['sport' => 'tennis']);
        $this->friendRequest($newbie, $paul); // demande en attente, nettoyée par l'amitié automatique

        $this->actingAs($newbie)->postJson("/api/join/{$session->share_token}", ['from' => $paul->id])
            ->assertOk()->assertJsonPath('data.id', $session->id)->assertJsonPath('message', 'Vous avez rejoint la session');

        $this->assertSame('accepted', $this->participantStatus($session, $newbie));
        $this->assertTrue($this->areFriends($newbie, $paul));
        $this->assertFalse($this->areFriends($newbie, $orga));
        $this->assertSame(0, FriendRequestModel::count());
        $this->assertContains('tennis', $newbie->fresh()->sports_preferences);
        $this->assertSame('Nouveau participant', $this->pushesFor($orga)[0]['title']);
        // Plus de commentaire système (ancien système supprimé)
        $this->assertDatabaseCount('sport_session_comments', 0);
        // Compte créé il y a moins de 24 h : parrainage pour celui qui a partagé
        $this->assertSame($paul->id, UserReferralModel::where('referred_id', $newbie->id)->value('referrer_id'));
    }

    public function test_rejoindre_sans_from_rend_ami_avec_l_organisateur_et_est_idempotent(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $session = $this->sharedSession($orga);

        $this->actingAs($paul)->postJson("/api/join/{$session->share_token}")->assertOk();
        $this->actingAs($paul)->postJson("/api/join/{$session->share_token}")->assertOk();

        $this->assertTrue($this->areFriends($paul, $orga));
        $this->assertSame(1, NotificationModel::where('user_id', $orga->id)->count());
        $this->assertCount(1, $this->pushesFor($orga));
    }

    public function test_un_compte_ancien_ne_compte_pas_comme_parrainage(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $paul->forceFill(['created_at' => now()->subDays(3)])->save();
        $session = $this->sharedSession($orga);

        $this->actingAs($paul)->postJson("/api/join/{$session->share_token}")->assertOk();
        $this->assertSame(0, UserReferralModel::count());
    }

    public function test_l_organisateur_qui_rejoint_ne_change_rien(): void
    {
        $orga = $this->makeUser('Orga');
        $session = $this->sharedSession($orga);

        $this->actingAs($orga)->postJson("/api/join/{$session->share_token}")->assertOk();
        $this->assertSame(0, NotificationModel::count());
        $this->assertDatabaseCount('friends', 0);
    }

    public function test_un_invite_qui_avait_decline_peut_rejoindre(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $session = $this->sharedSession($orga, [[$paul, 'declined']]);

        $this->actingAs($paul)->postJson("/api/join/{$session->share_token}")->assertOk();
        $this->assertSame('accepted', $this->participantStatus($session, $paul));
    }

    public function test_session_complete(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $dina = $this->makeUser('Dina');
        $session = $this->sharedSession($orga, [[$paul, 'accepted']], ['max_participants' => 2]);

        $this->actingAs($dina)->postJson("/api/join/{$session->share_token}")
            ->assertStatus(409)->assertJsonPath('error.code', 'SESSION_FULL');
        $this->assertNull($this->participantStatus($session, $dina));
    }

    public function test_rejoindre_un_lien_invalide(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $cancelled = $this->sharedSession($orga, [], ['status' => 'cancelled']);

        $this->actingAs($paul)->postJson('/api/join/inconnu')->assertNotFound()->assertJsonPath('error.code', 'INVALID_SHARE_LINK');
        $this->actingAs($paul)->postJson("/api/join/{$cancelled->share_token}")->assertNotFound();
        $this->actingAs($paul)->postJson("/api/join/{$cancelled->share_token}", ['response' => 'decline'])->assertNotFound();
        $this->assertNull($this->participantStatus($cancelled, $paul));
    }

    public function test_rejoindre_une_session_a_creneau_a_definir_comme_invite(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $session = $this->sharedSession($orga, [], ['scheduling' => 'finding_slot', 'max_participants' => 1]);

        // Pas de contrôle de limite : on vient donner ses dispos
        $this->actingAs($paul)->postJson("/api/join/{$session->share_token}")->assertOk();
        $this->assertSame('pending', $this->participantStatus($session, $paul));
        $this->assertTrue($this->areFriends($paul, $orga));
        $this->assertSame(0, NotificationModel::count());

        // Deuxième fois : rien ne change
        $this->actingAs($paul)->postJson("/api/join/{$session->share_token}")->assertOk();
        $this->assertSame('pending', $this->participantStatus($session, $paul));
    }

    // --- Décliner depuis la page web ------------------------------------------------------

    public function test_decliner_par_lien(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $session = $this->sharedSession($orga);

        $this->actingAs($paul)->postJson("/api/join/{$session->share_token}", ['response' => 'decline'])
            ->assertOk()->assertJsonPath('message', 'Réponse enregistrée');

        $this->assertSame('declined', $this->participantStatus($session, $paul));
        $this->assertSame('Invitation déclinée', $this->pushesFor($orga)[0]['title']);
        $this->assertDatabaseCount('friends', 0);

        // L'organisateur qui « décline » son propre lien : sans effet
        $this->actingAs($orga)->postJson("/api/join/{$session->share_token}", ['response' => 'decline'])->assertOk();
        $this->assertSame('accepted', $this->participantStatus($session, $orga));
    }
}
