<?php

namespace Tests\Feature\SportSession;

use App\Models\UserModel;
use App\Models\SportSessionModel;
use App\Models\SportSessionParticipantModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UpdateSportSessionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private UserModel $organizer;
    private UserModel $participant;
    private SportSessionModel $session;

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

        // Créer une session
        $this->session = SportSessionModel::factory()->create([
            'organizer_id' => $this->organizer->id,
            'sport' => 'tennis',
            'date' => now()->addDays(7)->format('Y-m-d'),
            'start_time' => '14:00',
            'end_time' => '16:00',
            'location' => 'Tennis Club',
            'max_participants' => 4,
            'status' => 'active',
        ]);

        // Ajouter le participant avec le statut 'accepted'
        SportSessionParticipantModel::create([
            'id' => $this->faker->uuid(),
            'session_id' => $this->session->id,
            'user_id' => $this->participant->id,
            'status' => 'accepted',
        ]);
    }

    public function test_organizer_can_update_session_successfully(): void
    {
        $updateData = [
            'date' => now()->addDays(10)->format('Y-m-d'),
            'startTime' => '16:00',
            'endTime' => '18:00',
            'location' => 'Nouveau Tennis Club',
        ];

        $response = $this->actingAs($this->organizer)
            ->putJson("/api/sessions/{$this->session->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Session mise à jour avec succès',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'sport',
                    'date',
                    'startTime',
                    'endTime',
                    'location',
                    'maxParticipants',
                    'status',
                    'organizer',
                    'participants',
                ],
            ]);

        // Vérifier que les données ont été mises à jour
        $this->session->refresh();
        $this->assertEquals($updateData['date'], $this->session->date->format('Y-m-d'));
        $this->assertEquals($updateData['startTime'], substr($this->session->start_time, 0, 5)); // Enlever les secondes
        $this->assertEquals($updateData['location'], $this->session->location);
    }

    public function test_non_organizer_cannot_update_session(): void
    {
        $updateData = [
            'date' => now()->addDays(10)->format('Y-m-d'),
            'startTime' => '16:00',
            'endTime' => '18:00',
        ];

        $response = $this->actingAs($this->participant)
            ->putJson("/api/sessions/{$this->session->id}", $updateData);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'Vous n\'êtes pas autorisé à modifier cette session',
                ],
            ]);
    }

    public function test_cannot_update_session_with_invalid_date(): void
    {
        $updateData = [
            'date' => now()->subDays(1)->format('Y-m-d'), // Date dans le passé
        ];

        $response = $this->actingAs($this->organizer)
            ->putJson("/api/sessions/{$this->session->id}", $updateData);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Données invalides',
                ],
            ]);
    }

    public function test_cannot_update_session_with_invalid_time(): void
    {
        $updateData = [
            'startTime' => '25:00', // Heure invalide
        ];

        $response = $this->actingAs($this->organizer)
            ->putJson("/api/sessions/{$this->session->id}", $updateData);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Données invalides',
                ],
            ]);
    }



    public function test_cannot_update_session_with_too_long_location(): void
    {
        $updateData = [
            'location' => str_repeat('a', 201), // Plus de 200 caractères
        ];

        $response = $this->actingAs($this->organizer)
            ->putJson("/api/sessions/{$this->session->id}", $updateData);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Données invalides',
                ],
            ]);
    }

    public function test_cannot_update_ended_session(): void
    {
        // Créer une session passée
        $pastSession = SportSessionModel::factory()->create([
            'organizer_id' => $this->organizer->id,
            'sport' => 'tennis',
            'date' => now()->subDays(1)->format('Y-m-d'),
            'start_time' => '14:00',
            'end_time' => '16:00',
            'location' => 'Tennis Club',
            'status' => 'active',
        ]);

        $updateData = [
            'startTime' => '16:00',
            'endTime' => '18:00',
        ];

        $response = $this->actingAs($this->organizer)
            ->putJson("/api/sessions/{$pastSession->id}", $updateData);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Impossible de modifier une session passée',
                ],
            ]);
    }

    public function test_notifications_are_created_for_participants(): void
    {
        // Créer un autre participant accepté
        $anotherParticipant = UserModel::factory()->create([
            'firstname' => 'Pierre',
            'lastname' => 'Durand',
        ]);

        SportSessionParticipantModel::create([
            'id' => $this->faker->uuid(),
            'session_id' => $this->session->id,
            'user_id' => $anotherParticipant->id,
            'status' => 'accepted',
        ]);

        $updateData = [
            'startTime' => '16:00',
            'endTime' => '18:00',
            'location' => 'Nouveau Tennis Club',
        ];

        $response = $this->actingAs($this->organizer)
            ->putJson("/api/sessions/{$this->session->id}", $updateData);

        $response->assertStatus(200);

        // Vérifier que les notifications ont été créées pour les participants
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->participant->id,
            'type' => 'session_update',
            'title' => 'Session modifiée',
            'session_id' => $this->session->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $anotherParticipant->id,
            'type' => 'session_update',
            'title' => 'Session modifiée',
            'session_id' => $this->session->id,
        ]);

        // Vérifier que l'organisateur n'a pas reçu de notification
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->organizer->id,
            'type' => 'session_update',
            'session_id' => $this->session->id,
        ]);
    }

    public function test_can_update_max_participants_to_null(): void
    {
        // Créer une session avec maxParticipants défini
        $sessionWithMaxParticipants = SportSessionModel::factory()->create([
            'organizer_id' => $this->organizer->id,
            'sport' => 'tennis',
            'date' => now()->addDays(7)->format('Y-m-d'),
            'start_time' => '14:00',
            'end_time' => '16:00',
            'location' => 'Tennis Club',
            'max_participants' => 4,
            'price_per_person' => 10.50,
            'status' => 'active',
        ]);

        $updateData = [
            'maxParticipants' => null,
        ];

        $response = $this->actingAs($this->organizer)
            ->putJson("/api/sessions/{$sessionWithMaxParticipants->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Session mise à jour avec succès',
            ]);

        // Vérifier que maxParticipants est bien null en base
        $sessionWithMaxParticipants->refresh();
        $this->assertNull($sessionWithMaxParticipants->max_participants);
    }

    public function test_can_update_price_per_person_to_null(): void
    {
        // Créer une session avec pricePerPerson défini
        $sessionWithPrice = SportSessionModel::factory()->create([
            'organizer_id' => $this->organizer->id,
            'sport' => 'tennis',
            'date' => now()->addDays(7)->format('Y-m-d'),
            'start_time' => '14:00',
            'end_time' => '16:00',
            'location' => 'Tennis Club',
            'max_participants' => 4,
            'price_per_person' => 10.50,
            'status' => 'active',
        ]);

        $updateData = [
            'pricePerPerson' => null,
        ];

        $response = $this->actingAs($this->organizer)
            ->putJson("/api/sessions/{$sessionWithPrice->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Session mise à jour avec succès',
            ]);

        // Vérifier que pricePerPerson est bien null en base
        $sessionWithPrice->refresh();
        $this->assertNull($sessionWithPrice->price_per_person);
    }

    public function test_can_update_both_max_participants_and_price_to_null(): void
    {
        // Créer une session avec maxParticipants et pricePerPerson définis
        $sessionWithBoth = SportSessionModel::factory()->create([
            'organizer_id' => $this->organizer->id,
            'sport' => 'tennis',
            'date' => now()->addDays(7)->format('Y-m-d'),
            'start_time' => '14:00',
            'end_time' => '16:00',
            'location' => 'Tennis Club',
            'max_participants' => 4,
            'price_per_person' => 10.50,
            'status' => 'active',
        ]);

        $updateData = [
            'maxParticipants' => null,
            'pricePerPerson' => null,
        ];

        $response = $this->actingAs($this->organizer)
            ->putJson("/api/sessions/{$sessionWithBoth->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Session mise à jour avec succès',
            ]);

        // Vérifier que les deux champs sont bien null en base
        $sessionWithBoth->refresh();
        $this->assertNull($sessionWithBoth->max_participants);
        $this->assertNull($sessionWithBoth->price_per_person);
    }

    public function test_cannot_update_max_participants_to_invalid_value(): void
    {
        $updateData = [
            'maxParticipants' => 0, // Valeur invalide
        ];

        $response = $this->actingAs($this->organizer)
            ->putJson("/api/sessions/{$this->session->id}", $updateData);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Données invalides',
                ],
            ]);
    }

    public function test_cannot_update_price_per_person_to_negative_value(): void
    {
        $updateData = [
            'pricePerPerson' => -5.0, // Valeur négative
        ];

        $response = $this->actingAs($this->organizer)
            ->putJson("/api/sessions/{$this->session->id}", $updateData);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Données invalides',
                ],
            ]);
    }

    public function test_pending_participants_do_not_receive_notifications(): void
    {
        // Créer un participant en attente
        $pendingParticipant = UserModel::factory()->create([
            'firstname' => 'Sophie',
            'lastname' => 'Bernard',
        ]);

        SportSessionParticipantModel::create([
            'id' => $this->faker->uuid(),
            'session_id' => $this->session->id,
            'user_id' => $pendingParticipant->id,
            'status' => 'pending',
        ]);

        $updateData = [
            'time' => '16:00',
        ];

        $response = $this->actingAs($this->organizer)
            ->putJson("/api/sessions/{$this->session->id}", $updateData);

        $response->assertStatus(200);

        // Vérifier que le participant en attente n'a pas reçu de notification
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $pendingParticipant->id,
            'type' => 'session_update',
            'session_id' => $this->session->id,
        ]);
    }
}
