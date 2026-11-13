<?php

namespace Tests\Feature;

use App\Models\SupportRequestModel;
use App\Services\SportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\SueTestHelpers;
use Tests\TestCase;

/**
 * Petits endpoints : racine web, version, sports, sports joués, support.
 */
class MiscEndpointsTest extends TestCase
{
    use RefreshDatabase, SueTestHelpers;

    public function test_racine_web_renvoie_la_version_de_laravel(): void
    {
        $this->get('/')->assertOk()->assertJsonStructure(['Laravel']);
    }

    public function test_route_de_sante(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_version_est_publique(): void
    {
        $this->getJson('/api/version')
            ->assertOk()
            ->assertJsonStructure(['ios_version', 'android_version', 'force_update', 'update_message'])
            ->assertJsonPath('force_update', false);
    }

    public function test_liste_des_sports(): void
    {
        $this->getJson('/api/sports')->assertUnauthorized();

        $this->actingAs($this->makeUser('Alice'))->getJson('/api/sports')
            ->assertOk()
            ->assertJson(['success' => true, 'data' => SportService::getSupportedSports()]);
    }

    public function test_sports_joues_tries_par_nombre_de_sessions(): void
    {
        $this->getJson('/api/users/played-sports')->assertUnauthorized();

        $me = $this->makeUser('Me');
        $orga = $this->makeUser('Orga');
        $this->makeSession($orga, [[$me, 'accepted']], ['sport' => 'tennis']);
        $this->makeSession($orga, [[$me, 'accepted']], ['sport' => 'tennis']);
        $this->makeSession($me, [], ['sport' => 'football']);
        $this->makeSession($orga, [[$me, 'pending']], ['sport' => 'padel']);                    // pas accepté
        $this->makeSession($orga, [[$me, 'accepted']], ['sport' => 'golf', 'status' => 'cancelled']); // annulée

        $this->actingAs($me)->getJson('/api/users/played-sports')
            ->assertOk()
            ->assertExactJson(['success' => true, 'data' => [
                ['sport' => 'tennis', 'count' => 2],
                ['sport' => 'football', 'count' => 1],
            ]]);
    }

    public function test_support_creer_et_lister_ses_demandes(): void
    {
        $this->postJson('/api/support', ['message' => 'x'])->assertUnauthorized();
        $this->getJson('/api/support')->assertUnauthorized();

        $me = $this->makeUser('Me');
        $other = $this->makeUser('Other');
        SupportRequestModel::create(['id' => (string) Str::uuid(), 'user_id' => $other->id, 'message' => 'Pas à moi']);

        $this->actingAs($me)->postJson('/api/support', ['message' => 'Bonjour, besoin d\'aide'])
            ->assertCreated()
            ->assertJsonPath('message', 'Bonjour, besoin d\'aide')
            ->assertJsonStructure(['id', 'message', 'created_at']);
        $this->assertDatabaseHas('support_requests', ['user_id' => $me->id, 'message' => 'Bonjour, besoin d\'aide']);

        $this->actingAs($me)->getJson('/api/support')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.message', 'Bonjour, besoin d\'aide')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_support_message_invalide_renvoie_422(): void
    {
        $me = $this->makeUser('Me');
        $this->actingAs($me)->postJson('/api/support', [])->assertStatus(422)
            ->assertJsonPath('message', 'Écris ton message.')
            ->assertJsonStructure(['errors' => ['message']]);
        $this->actingAs($me)->postJson('/api/support', ['message' => str_repeat('a', 1001)])->assertStatus(422)
            ->assertJsonPath('message', 'Ton message est trop long (1000 caractères max).');
        $this->assertSame(0, SupportRequestModel::count());
    }
}
