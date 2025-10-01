<?php

namespace Tests\Feature\User;

use App\Models\UserModel;
use App\Models\SportSessionModel;
use App\Models\SportSessionParticipantModel;
use App\Models\FriendModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class FindUserByIdTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private UserModel $user;
    private UserModel $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un utilisateur principal
        $this->user = UserModel::factory()->create([
            'firstname' => 'Jean',
            'lastname' => 'Dupont',
            'email' => 'jean.dupont@example.com',
        ]);

        // Créer un autre utilisateur
        $this->otherUser = UserModel::factory()->create([
            'firstname' => 'Marie',
            'lastname' => 'Martin',
            'email' => 'marie.martin@example.com',
            'sports_preferences' => ['tennis', 'football', 'basketball'],
        ]);

        // Créer des sessions sportives pour les statistiques
        $this->createTestSessions();
        
        // Créer des relations d'amitié pour les tests
        $this->createTestFriendships();
    }

    private function createTestSessions(): void
    {
        // Sessions créées par l'utilisateur principal
        $session1 = SportSessionModel::factory()->create([
            'organizer_id' => $this->user->id,
            'sport' => 'tennis',
            'date' => now()->addDays(7)->format('Y-m-d'),
            'start_time' => '14:00',
            'end_time' => '16:00',
            'location' => 'Tennis Club',
        ]);

        $session2 = SportSessionModel::factory()->create([
            'organizer_id' => $this->user->id,
            'sport' => 'football',
            'date' => now()->addDays(10)->format('Y-m-d'),
            'start_time' => '16:00',
            'end_time' => '18:00',
            'location' => 'Stade Municipal',
        ]);

        // Sessions créées par l'autre utilisateur
        $session3 = SportSessionModel::factory()->create([
            'organizer_id' => $this->otherUser->id,
            'sport' => 'basketball',
            'date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '18:00',
            'end_time' => '20:00',
            'location' => 'Gymnase',
        ]);

        // Participations de l'utilisateur principal
        SportSessionParticipantModel::create([
            'id' => $this->faker->uuid(),
            'session_id' => $session1->id,
            'user_id' => $this->user->id,
            'status' => 'accepted',
        ]);

        SportSessionParticipantModel::create([
            'id' => $this->faker->uuid(),
            'session_id' => $session2->id,
            'user_id' => $this->user->id,
            'status' => 'accepted',
        ]);

        SportSessionParticipantModel::create([
            'id' => $this->faker->uuid(),
            'session_id' => $session3->id,
            'user_id' => $this->user->id,
            'status' => 'accepted',
        ]);

        // Participation refusée (ne doit pas compter)
        SportSessionParticipantModel::create([
            'id' => $this->faker->uuid(),
            'session_id' => $session3->id,
            'user_id' => $this->otherUser->id,
            'status' => 'declined',
        ]);
    }

    private function createTestFriendships(): void
    {
        // Créer une amitié entre l'utilisateur principal et l'autre utilisateur
        FriendModel::create([
            'id' => $this->faker->uuid(),
            'user_id' => $this->user->id,
            'friend_id' => $this->otherUser->id,
        ]);

        FriendModel::create([
            'id' => $this->faker->uuid(),
            'user_id' => $this->otherUser->id,
            'friend_id' => $this->user->id,
        ]);
    }

    /**
     * Test récupération d'un utilisateur existant avec authentification
     */
    public function test_can_get_user_by_id_with_authentication(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/users/{$this->user->id}");

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
                    ],
                    'isAlreadyFriend'
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->user->id,
                    'firstname' => 'Jean',
                    'lastname' => 'Dupont',
                    'email' => 'jean.dupont@example.com',
                    'stats' => [
                        'sessionsCreated' => 2,
                        'sessionsParticipated' => 3
                    ],
                    'isAlreadyFriend' => false
                ]
            ]);
    }

    /**
     * Test récupération d'un autre utilisateur (permissions)
     */
    public function test_can_get_other_user_by_id(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/users/{$this->otherUser->id}");

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
                    ],
                    'isAlreadyFriend'
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->otherUser->id,
                    'firstname' => 'Marie',
                    'lastname' => 'Martin',
                    'email' => 'marie.martin@example.com',
                    'stats' => [
                        'sessionsCreated' => 1,
                        'sessionsParticipated' => 0
                    ],
                    'isAlreadyFriend' => true
                ]
            ]);
    }

    /**
     * Test utilisateur inexistant
     */
    public function test_returns_404_for_nonexistent_user(): void
    {
        $nonexistentId = $this->faker->uuid();

        $response = $this->actingAs($this->user)
            ->getJson("/api/users/{$nonexistentId}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ]);
    }

    /**
     * Test sans authentification
     */
    public function test_returns_401_without_authentication(): void
    {
        $response = $this->getJson("/api/users/{$this->user->id}");

        $response->assertStatus(401);
    }

    /**
     * Test avec un utilisateur sans sessions
     */
    public function test_user_without_sessions_returns_zero_stats(): void
    {
        $newUser = UserModel::factory()->create([
            'firstname' => 'Pierre',
            'lastname' => 'Durand',
            'email' => 'pierre.durand@example.com',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/users/{$newUser->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $newUser->id,
                    'firstname' => 'Pierre',
                    'lastname' => 'Durand',
                    'email' => 'pierre.durand@example.com',
                    'stats' => [
                        'sessionsCreated' => 0,
                        'sessionsParticipated' => 0
                    ],
                    'isAlreadyFriend' => false
                ]
            ]);
    }

    /**
     * Test que l'avatar peut être null
     */
    public function test_avatar_can_be_null(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/users/{$this->user->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'firstname',
                    'lastname',
                    'email',
                    'avatar',
                    'stats',
                    'isAlreadyFriend'
                ]
            ]);

        // L'avatar peut être null ou une URL
        $data = $response->json('data');
        $this->assertArrayHasKey('avatar', $data);
        $this->assertTrue($data['avatar'] === null || is_string($data['avatar']));
    }

    /**
     * Test que isAlreadyFriend retourne true pour un ami
     */
    public function test_isAlreadyFriend_returns_true_for_friend(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/users/{$this->otherUser->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->otherUser->id,
                    'isAlreadyFriend' => true
                ]
            ]);
    }

    /**
     * Test que isAlreadyFriend retourne false pour un non-ami
     */
    public function test_isAlreadyFriend_returns_false_for_non_friend(): void
    {
        $newUser = UserModel::factory()->create([
            'firstname' => 'Pierre',
            'lastname' => 'Durand',
            'email' => 'pierre.durand@example.com',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/users/{$newUser->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $newUser->id,
                    'isAlreadyFriend' => false
                ]
            ]);
    }

    /**
     * Test que isAlreadyFriend retourne false pour son propre profil
     */
    public function test_isAlreadyFriend_returns_false_for_own_profile(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/users/{$this->user->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->user->id,
                    'isAlreadyFriend' => false
                ]
            ]);
    }

    /**
     * Test que les sessions declined ne sont pas comptées dans sessionsParticipated
     */
    public function test_declined_sessions_are_not_counted_in_participations(): void
    {
        // Créer un nouvel utilisateur
        $newUser = UserModel::factory()->create([
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'test.user@example.com',
        ]);

        // Créer une session
        $session = SportSessionModel::factory()->create([
            'organizer_id' => $this->user->id,
            'sport' => 'tennis',
            'date' => now()->addDays(7)->format('Y-m-d'),
            'start_time' => '14:00',
            'end_time' => '16:00',
            'location' => 'Tennis Club',
        ]);

        // Ajouter une participation accepted
        SportSessionParticipantModel::create([
            'id' => $this->faker->uuid(),
            'session_id' => $session->id,
            'user_id' => $newUser->id,
            'status' => 'accepted',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/users/{$newUser->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $newUser->id,
                    'sports_preferences' => [], // Utilisateur sans sports préférés
                    'stats' => [
                        'sessionsCreated' => 0,
                        'sessionsParticipated' => 1 // Seule la participation 'accepted' doit être comptée
                    ]
                ]
            ]);
    }

    public function test_user_with_sports_preferences_returns_correct_data(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/users/{$this->otherUser->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->otherUser->id,
                    'firstname' => 'Marie',
                    'lastname' => 'Martin',
                    'email' => 'marie.martin@example.com',
                    'sports_preferences' => ['tennis', 'football', 'basketball'],
                ],
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'firstname',
                    'lastname',
                    'email',
                    'avatar',
                    'sports_preferences',
                    'stats' => [
                        'sessionsCreated',
                        'sessionsParticipated',
                    ],
                    'isAlreadyFriend',
                    'hasPendingRequest',
                    'relationshipStatus',
                ],
            ]);
    }

    public function test_user_without_sports_preferences_returns_empty_array(): void
    {
        // Créer un utilisateur sans sports préférés
        $userWithoutPreferences = UserModel::factory()->create([
            'firstname' => 'No',
            'lastname' => 'Preferences',
            'email' => 'no.preferences@example.com',
            'sports_preferences' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/users/{$userWithoutPreferences->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $userWithoutPreferences->id,
                    'firstname' => 'No',
                    'lastname' => 'Preferences',
                    'email' => 'no.preferences@example.com',
                    'sports_preferences' => [], // Tableau vide pour null
                ],
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'firstname',
                    'lastname',
                    'email',
                    'avatar',
                    'sports_preferences',
                    'stats' => [
                        'sessionsCreated',
                        'sessionsParticipated',
                    ],
                    'isAlreadyFriend',
                    'hasPendingRequest',
                    'relationshipStatus',
                ],
            ]);
    }

    public function test_user_with_maximum_sports_preferences(): void
    {
        // Créer un utilisateur avec le maximum de sports préférés (5)
        $userWithMaxPreferences = UserModel::factory()->create([
            'firstname' => 'Max',
            'lastname' => 'Sports',
            'email' => 'max.sports@example.com',
            'sports_preferences' => ['tennis', 'football', 'basketball', 'golf', 'musculation'],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/users/{$userWithMaxPreferences->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $userWithMaxPreferences->id,
                    'firstname' => 'Max',
                    'lastname' => 'Sports',
                    'email' => 'max.sports@example.com',
                    'sports_preferences' => ['tennis', 'football', 'basketball', 'golf', 'musculation'],
                ],
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'firstname',
                    'lastname',
                    'email',
                    'avatar',
                    'sports_preferences',
                    'stats' => [
                        'sessionsCreated',
                        'sessionsParticipated',
                    ],
                    'isAlreadyFriend',
                    'hasPendingRequest',
                    'relationshipStatus',
                ],
            ]);
    }
}
