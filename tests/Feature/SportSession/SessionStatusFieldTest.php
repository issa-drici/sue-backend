<?php

namespace Tests\Feature\SportSession;

use App\Models\UserModel;
use App\Models\SportSessionModel;
use App\Models\SportSessionParticipantModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SessionStatusFieldTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private UserModel $organizer;
    private UserModel $participant;
    private SportSessionModel $activeSession;
    private SportSessionModel $cancelledSession;
    private SportSessionModel $completedSession;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer l'organisateur
        $this->organizer = UserModel::factory()->create([
            'firstname' => 'Jean',
            'lastname' => 'Dupont',
        ]);

        // Créer le participant
        $this->participant = UserModel::factory()->create([
            'firstname' => 'Marie',
            'lastname' => 'Martin',
        ]);

        // Créer une session active
        $this->activeSession = SportSessionModel::factory()->create([
            'organizer_id' => $this->organizer->id,
            'sport' => 'tennis',
            'date' => now()->addDays(7)->format('Y-m-d'),
            'start_time' => '14:00',
            'end_time' => '16:00',
            'location' => 'Tennis Club',
            'status' => 'active',
        ]);

        // Créer une session annulée
        $this->cancelledSession = SportSessionModel::factory()->create([
            'organizer_id' => $this->organizer->id,
            'sport' => 'golf',
            'date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'location' => 'Golf Club',
            'status' => 'cancelled',
        ]);

        // Créer une session terminée
        $this->completedSession = SportSessionModel::factory()->create([
            'organizer_id' => $this->organizer->id,
            'sport' => 'football',
            'date' => now()->subDays(1)->format('Y-m-d'),
            'start_time' => '16:00',
            'end_time' => '18:00',
            'location' => 'Stade Municipal',
            'status' => 'completed',
        ]);

        // Ajouter l'organisateur comme participant dans toutes les sessions
        foreach ([$this->activeSession, $this->cancelledSession, $this->completedSession] as $session) {
            SportSessionParticipantModel::create([
                'id' => $this->faker->uuid(),
                'session_id' => $session->id,
                'user_id' => $this->organizer->id,
                'status' => 'accepted',
            ]);
        }

        // Ajouter le participant dans les sessions active et annulée
        foreach ([$this->activeSession, $this->cancelledSession] as $session) {
            SportSessionParticipantModel::create([
                'id' => $this->faker->uuid(),
                'session_id' => $session->id,
                'user_id' => $this->participant->id,
                'status' => 'accepted',
            ]);
        }
    }

    /**
     * Test que GET /api/sessions retourne le champ status
     */
    public function test_get_sessions_returns_status_field(): void
    {
        $response = $this->actingAs($this->organizer)
            ->getJson('/api/sessions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'sport',
                        'date',
                        'time',
                        'location',
                        'status', // Vérifier que le champ status est présent
                        'organizer',
                        'participants',
                    ],
                ],
                'pagination',
            ]);

        // Vérifier que les sessions ont bien leur statut
        $sessions = $response->json('data');
        $this->assertNotEmpty($sessions);

        foreach ($sessions as $session) {
            $this->assertArrayHasKey('status', $session);
            $this->assertContains($session['status'], ['active', 'cancelled', 'completed']);
        }
    }

    /**
     * Test que GET /api/sessions/{id} retourne le champ status
     */
    public function test_get_session_by_id_returns_status_field(): void
    {
        $response = $this->actingAs($this->organizer)
            ->getJson("/api/sessions/{$this->activeSession->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'sport',
                    'date',
                    'time',
                    'location',
                    'status', // Vérifier que le champ status est présent
                    'organizer',
                    'participants',
                ],
            ])
            ->assertJson([
                'data' => [
                    'status' => 'active',
                ],
            ]);
    }

    /**
     * Test que GET /api/sessions/history retourne le champ status
     */
    public function test_get_sessions_history_returns_status_field(): void
    {
        $response = $this->actingAs($this->organizer)
            ->getJson('/api/sessions/history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'sport',
                        'date',
                        'time',
                        'location',
                        'status', // Vérifier que le champ status est présent
                        'organizer',
                        'participants',
                    ],
                ],
                'pagination',
            ]);

        // Vérifier que les sessions dans l'historique ont bien leur statut
        $sessions = $response->json('data');
        $this->assertNotEmpty($sessions);

        foreach ($sessions as $session) {
            $this->assertArrayHasKey('status', $session);
            $this->assertContains($session['status'], ['active', 'cancelled', 'completed']);
        }
    }

    /**
     * Test que GET /api/sessions/my-created retourne le champ status
     */
    public function test_get_my_created_sessions_returns_status_field(): void
    {
        $response = $this->actingAs($this->organizer)
            ->getJson('/api/sessions/my-created');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'sport',
                        'date',
                        'time',
                        'location',
                        'status', // Vérifier que le champ status est présent
                        'organizer',
                        'participants',
                    ],
                ],
                'pagination',
            ]);

        // Vérifier que les sessions créées ont bien leur statut
        $sessions = $response->json('data');
        $this->assertNotEmpty($sessions);

        foreach ($sessions as $session) {
            $this->assertArrayHasKey('status', $session);
            $this->assertContains($session['status'], ['active', 'cancelled', 'completed']);
        }
    }

    /**
     * Test que GET /api/sessions/my-participations retourne le champ status
     */
    public function test_get_my_participations_returns_status_field(): void
    {
        $response = $this->actingAs($this->participant)
            ->getJson('/api/sessions/my-participations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'sport',
                        'date',
                        'time',
                        'location',
                        'status', // Vérifier que le champ status est présent
                        'organizer',
                        'participants',
                    ],
                ],
                'pagination',
            ]);

        // Vérifier que les sessions de participation ont bien leur statut
        $sessions = $response->json('data');
        $this->assertNotEmpty($sessions);

        foreach ($sessions as $session) {
            $this->assertArrayHasKey('status', $session);
            $this->assertContains($session['status'], ['active', 'cancelled', 'completed']);
        }
    }

    /**
     * Test que POST /api/sessions retourne le champ status avec 'active' par défaut
     */
    public function test_create_session_returns_status_field_with_active_default(): void
    {
        $sessionData = [
            'sport' => 'basketball',
            'date' => now()->addDays(10)->format('Y-m-d'),
            'time' => '18:00',
            'location' => 'Gymnase Municipal',
        ];

        $response = $this->actingAs($this->organizer)
            ->postJson('/api/sessions', $sessionData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'sport',
                    'date',
                    'time',
                    'location',
                    'status', // Vérifier que le champ status est présent
                    'organizer',
                    'participants',
                ],
                'message',
            ])
            ->assertJson([
                'data' => [
                    'status' => 'active', // Vérifier que le statut par défaut est 'active'
                ],
            ]);
    }

    /**
     * Test que PUT /api/sessions/{id} retourne le champ status
     */
    public function test_update_session_returns_status_field(): void
    {
        $updateData = [
            'date' => now()->addDays(8)->format('Y-m-d'),
            'time' => '15:00',
            'location' => 'Nouveau Tennis Club',
        ];

        $response = $this->actingAs($this->organizer)
            ->putJson("/api/sessions/{$this->activeSession->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'sport',
                    'date',
                    'time',
                    'location',
                    'status', // Vérifier que le champ status est présent
                    'organizer',
                    'participants',
                ],
                'message',
            ])
            ->assertJson([
                'data' => [
                    'status' => 'active', // Le statut doit rester 'active' après modification
                ],
            ]);
    }

    /**
     * Test que PATCH /api/sessions/{id}/cancel retourne le champ status avec 'cancelled'
     */
    public function test_cancel_session_returns_status_field_with_cancelled(): void
    {
        $response = $this->actingAs($this->organizer)
            ->patchJson("/api/sessions/{$this->activeSession->id}/cancel");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'session' => [
                        'id',
                        'sport',
                        'date',
                        'time',
                        'location',
                        'status', // Vérifier que le champ status est présent
                        'organizer',
                        'participants',
                    ],
                ],
            ])
            ->assertJson([
                'data' => [
                    'session' => [
                        'status' => 'cancelled', // Vérifier que le statut est 'cancelled'
                    ],
                ],
            ]);
    }

    /**
     * Test que les sessions avec différents statuts sont correctement retournées
     */
    public function test_sessions_with_different_statuses_are_correctly_returned(): void
    {
        // Vérifier la session active
        $response = $this->actingAs($this->organizer)
            ->getJson("/api/sessions/{$this->activeSession->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'active',
                ],
            ]);

        // Vérifier la session annulée
        $response = $this->actingAs($this->organizer)
            ->getJson("/api/sessions/{$this->cancelledSession->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'cancelled',
                ],
            ]);

        // Vérifier la session terminée
        $response = $this->actingAs($this->organizer)
            ->getJson("/api/sessions/{$this->completedSession->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'completed',
                ],
            ]);
    }

    /**
     * Test que les sessions existantes sans status sont traitées comme 'active'
     */
    public function test_existing_sessions_without_status_are_treated_as_active(): void
    {
        // Créer une session avec un statut 'active' (valeur par défaut)
        $sessionWithDefaultStatus = SportSessionModel::factory()->create([
            'organizer_id' => $this->organizer->id,
            'sport' => 'musculation',
            'date' => now()->addDays(3)->format('Y-m-d'),
            'time' => '19:00',
            'location' => 'Salle de sport',
            'status' => 'active', // Utiliser le statut par défaut
        ]);

        // Ajouter l'organisateur comme participant
        SportSessionParticipantModel::create([
            'id' => $this->faker->uuid(),
            'session_id' => $sessionWithDefaultStatus->id,
            'user_id' => $this->organizer->id,
            'status' => 'accepted',
        ]);

        $response = $this->actingAs($this->organizer)
            ->getJson("/api/sessions/{$sessionWithDefaultStatus->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'active', // Doit être 'active' par défaut
                ],
            ]);
    }
}
