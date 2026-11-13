<?php

namespace Tests\Feature\SportSession;

use App\Events\UserOffline;
use App\Events\UserOnline;
use App\Events\UserTyping;
use App\Models\SportSessionModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Concerns\SueTestHelpers;
use Tests\TestCase;

/**
 * Présence en temps réel (rejoindre / quitter / écrire / en ligne). L'ancien système de
 * commentaires a été supprimé (remplacé par le chat de session).
 */
class PresenceTest extends TestCase
{
    use RefreshDatabase, SueTestHelpers;

    private UserModel $orga;
    private UserModel $paul;
    private UserModel $dina;
    private UserModel $zoe;
    private SportSessionModel $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orga = $this->makeUser('Orga');
        $this->paul = $this->makeUser('Paul');
        $this->dina = $this->makeUser('Dina');
        $this->zoe = $this->makeUser('Zoe');
        $this->session = $this->makeSession($this->orga, [[$this->paul, 'accepted'], [$this->dina, 'pending'], [$this->zoe, 'declined']]);
    }

    // --- Ancien système de commentaires (supprimé) ----------------------------------------

    public function test_les_routes_de_commentaires_n_existent_plus(): void
    {
        $base = "/api/sessions/{$this->session->id}/comments";
        $id = Str::uuid();

        $this->actingAs($this->paul)->getJson($base)->assertNotFound();
        $this->actingAs($this->paul)->postJson($base, ['content' => 'Salut'])->assertNotFound();
        $this->actingAs($this->paul)->putJson("{$base}/{$id}", ['content' => 'x'])->assertNotFound();
        $this->actingAs($this->paul)->deleteJson("{$base}/{$id}")->assertNotFound();
    }

    public function test_le_detail_d_une_session_renvoie_toujours_comments_vide(): void
    {
        // Champ gardé (toujours vide) pour les versions de l'app qui le lisent encore
        $this->actingAs($this->paul)->getJson("/api/sessions/{$this->session->id}")
            ->assertOk()->assertJsonPath('data.comments', []);
    }

    // --- Présence ------------------------------------------------------------------------

    public function test_presence_rejoindre_ecrire_lister_quitter(): void
    {
        Event::fake([UserOnline::class, UserOffline::class, UserTyping::class]);
        $base = "/api/sessions/{$this->session->id}/presence";

        $this->actingAs($this->paul)->postJson("{$base}/join")->assertOk()
            ->assertJsonPath('data.userId', $this->paul->id)->assertJsonStructure(['data' => ['joinedAt']]);
        $this->actingAs($this->orga)->postJson("{$base}/join")->assertOk();
        Event::assertDispatchedTimes(UserOnline::class, 2);

        $this->actingAs($this->dina)->getJson("{$base}/users")->assertOk()->assertJsonPath('total', 2);

        $this->actingAs($this->paul)->postJson("{$base}/typing", ['isTyping' => true])->assertOk()->assertJsonPath('data.isTyping', true);
        Event::assertDispatched(UserTyping::class);
        $this->assertDatabaseHas('sport_session_presence', ['user_id' => $this->paul->id, 'is_typing' => true]);

        $this->actingAs($this->paul)->postJson("{$base}/leave")->assertOk()->assertJsonStructure(['data' => ['leftAt']]);
        Event::assertDispatched(UserOffline::class);
        $this->actingAs($this->dina)->getJson("{$base}/users")->assertJsonPath('total', 1);
    }

    public function test_rejoindre_la_presence_une_deuxieme_fois(): void
    {
        $base = "/api/sessions/{$this->session->id}/presence";
        $this->actingAs($this->paul)->postJson("{$base}/join")->assertOk();
        $id = \App\Models\SportSessionPresenceModel::value('id');
        $this->actingAs($this->paul)->postJson("{$base}/join")->assertOk(); // re-connexion sans leave
        $this->actingAs($this->paul)->postJson("{$base}/leave")->assertOk();
        $this->actingAs($this->paul)->postJson("{$base}/join")->assertOk(); // retour après leave
        $this->assertDatabaseCount('sport_session_presence', 1);
        $this->assertDatabaseHas('sport_session_presence', ['id' => $id, 'user_id' => $this->paul->id, 'is_online' => true]);
        $this->actingAs($this->dina)->getJson("{$base}/users")->assertJsonPath('total', 1);
    }

    public function test_presence_erreurs(): void
    {
        $this->actingAs($this->paul)->postJson('/api/sessions/' . Str::uuid() . '/presence/join')->assertStatus(400);
        $this->actingAs($this->paul)->postJson('/api/sessions/pas-un-uuid/presence/leave')->assertStatus(400);
        $this->actingAs($this->paul)->postJson('/api/sessions/' . Str::uuid() . '/presence/typing', ['isTyping' => true])->assertStatus(400);
        $this->actingAs($this->paul)->getJson('/api/sessions/' . Str::uuid() . '/presence/users')->assertNotFound();
    }

    public function test_quitter_ou_ecrire_sans_etre_present_ne_renvoie_pas_500(): void
    {
        Event::fake([UserOffline::class]);
        $base = "/api/sessions/{$this->session->id}/presence";

        // Quitter sans avoir rejoint : rien à faire, 200
        $this->actingAs($this->paul)->postJson("{$base}/leave")->assertOk()->assertJsonPath('success', true);
        Event::assertNotDispatched(UserOffline::class);
        $this->assertDatabaseCount('sport_session_presence', 0);

        // Écrire sans avoir rejoint : 400 avec un message clair
        $this->actingAs($this->paul)->postJson("{$base}/typing", ['isTyping' => true])
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'NOT_PRESENT')
            ->assertJsonPath('error.message', "Rejoins d'abord la session pour écrire.");
    }

    public function test_la_presence_est_ouverte_a_tout_utilisateur_connecte(): void
    {
        // Choix produit : comme le détail d'une session (accessible avec le lien), la présence
        // n'est pas réservée aux membres
        $outsider = $this->makeUser('Intrus');
        $this->actingAs($outsider)->postJson("/api/sessions/{$this->session->id}/presence/join")->assertOk();
        $this->actingAs($outsider)->getJson("/api/sessions/{$this->session->id}/presence/users")->assertOk()->assertJsonPath('total', 1);
    }
}
