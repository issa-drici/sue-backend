<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use App\Models\UserModel;
use App\Models\SportSessionModel;
use App\Models\SportSessionParticipantModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GetUserProfileStatsTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un utilisateur de test
        $this->user = UserModel::factory()->create();
    }

    public function test_get_user_profile_returns_correct_stats_format(): void
    {
        // Créer quelques sessions pour l'utilisateur
        SportSessionModel::factory()->count(3)->create([
            'organizer_id' => $this->user->id,
            'status' => 'active'
        ]);

        // Créer une session annulée (ne doit pas être comptée)
        SportSessionModel::factory()->create([
            'organizer_id' => $this->user->id,
            'status' => 'cancelled'
        ]);

        // Créer des participations pour l'utilisateur
        $otherSessions = SportSessionModel::factory()->count(2)->create();

        foreach ($otherSessions as $session) {
            SportSessionParticipantModel::factory()->create([
                'session_id' => $session->id,
                'user_id' => $this->user->id,
                'status' => 'accepted'
            ]);
        }

        // Créer une participation refusée (ne doit pas être comptée)
        $declinedSession = SportSessionModel::factory()->create();
        SportSessionParticipantModel::factory()->create([
            'session_id' => $declinedSession->id,
            'user_id' => $this->user->id,
            'status' => 'declined'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/users/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'firstname',
                    'lastname',
                    'email',
                    'avatar',
                    'stats' => [
                        'sessionsCreated',
                        'sessionsParticipated'
                    ]
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->user->id,
                    'firstname' => $this->user->firstname,
                    'lastname' => $this->user->lastname,
                    'email' => $this->user->email,
                    'stats' => [
                        'sessionsCreated' => 3, // 3 sessions actives, 1 annulée exclue
                        'sessionsParticipated' => 2 // 2 participations acceptées, 1 refusée exclue
                    ]
                ]
            ]);
    }

    public function test_get_user_profile_returns_zero_stats_for_new_user(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/users/profile');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->user->id,
                    'stats' => [
                        'sessionsCreated' => 0,
                        'sessionsParticipated' => 0
                    ]
                ]
            ]);
    }

    public function test_get_user_profile_excludes_cancelled_sessions_from_created_count(): void
    {
        // Créer des sessions annulées
        SportSessionModel::factory()->count(2)->create([
            'organizer_id' => $this->user->id,
            'status' => 'cancelled'
        ]);

        // Créer une session active
        SportSessionModel::factory()->create([
            'organizer_id' => $this->user->id,
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/users/profile');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'stats' => [
                        'sessionsCreated' => 1, // Seulement la session active
                        'sessionsParticipated' => 0
                    ]
                ]
            ]);
    }

    public function test_get_user_profile_only_counts_accepted_participations(): void
    {
        // Créer des sessions d'autres utilisateurs
        $otherSessions = SportSessionModel::factory()->count(3)->create();

        // Créer des participations avec différents statuts
        SportSessionParticipantModel::factory()->create([
            'session_id' => $otherSessions[0]->id,
            'user_id' => $this->user->id,
            'status' => 'accepted'
        ]);

        SportSessionParticipantModel::factory()->create([
            'session_id' => $otherSessions[1]->id,
            'user_id' => $this->user->id,
            'status' => 'pending'
        ]);

        SportSessionParticipantModel::factory()->create([
            'session_id' => $otherSessions[2]->id,
            'user_id' => $this->user->id,
            'status' => 'declined'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/users/profile');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'stats' => [
                        'sessionsCreated' => 0,
                        'sessionsParticipated' => 1 // Seulement la participation acceptée
                    ]
                ]
            ]);
    }

    public function test_get_user_profile_performance_with_many_sessions(): void
    {
        // Créer beaucoup de sessions pour tester les performances
        SportSessionModel::factory()->count(50)->create([
            'organizer_id' => $this->user->id,
            'status' => 'active'
        ]);

        // Créer beaucoup de participations
        $otherSessions = SportSessionModel::factory()->count(30)->create();
        foreach ($otherSessions as $session) {
            SportSessionParticipantModel::factory()->create([
                'session_id' => $session->id,
                'user_id' => $this->user->id,
                'status' => 'accepted'
            ]);
        }

        $startTime = microtime(true);

        $response = $this->actingAs($this->user)
            ->getJson('/api/users/profile');

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'stats' => [
                        'sessionsCreated' => 50,
                        'sessionsParticipated' => 30
                    ]
                ]
            ]);

        // Vérifier que l'exécution est rapide (moins de 100ms)
        $this->assertLessThan(0.1, $executionTime, 'La requête doit être exécutée en moins de 100ms');
    }
}
