<?php

namespace Tests\Feature\User;

use App\Models\UserModel;
use App\Models\FriendModel;
use App\Models\FriendRequestModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SearchUsersTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private UserModel $currentUser;
    private UserModel $user1;
    private UserModel $user2;
    private UserModel $user3;
    private UserModel $user4;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer l'utilisateur connecté
        $this->currentUser = UserModel::factory()->create([
            'firstname' => 'Current',
            'lastname' => 'User',
            'email' => 'current.user@example.com',
        ]);

        // Créer des utilisateurs de test avec différents noms
        $this->user1 = UserModel::factory()->create([
            'firstname' => 'Jean',
            'lastname' => 'Dupont',
            'email' => 'jean.dupont@example.com',
        ]);

        $this->user2 = UserModel::factory()->create([
            'firstname' => 'Marie',
            'lastname' => 'Dupont',
            'email' => 'marie.dupont@example.com',
        ]);

        $this->user3 = UserModel::factory()->create([
            'firstname' => 'Jean',
            'lastname' => 'Martin',
            'email' => 'jean.martin@example.com',
        ]);

        $this->user4 = UserModel::factory()->create([
            'firstname' => 'Pierre',
            'lastname' => 'Durand',
            'email' => 'pierre.durand@example.com',
        ]);

        // Créer des relations d'amitié pour les tests
        $this->createTestRelationships();
    }

    private function createTestRelationships(): void
    {
        // user1 est ami avec currentUser
        FriendModel::create([
            'id' => $this->faker->uuid(),
            'user_id' => $this->currentUser->id,
            'friend_id' => $this->user1->id,
        ]);
        FriendModel::create([
            'id' => $this->faker->uuid(),
            'user_id' => $this->user1->id,
            'friend_id' => $this->currentUser->id,
        ]);

        // user2 a une demande d'ami en attente (envoyée par currentUser)
        FriendRequestModel::create([
            'id' => $this->faker->uuid(),
            'sender_id' => $this->currentUser->id,
            'receiver_id' => $this->user2->id,
            'status' => 'pending',
        ]);

        // user3 a une demande d'ami en attente (reçue par currentUser)
        FriendRequestModel::create([
            'id' => $this->faker->uuid(),
            'sender_id' => $this->user3->id,
            'receiver_id' => $this->currentUser->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Test recherche par prénom uniquement
     */
    public function test_can_search_by_firstname_only(): void
    {
        $response = $this->actingAs($this->currentUser)
            ->getJson('/api/users/search?q=Jean');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'firstname',
                        'lastname',
                        'email',
                        'relationship' => [
                            'status',
                            'isFriend',
                            'hasPendingRequest',
                            'mutualFriends'
                        ]
                    ]
                ],
                'pagination'
            ])
            ->assertJson([
                'success' => true
            ]);

        $data = $response->json('data');
        $this->assertCount(2, $data); // Jean Dupont et Jean Martin

        // Vérifier que les résultats contiennent bien les utilisateurs attendus
        $userIds = collect($data)->pluck('id')->toArray();
        $this->assertContains($this->user1->id, $userIds);
        $this->assertContains($this->user3->id, $userIds);
        $this->assertNotContains($this->user2->id, $userIds);
        $this->assertNotContains($this->user4->id, $userIds);
    }

    /**
     * Test recherche par nom uniquement
     */
    public function test_can_search_by_lastname_only(): void
    {
        $response = $this->actingAs($this->currentUser)
            ->getJson('/api/users/search?q=Dupont');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertCount(2, $data); // Jean Dupont et Marie Dupont

        $userIds = collect($data)->pluck('id')->toArray();
        $this->assertContains($this->user1->id, $userIds);
        $this->assertContains($this->user2->id, $userIds);
        $this->assertNotContains($this->user3->id, $userIds);
        $this->assertNotContains($this->user4->id, $userIds);
    }

    /**
     * Test recherche par prénom + nom (ordre normal)
     */
    public function test_can_search_by_firstname_and_lastname_normal_order(): void
    {
        $response = $this->actingAs($this->currentUser)
            ->getJson('/api/users/search?q=Jean Dupont');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertCount(1, $data); // Seulement Jean Dupont

        $this->assertEquals($this->user1->id, $data[0]['id']);
        $this->assertEquals('Jean', $data[0]['firstname']);
        $this->assertEquals('Dupont', $data[0]['lastname']);
    }

    /**
     * Test recherche par nom + prénom (ordre inversé)
     */
    public function test_can_search_by_lastname_and_firstname_reverse_order(): void
    {
        $response = $this->actingAs($this->currentUser)
            ->getJson('/api/users/search?q=Dupont Jean');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertCount(1, $data); // Seulement Jean Dupont

        $this->assertEquals($this->user1->id, $data[0]['id']);
        $this->assertEquals('Jean', $data[0]['firstname']);
        $this->assertEquals('Dupont', $data[0]['lastname']);
    }

    /**
     * Test recherche partielle (prénom + début de nom)
     */
    public function test_can_search_by_partial_name(): void
    {
        $response = $this->actingAs($this->currentUser)
            ->getJson('/api/users/search?q=Jean D');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertCount(1, $data); // Seulement Jean Dupont

        $this->assertEquals($this->user1->id, $data[0]['id']);
    }

    /**
     * Test recherche par email
     */
    public function test_can_search_by_email(): void
    {
        $response = $this->actingAs($this->currentUser)
            ->getJson('/api/users/search?q=jean.dupont@example.com');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertCount(1, $data);

        $this->assertEquals($this->user1->id, $data[0]['id']);
        $this->assertEquals('jean.dupont@example.com', $data[0]['email']);
    }

    /**
     * Test que l'utilisateur connecté n'apparaît pas dans les résultats
     */
    public function test_current_user_not_in_search_results(): void
    {
        $response = $this->actingAs($this->currentUser)
            ->getJson('/api/users/search?q=Current');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertCount(0, $data); // Aucun résultat car l'utilisateur connecté est exclu
    }

    /**
     * Test des informations de relation dans les résultats
     */
    public function test_search_results_include_relationship_info(): void
    {
        $response = $this->actingAs($this->currentUser)
            ->getJson('/api/users/search?q=Jean Dupont');

        $response->assertStatus(200);

        $data = $response->json('data');
        $user = $data[0];

        // Vérifier les informations de relation
        $this->assertArrayHasKey('relationship', $user);
        $this->assertEquals('accepted', $user['relationship']['status']); // user1 est ami
        $this->assertTrue($user['relationship']['isFriend']);
        $this->assertFalse($user['relationship']['hasPendingRequest']);
    }

    /**
     * Test des informations de relation pour une demande en attente
     */
    public function test_search_results_include_pending_request_info(): void
    {
        $response = $this->actingAs($this->currentUser)
            ->getJson('/api/users/search?q=Marie Dupont');

        $response->assertStatus(200);

        $data = $response->json('data');
        $user = $data[0];

        // Vérifier les informations de relation
        $this->assertArrayHasKey('relationship', $user);
        $this->assertEquals('pending', $user['relationship']['status']); // user2 a une demande en attente
        $this->assertFalse($user['relationship']['isFriend']);
        $this->assertTrue($user['relationship']['hasPendingRequest']);
    }

    /**
     * Test recherche avec requête vide
     */
    public function test_returns_error_for_empty_query(): void
    {
        $response = $this->actingAs($this->currentUser)
            ->getJson('/api/users/search?q=');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Le paramètre q est requis et doit contenir au moins 2 caractères valides'
                ]
            ]);
    }

    /**
     * Test recherche avec requête trop courte
     */
    public function test_returns_error_for_short_query(): void
    {
        $response = $this->actingAs($this->currentUser)
            ->getJson('/api/users/search?q=a');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Le paramètre q est requis et doit contenir au moins 2 caractères valides'
                ]
            ]);
    }

    /**
     * Test recherche sans authentification
     */
    public function test_returns_401_without_authentication(): void
    {
        $response = $this->getJson('/api/users/search?q=Jean');

        $response->assertStatus(401);
    }

    /**
     * Test pagination
     */
    public function test_pagination_works(): void
    {
        $response = $this->actingAs($this->currentUser)
            ->getJson('/api/users/search?q=Jean&limit=1&page=1');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'pagination' => [
                    'page',
                    'limit',
                    'total',
                    'totalPages'
                ]
            ]);

        $pagination = $response->json('pagination');
        $this->assertEquals(1, $pagination['page']);
        $this->assertEquals(1, $pagination['limit']);
        $this->assertEquals(2, $pagination['total']); // 2 utilisateurs avec "Jean"
        $this->assertEquals(2, $pagination['totalPages']);
    }

    /**
     * Test recherche insensible à la casse
     */
    public function test_search_is_case_insensitive(): void
    {
        $response = $this->actingAs($this->currentUser)
            ->getJson('/api/users/search?q=JEAN');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertCount(2, $data); // Jean Dupont et Jean Martin
    }

    /**
     * Test recherche avec caractères spéciaux nettoyés
     */
    public function test_search_cleans_special_characters(): void
    {
        $response = $this->actingAs($this->currentUser)
            ->getJson('/api/users/search?q=Jean#');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        // Après nettoyage, "Jean#" devient "Jean" (caractères spéciaux supprimés)
        // Donc la recherche trouve tous les utilisateurs avec prénom "Jean"
        $this->assertCount(2, $data); // Jean Dupont et Jean Martin
        
        // Vérifier que les résultats contiennent bien des utilisateurs avec "Jean"
        $userIds = collect($data)->pluck('id')->toArray();
        $this->assertContains($this->user1->id, $userIds); // Jean Dupont
        $this->assertContains($this->user3->id, $userIds); // Jean Martin
    }
}
