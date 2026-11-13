<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\SueTestHelpers;
use Tests\TestCase;

/**
 * Autorisations des canaux temps réel (routes/channels.php) via POST /broadcasting/auth.
 */
class BroadcastChannelsTest extends TestCase
{
    use RefreshDatabase, SueTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher.key' => 'key',
            'broadcasting.connections.pusher.secret' => 'secret',
            'broadcasting.connections.pusher.app_id' => 'app',
        ]);
        require base_path('routes/channels.php'); // canaux enregistrés sur le pilote pusher
    }

    private function auth(string $channel)
    {
        return $this->postJson('/broadcasting/auth', ['socket_id' => '123.456', 'channel_name' => "private-{$channel}"]);
    }

    public function test_authentification_requise(): void
    {
        $this->auth('App.Models.User.' . Str::uuid())->assertUnauthorized();
    }

    public function test_canal_utilisateur_accessible_a_son_proprietaire(): void
    {
        $alice = $this->makeUser('Alice');
        $this->actingAs($alice)->auth("App.Models.User.{$alice->id}")->assertOk()->assertJsonStructure(['auth']);
    }

    public function test_canal_utilisateur_refuse_aux_autres(): void
    {
        // Identifiants commençant par une lettre (cas fréquent : ~40 % des UUID)
        $alice = $this->makeUser('Alice', ['id' => 'aaaaaaaa-0000-4000-8000-000000000001'], false);
        $bob = $this->makeUser('Bob', ['id' => 'bbbbbbbb-0000-4000-8000-000000000002'], false);

        $this->actingAs($alice)->auth("App.Models.User.{$bob->id}")->assertForbidden();
    }

    public function test_canaux_de_session_reserves_aux_membres_meme_refuses(): void
    {
        $orga = $this->makeUser('Orga');
        $pending = $this->makeUser('Paul');
        $declined = $this->makeUser('Dina');
        $outsider = $this->makeUser('Zed');
        $session = $this->makeSession($orga, [[$pending, 'pending'], [$declined, 'declined']]);

        foreach (["session-chat.{$session->id}", "sport-session.{$session->id}"] as $channel) {
            foreach ([$orga, $pending, $declined] as $member) {
                $this->actingAs($member)->auth($channel)->assertOk();
            }
            $this->actingAs($outsider)->auth($channel)->assertForbidden();
        }

        $this->actingAs($orga)->auth('sport-session.' . Str::uuid())->assertForbidden();
    }
}
