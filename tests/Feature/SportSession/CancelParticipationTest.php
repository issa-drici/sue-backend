<?php

namespace Tests\Feature\SportSession;

use App\Models\UserModel;
use App\Models\SportSessionModel;
use App\Models\SportSessionParticipantModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CancelParticipationTest extends TestCase
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
            'time' => '14:00',
            'location' => 'Tennis Club',
        ]);

        // Ajouter le participant avec le statut 'accepted'
        SportSessionParticipantModel::create([
            'id' => $this->faker->uuid(),
            'session_id' => $this->session->id,
            'user_id' => $this->participant->id,
            'status' => 'accepted',
        ]);
    }

    public function test_user_can_cancel_participation_successfully(): void
    {
        $response = $this->actingAs($this->participant)
            ->patchJson("/api/sessions/{$this->session->id}/cancel-participation");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Participation annulée avec succès',
            ])
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
                        'participants',
                    ],
                ],
            ]);

        // Vérifier que le statut du participant a été mis à jour
        $this->assertDatabaseHas('sport_session_participants', [
            'session_id' => $this->session->id,
            'user_id' => $this->participant->id,
            'status' => 'declined',
        ]);
    }

    public function test_organizer_cannot_cancel_participation(): void
    {
        $response = $this->actingAs($this->organizer)
            ->patchJson("/api/sessions/{$this->session->id}/cancel-participation");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à annuler votre participation à cette session',
                'error' => 'UNAUTHORIZED',
            ]);
    }

    public function test_user_cannot_cancel_participation_if_not_accepted(): void
    {
        // Créer un autre utilisateur avec le statut 'pending'
        /** @var UserModel $pendingUser */
        $pendingUser = UserModel::factory()->create();
        SportSessionParticipantModel::create([
            'id' => $this->faker->uuid(),
            'session_id' => $this->session->id,
            'user_id' => $pendingUser->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($pendingUser)
            ->patchJson("/api/sessions/{$this->session->id}/cancel-participation");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Vous n\'avez pas accepté l\'invitation à cette session',
                'error' => 'USER_NOT_ACCEPTED',
            ]);
    }

    public function test_user_cannot_cancel_participation_if_not_participant(): void
    {
        // Créer un utilisateur qui n'est pas participant
        /** @var UserModel $nonParticipant */
        $nonParticipant = UserModel::factory()->create();

        $response = $this->actingAs($nonParticipant)
            ->patchJson("/api/sessions/{$this->session->id}/cancel-participation");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à annuler votre participation à cette session',
                'error' => 'UNAUTHORIZED',
            ]);
    }

    public function test_cannot_cancel_participation_for_ended_session(): void
    {
        // Créer une session passée
        $pastSession = SportSessionModel::factory()->create([
            'organizer_id' => $this->organizer->id,
            'sport' => 'tennis',
            'date' => now()->subDays(1)->format('Y-m-d'),
            'time' => '14:00',
            'location' => 'Tennis Club',
        ]);

        // Ajouter le participant
        SportSessionParticipantModel::create([
            'id' => $this->faker->uuid(),
            'session_id' => $pastSession->id,
            'user_id' => $this->participant->id,
            'status' => 'accepted',
        ]);

        $response = $this->actingAs($this->participant)
            ->patchJson("/api/sessions/{$pastSession->id}/cancel-participation");

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Impossible d\'annuler la participation à une session terminée',
                'error' => 'SESSION_ENDED',
            ]);
    }

    public function test_cannot_cancel_participation_for_nonexistent_session(): void
    {
        $nonExistentSessionId = $this->faker->uuid();

        $response = $this->actingAs($this->participant)
            ->patchJson("/api/sessions/{$nonExistentSessionId}/cancel-participation");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Session non trouvée',
                'error' => 'SESSION_NOT_FOUND',
            ]);
    }

    public function test_notifications_are_created_for_all_active_participants(): void
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

        $response = $this->actingAs($this->participant)
            ->patchJson("/api/sessions/{$this->session->id}/cancel-participation");

        $response->assertStatus(200);

        // Vérifier qu'une notification a été créée pour l'organisateur
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->organizer->id,
            'type' => 'update',
            'title' => 'Participation annulée',
            'session_id' => $this->session->id,
        ]);

        // Vérifier qu'une notification a été créée pour l'autre participant
        $this->assertDatabaseHas('notifications', [
            'user_id' => $anotherParticipant->id,
            'type' => 'update',
            'title' => 'Participation annulée',
            'session_id' => $this->session->id,
        ]);

        // Vérifier qu'aucune notification n'a été créée pour le participant qui annule
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->participant->id,
            'type' => 'update',
            'title' => 'Participation annulée',
            'session_id' => $this->session->id,
        ]);
    }
}
