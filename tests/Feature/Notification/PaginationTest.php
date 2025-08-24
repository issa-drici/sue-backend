<?php

namespace Tests\Feature\Notification;

use App\Models\NotificationModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private UserModel $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer un utilisateur et générer un token
        $this->user = UserModel::factory()->create();
        $response = $this->post('/api/login', [
            'email' => $this->user->email,
            'password' => 'password',
            'device_name' => 'test-device',
        ]);
        
        $this->token = $response->json('token');
    }

    public function test_pagination_returns_correct_metadata()
    {
        // Créer 47 notifications pour l'utilisateur
        for ($i = 0; $i < 47; $i++) {
            NotificationModel::factory()->create([
                'user_id' => $this->user->id,
                'type' => 'invitation',
                'title' => "Notification {$i}",
                'message' => "Message {$i}",
            ]);
        }

        // Tester la première page
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/notifications?page=1&limit=20');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'pagination' => [
                'page',
                'limit',
                'total',
                'totalPages',
            ],
        ]);

        $pagination = $response->json('pagination');
        
        // Vérifier les métadonnées de pagination
        $this->assertEquals(1, $pagination['page']);
        $this->assertEquals(20, $pagination['limit']);
        $this->assertEquals(47, $pagination['total']);
        $this->assertEquals(3, $pagination['totalPages']);
        
        // Vérifier le nombre d'éléments retournés
        $this->assertCount(20, $response->json('data'));
    }

    public function test_pagination_second_page()
    {
        // Créer 47 notifications pour l'utilisateur
        for ($i = 0; $i < 47; $i++) {
            NotificationModel::factory()->create([
                'user_id' => $this->user->id,
                'type' => 'invitation',
                'title' => "Notification {$i}",
                'message' => "Message {$i}",
            ]);
        }

        // Tester la deuxième page
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/notifications?page=2&limit=20');

        $response->assertStatus(200);
        
        $pagination = $response->json('pagination');
        
        // Vérifier les métadonnées de pagination
        $this->assertEquals(2, $pagination['page']);
        $this->assertEquals(20, $pagination['limit']);
        $this->assertEquals(47, $pagination['total']);
        $this->assertEquals(3, $pagination['totalPages']);
        
        // Vérifier le nombre d'éléments retournés (20 sur la page 2)
        $this->assertCount(20, $response->json('data'));
    }

    public function test_pagination_third_page()
    {
        // Créer 47 notifications pour l'utilisateur
        for ($i = 0; $i < 47; $i++) {
            NotificationModel::factory()->create([
                'user_id' => $this->user->id,
                'type' => 'invitation',
                'title' => "Notification {$i}",
                'message' => "Message {$i}",
            ]);
        }

        // Tester la troisième page
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/notifications?page=3&limit=20');

        $response->assertStatus(200);
        
        $pagination = $response->json('pagination');
        
        // Vérifier les métadonnées de pagination
        $this->assertEquals(3, $pagination['page']);
        $this->assertEquals(20, $pagination['limit']);
        $this->assertEquals(47, $pagination['total']);
        $this->assertEquals(3, $pagination['totalPages']);
        
        // Vérifier le nombre d'éléments retournés (7 sur la page 3)
        $this->assertCount(7, $response->json('data'));
    }

    public function test_pagination_with_different_limits()
    {
        // Créer 47 notifications pour l'utilisateur
        for ($i = 0; $i < 47; $i++) {
            NotificationModel::factory()->create([
                'user_id' => $this->user->id,
                'type' => 'invitation',
                'title' => "Notification {$i}",
                'message' => "Message {$i}",
            ]);
        }

        // Tester avec une limite de 10
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/notifications?page=1&limit=10');

        $response->assertStatus(200);
        
        $pagination = $response->json('pagination');
        
        // Vérifier les métadonnées de pagination
        $this->assertEquals(1, $pagination['page']);
        $this->assertEquals(10, $pagination['limit']);
        $this->assertEquals(47, $pagination['total']);
        $this->assertEquals(5, $pagination['totalPages']); // 47 / 10 = 5 pages
        
        // Vérifier le nombre d'éléments retournés
        $this->assertCount(10, $response->json('data'));
    }

    public function test_pagination_without_parameters()
    {
        // Créer 47 notifications pour l'utilisateur
        for ($i = 0; $i < 47; $i++) {
            NotificationModel::factory()->create([
                'user_id' => $this->user->id,
                'type' => 'invitation',
                'title' => "Notification {$i}",
                'message' => "Message {$i}",
            ]);
        }

        // Tester sans paramètres (valeurs par défaut)
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/notifications');

        $response->assertStatus(200);
        
        $pagination = $response->json('pagination');
        
        // Vérifier les métadonnées de pagination (valeurs par défaut)
        $this->assertEquals(1, $pagination['page']);
        $this->assertEquals(20, $pagination['limit']);
        $this->assertEquals(47, $pagination['total']);
        $this->assertEquals(3, $pagination['totalPages']);
        
        // Vérifier le nombre d'éléments retournés
        $this->assertCount(20, $response->json('data'));
    }

    public function test_pagination_response_structure()
    {
        // Créer quelques notifications
        for ($i = 0; $i < 5; $i++) {
            NotificationModel::factory()->create([
                'user_id' => $this->user->id,
                'type' => 'invitation',
                'title' => "Notification {$i}",
                'message' => "Message {$i}",
            ]);
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/notifications?page=1&limit=20');

        $response->assertStatus(200);
        
        // Vérifier la structure complète de la réponse
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'user_id',
                    'type',
                    'title',
                    'message',
                    'session_id',
                    'created_at',
                    'read',
                    'push_sent',
                    'push_sent_at',
                    'push_data',
                ],
            ],
            'pagination' => [
                'page',
                'limit',
                'total',
                'totalPages',
            ],
        ]);

        // Vérifier que success est true
        $this->assertTrue($response->json('success'));
    }
}
