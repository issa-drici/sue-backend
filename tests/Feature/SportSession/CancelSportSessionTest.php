<?php

namespace Tests\Feature\SportSession;

use App\Models\UserModel;
use App\Models\SportSessionModel;
use App\Models\SportSessionParticipantModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CancelSportSessionTest extends TestCase
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
            'status' => 'active',
        ]);

        // Ajouter l'organisateur comme participant (comme dans la logique réelle)
        SportSessionParticipantModel::create([
            'id' => $this->faker->uuid(),
            'session_id' => $this->session->id,
            'user_id' => $this->organizer->id,
            'status' => 'accepted',
        ]);

        // Ajouter le participant avec le statut 'accepted'
        SportSessionParticipantModel::create([
            'id' => $this->faker->uuid(),
            'session_id' => $this->session->id,
            'user_id' => $this->participant->id,
            'status' => 'accepted',
        ]);
    }

    public function test_organizer_can_cancel_session_successfully(): void
    {
        $response = $this->actingAs($this->organizer)
            ->patchJson("/api/sessions/{$this->session->id}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Session annulée avec succès',
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
                        'status',
                        'organizer',
                        'participants',
                    ],
                ],
            ]);

        // Vérifier que le statut de la session a été mis à jour
        $this->session->refresh();
        $this->assertEquals('cancelled', $this->session->status);
    }

    public function test_non_organizer_cannot_cancel_session(): void
    {
        $response = $this->actingAs($this->participant)
            ->patchJson("/api/sessions/{$this->session->id}/cancel");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à annuler cette session',
                'error' => 'UNAUTHORIZED',
            ]);
    }

    public function test_cannot_cancel_already_cancelled_session(): void
    {
        // Annuler d'abord la session
        $this->session->update(['status' => 'cancelled']);

        $response = $this->actingAs($this->organizer)
            ->patchJson("/api/sessions/{$this->session->id}/cancel");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cette session est déjà annulée',
                'error' => 'SESSION_ALREADY_CANCELLED',
            ]);
    }

    public function test_cannot_cancel_ended_session(): void
    {
        // Créer une session passée
        $pastSession = SportSessionModel::factory()->create([
            'organizer_id' => $this->organizer->id,
            'sport' => 'tennis',
            'date' => now()->subDays(1)->format('Y-m-d'),
            'time' => '14:00',
            'location' => 'Tennis Club',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->organizer)
            ->patchJson("/api/sessions/{$pastSession->id}/cancel");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Impossible d\'annuler une session terminée',
                'error' => 'SESSION_ENDED',
            ]);
    }

    public function test_cannot_cancel_nonexistent_session(): void
    {
        $nonExistentSessionId = $this->faker->uuid();

        $response = $this->actingAs($this->organizer)
            ->patchJson("/api/sessions/{$nonExistentSessionId}/cancel");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Session non trouvée',
                'error' => 'SESSION_NOT_FOUND',
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

        $response = $this->actingAs($this->organizer)
            ->patchJson("/api/sessions/{$this->session->id}/cancel");

        $response->assertStatus(200);

        // Vérifier que les notifications ont été créées pour les participants
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->participant->id,
            'type' => 'session_cancelled',
            'title' => 'Session annulée',
            'session_id' => $this->session->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $anotherParticipant->id,
            'type' => 'session_cancelled',
            'title' => 'Session annulée',
            'session_id' => $this->session->id,
        ]);

        // Vérifier que l'organisateur n'a pas reçu de notification
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->organizer->id,
            'type' => 'session_cancelled',
            'session_id' => $this->session->id,
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

        $response = $this->actingAs($this->organizer)
            ->patchJson("/api/sessions/{$this->session->id}/cancel");

        $response->assertStatus(200);

        // Vérifier que le participant en attente n'a pas reçu de notification
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $pendingParticipant->id,
            'type' => 'session_cancelled',
            'session_id' => $this->session->id,
        ]);
    }

    public function test_cancelled_session_does_not_appear_in_sessions_list(): void
    {
        // Annuler d'abord la session
        $response = $this->actingAs($this->organizer)
            ->patchJson("/api/sessions/{$this->session->id}/cancel");

        $response->assertStatus(200);

        // Vérifier que la session annulée n'apparaît pas dans la liste des sessions
        $listResponse = $this->actingAs($this->organizer)
            ->getJson('/api/sessions');

        $listResponse->assertStatus(200);

        $sessions = $listResponse->json('data.data');
        $sessionIds = collect($sessions)->pluck('id')->toArray();

        $this->assertNotContains($this->session->id, $sessionIds);
    }

    public function test_cancelled_session_does_not_appear_in_my_sessions(): void
    {
        // Annuler d'abord la session
        $response = $this->actingAs($this->organizer)
            ->patchJson("/api/sessions/{$this->session->id}/cancel");

        $response->assertStatus(200);

        // Vérifier que la session annulée n'apparaît pas dans "mes sessions"
        $listResponse = $this->actingAs($this->organizer)
            ->getJson('/api/sessions/my-created');

        $listResponse->assertStatus(200);

        $sessions = $listResponse->json('data.data');
        $sessionIds = collect($sessions)->pluck('id')->toArray();

        $this->assertNotContains($this->session->id, $sessionIds);
    }

    public function test_cancelled_session_appears_in_history(): void
    {
        // Annuler la session (qui est dans le futur)
        $response = $this->actingAs($this->organizer)
            ->patchJson("/api/sessions/{$this->session->id}/cancel");

        $response->assertStatus(200);

        // Vérifier que la session annulée apparaît dans l'historique
        $historyResponse = $this->actingAs($this->organizer)
            ->getJson('/api/sessions/history');

        $historyResponse->assertStatus(200);

        $sessions = $historyResponse->json('data');
        $sessionIds = collect($sessions)->pluck('id')->toArray();

        $this->assertContains((string) $this->session->id, $sessionIds);
    }

    public function test_cancelled_session_appears_in_history_for_participant(): void
    {
        // Annuler la session (qui est dans le futur)
        $response = $this->actingAs($this->organizer)
            ->patchJson("/api/sessions/{$this->session->id}/cancel");

        $response->assertStatus(200);

        // Vérifier que la session annulée apparaît dans l'historique du participant
        $historyResponse = $this->actingAs($this->participant)
            ->getJson('/api/sessions/history');

        $historyResponse->assertStatus(200);

        $sessions = $historyResponse->json('data');
        $sessionIds = collect($sessions)->pluck('id')->toArray();

        $this->assertContains((string) $this->session->id, $sessionIds);
    }
}
