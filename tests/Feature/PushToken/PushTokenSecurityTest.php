<?php

namespace Tests\Feature\PushToken;

use App\Models\PushTokenModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushTokenSecurityTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $user1;
    private UserModel $user2;
    private string $token1;
    private string $token2;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer deux utilisateurs de test
        $this->user1 = UserModel::factory()->create();
        $this->user2 = UserModel::factory()->create();

        // Créer des tokens de test
        $this->token1 = 'ExponentPushToken[test-token-user1]';
        $this->token2 = 'ExponentPushToken[test-token-user2]';

        // Créer des tokens en base pour les deux utilisateurs
        PushTokenModel::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $this->user1->id,
            'token' => $this->token1,
            'platform' => 'ios',
            'is_active' => true,
        ]);

        PushTokenModel::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $this->user2->id,
            'token' => $this->token2,
            'platform' => 'ios',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function user_can_delete_their_own_token()
    {
        $response = $this->actingAs($this->user1)
            ->deleteJson('/api/push-tokens', [
                'token' => $this->token1
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Vérifier que le token a été supprimé
        $this->assertDatabaseMissing('push_tokens', [
            'token' => $this->token1,
            'user_id' => $this->user1->id
        ]);
    }

    /** @test */
    public function user_cannot_delete_another_users_token()
    {
        $response = $this->actingAs($this->user1)
            ->deleteJson('/api/push-tokens', [
                'token' => $this->token2 // Token de user2
            ]);

        $response->assertStatus(404)
            ->assertJson(['success' => false]);

        // Vérifier que le token de user2 n'a PAS été supprimé
        $this->assertDatabaseHas('push_tokens', [
            'token' => $this->token2,
            'user_id' => $this->user2->id
        ]);
    }

    /** @test */
    public function user_cannot_delete_nonexistent_token()
    {
        $response = $this->actingAs($this->user1)
            ->deleteJson('/api/push-tokens', [
                'token' => 'ExponentPushToken[nonexistent-token]'
            ]);

        $response->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function unauthenticated_user_cannot_delete_token()
    {
        $response = $this->deleteJson('/api/push-tokens', [
            'token' => $this->token1
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function user_can_register_token_successfully()
    {
        $newToken = 'ExponentPushToken[new-token]';

        $response = $this->actingAs($this->user1)
            ->postJson('/api/push-tokens', [
                'token' => $newToken,
                'platform' => 'ios'
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Vérifier que le token a été enregistré
        $this->assertDatabaseHas('push_tokens', [
            'token' => $newToken,
            'user_id' => $this->user1->id,
            'platform' => 'ios',
            'is_active' => true
        ]);
    }

    /** @test */
    public function token_registration_handles_conflicts_correctly()
    {
        // User1 enregistre un token
        $sharedToken = 'ExponentPushToken[shared-token]';

        $response1 = $this->actingAs($this->user1)
            ->postJson('/api/push-tokens', [
                'token' => $sharedToken,
                'platform' => 'ios'
            ]);

        $response1->assertStatus(200);

        // User2 enregistre le même token (conflit)
        $response2 = $this->actingAs($this->user2)
            ->postJson('/api/push-tokens', [
                'token' => $sharedToken,
                'platform' => 'ios'
            ]);

        $response2->assertStatus(200);

        // Vérifier que le token appartient maintenant à user2
        $this->assertDatabaseHas('push_tokens', [
            'token' => $sharedToken,
            'user_id' => $this->user2->id
        ]);

        // Vérifier qu'il n'y a qu'un seul enregistrement pour ce token
        $this->assertEquals(1, PushTokenModel::where('token', $sharedToken)->count());
    }

    /** @test */
    public function token_validation_works_correctly()
    {
        // Test avec token invalide
        $response = $this->actingAs($this->user1)
            ->postJson('/api/push-tokens', [
                'token' => 'invalid-token-format',
                'platform' => 'ios'
            ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);

        // Test avec données manquantes
        $response = $this->actingAs($this->user1)
            ->postJson('/api/push-tokens', [
                'platform' => 'ios'
                // token manquant
            ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function multi_user_device_scenario_works_correctly()
    {
        $deviceToken = 'ExponentPushToken[device-specific-token]';

        // User1 se connecte sur l'appareil et enregistre le token
        $response1 = $this->actingAs($this->user1)
            ->postJson('/api/push-tokens', [
                'token' => $deviceToken,
                'platform' => 'ios',
                'device_id' => 'device-123'
            ]);

        $response1->assertStatus(200);

        // User1 se déconnecte et supprime son token
        $response2 = $this->actingAs($this->user1)
            ->deleteJson('/api/push-tokens', [
                'token' => $deviceToken
            ]);

        $response2->assertStatus(200);

        // User2 se connecte sur le même appareil et enregistre le même token
        $response3 = $this->actingAs($this->user2)
            ->postJson('/api/push-tokens', [
                'token' => $deviceToken,
                'platform' => 'ios',
                'device_id' => 'device-123'
            ]);

        $response3->assertStatus(200);

        // Vérifier que le token appartient maintenant à user2
        $this->assertDatabaseHas('push_tokens', [
            'token' => $deviceToken,
            'user_id' => $this->user2->id,
            'device_id' => 'device-123'
        ]);

        // Vérifier qu'il n'y a qu'un seul enregistrement
        $this->assertEquals(1, PushTokenModel::where('token', $deviceToken)->count());
    }
}

