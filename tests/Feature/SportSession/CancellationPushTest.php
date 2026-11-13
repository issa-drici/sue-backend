<?php

namespace Tests\Feature\SportSession;

use App\Models\NotificationModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SueTestHelpers;
use Tests\TestCase;

/**
 * Pushs envoyés lors d'une annulation de session ou d'un désistement (les suites historiques
 * ne vérifient que les notifications in-app).
 */
class CancellationPushTest extends TestCase
{
    use RefreshDatabase, SueTestHelpers;

    public function test_desistement_pousse_aux_confirmes_et_libere_une_place_aux_invites(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $dina = $this->makeUser('Dina');
        $zoe = $this->makeUser('Zoe');
        $session = $this->makeSession($orga, [[$paul, 'accepted'], [$dina, 'pending'], [$zoe, 'declined']], ['max_participants' => 2]);

        $this->actingAs($paul)->patchJson("/api/sessions/{$session->id}/cancel-participation")->assertOk();

        $this->assertSame('Paul ne vient plus', $this->pushesFor($orga)[0]['title']);
        $this->assertSame('Une place vient de se libérer', $this->pushesFor($dina)[0]['title']);
        $this->assertSame([], $this->pushesFor($zoe));
        $this->assertSame([], $this->pushesFor($paul));
    }

    public function test_annulation_cree_les_notifications_in_app(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $session = $this->makeSession($orga, [[$paul, 'accepted']]);

        $this->actingAs($orga)->patchJson("/api/sessions/{$session->id}/cancel")->assertOk();
        $this->assertSame(1, NotificationModel::where('user_id', $paul->id)->where('type', 'session_cancelled')->count());
        $this->assertSame(0, NotificationModel::where('user_id', $orga->id)->count());
    }

    public function test_annulation_pousse_aux_participants_confirmes(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $dina = $this->makeUser('Dina');
        $session = $this->makeSession($orga, [[$paul, 'accepted'], [$dina, 'pending']]);

        $this->actingAs($orga)->patchJson("/api/sessions/{$session->id}/cancel")->assertOk();

        $this->assertCount(1, $this->pushesFor($paul));
        $push = $this->pushesFor($paul)[0];
        $notification = NotificationModel::where('user_id', $paul->id)->where('type', 'session_cancelled')->first();
        $this->assertSame('Session annulée', $push['title']);
        $this->assertSame('session_cancelled', $push['data']['type']);
        $this->assertSame($session->id, $push['data']['session_id']);
        $this->assertSame($notification->id, $push['data']['notification_id']);
        // Ni l'organisateur, ni les invités en attente
        $this->assertSame([], $this->pushesFor($orga));
        $this->assertSame([], $this->pushesFor($dina));
    }
}
