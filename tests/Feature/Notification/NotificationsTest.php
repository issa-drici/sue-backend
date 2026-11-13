<?php

namespace Tests\Feature\Notification;

use App\Models\NotificationModel;
use App\Models\PushTokenModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\SueTestHelpers;
use Tests\TestCase;

/**
 * Notifications in-app (liste, non lues, lecture, suppression), endpoints d'envoi de push,
 * et compléments sur les jetons push.
 */
class NotificationsTest extends TestCase
{
    use RefreshDatabase, SueTestHelpers;

    private function notification(UserModel $user, bool $read = false, array $attributes = []): NotificationModel
    {
        return NotificationModel::create(array_merge([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'type' => 'update',
            'title' => 'Titre',
            'message' => 'Message',
            'read' => $read,
            'push_sent' => false,
        ], $attributes));
    }

    public function test_routes_notifications_exigent_une_authentification(): void
    {
        $id = (string) Str::uuid();
        $this->getJson('/api/notifications')->assertUnauthorized();
        $this->patchJson("/api/notifications/{$id}/read")->assertUnauthorized();
        $this->patchJson('/api/notifications/read-all')->assertUnauthorized();
        $this->deleteJson("/api/notifications/{$id}")->assertUnauthorized();
        $this->getJson('/api/notifications/unread-count')->assertUnauthorized();
        $this->postJson('/api/notifications/push')->assertUnauthorized();
        $this->postJson('/api/notifications/send')->assertUnauthorized();
        $this->postJson('/api/push-tokens')->assertUnauthorized();
    }

    public function test_liste_uniquement_mes_notifications_les_plus_recentes_en_premier(): void
    {
        $me = $this->makeUser('Me');
        $other = $this->makeUser('Other');
        $old = $this->notification($me, false, ['title' => 'Ancienne']);
        $old->forceFill(['created_at' => now()->subDay()])->save();
        $this->notification($me, true, ['title' => 'Récente']);
        $this->notification($other);

        $this->actingAs($me)->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('pagination.total', 2)
            ->assertJsonPath('data.0.title', 'Récente')
            ->assertJsonPath('data.1.title', 'Ancienne')
            ->assertJsonStructure(['data' => [['id', 'user_id', 'type', 'title', 'message', 'session_id', 'created_at', 'read', 'push_sent']]]);
    }

    public function test_compteur_de_non_lues(): void
    {
        $me = $this->makeUser('Me');
        $this->notification($me);
        $this->notification($me);
        $this->notification($me, true);
        $this->notification($this->makeUser('Other'));

        $this->actingAs($me)->getJson('/api/notifications/unread-count')->assertOk()->assertJson(['success' => true, 'data' => ['unreadCount' => 2]]);
    }

    public function test_marquer_une_notification_comme_lue(): void
    {
        $me = $this->makeUser('Me');
        $other = $this->makeUser('Other');
        $mine = $this->notification($me);
        $theirs = $this->notification($other);

        $this->actingAs($me)->patchJson("/api/notifications/{$mine->id}/read")->assertOk()->assertJsonPath('data.read', true);
        $this->assertTrue($mine->fresh()->read);

        $this->actingAs($me)->patchJson("/api/notifications/{$theirs->id}/read")->assertForbidden()->assertJsonPath('error.code', 'FORBIDDEN');
        $this->assertFalse($theirs->fresh()->read);
        $this->actingAs($me)->patchJson('/api/notifications/' . Str::uuid() . '/read')->assertNotFound()->assertJsonPath('error.code', 'NOTIFICATION_NOT_FOUND');
    }

    public function test_tout_marquer_comme_lu(): void
    {
        $me = $this->makeUser('Me');
        $other = $this->makeUser('Other');
        $this->notification($me);
        $this->notification($me);
        $theirs = $this->notification($other);

        $this->actingAs($me)->patchJson('/api/notifications/read-all')->assertOk()->assertJson(['success' => true]);
        $this->assertSame(0, NotificationModel::where('user_id', $me->id)->where('read', false)->count());
        $this->assertFalse($theirs->fresh()->read);
    }

    public function test_tout_marquer_comme_lu_sans_notification_non_lue(): void
    {
        $me = $this->makeUser('Me');
        $this->actingAs($me)->patchJson('/api/notifications/read-all')->assertOk()->assertJsonPath('data.updatedCount', 0);

        $this->notification($me);
        $this->notification($me);
        $this->actingAs($me)->patchJson('/api/notifications/read-all')->assertOk()->assertJsonPath('data.updatedCount', 2);
        $this->actingAs($me)->patchJson('/api/notifications/read-all')->assertOk()->assertJsonPath('data.updatedCount', 0);
    }

    public function test_supprimer_une_notification(): void
    {
        $me = $this->makeUser('Me');
        $other = $this->makeUser('Other');
        $mine = $this->notification($me);
        $theirs = $this->notification($other);

        $this->actingAs($me)->deleteJson("/api/notifications/{$theirs->id}")->assertForbidden();
        $this->actingAs($me)->deleteJson('/api/notifications/' . Str::uuid())->assertNotFound();
        $this->actingAs($me)->deleteJson("/api/notifications/{$mine->id}")->assertOk()->assertJson(['success' => true]);

        $this->assertNull(NotificationModel::find($mine->id));
        $this->assertNotNull(NotificationModel::find($theirs->id));
    }

    // --- Envoi de push --------------------------------------------------------------------

    public function test_endpoint_push_simule(): void
    {
        // Endpoint bouchon : ne fait rien d'autre que renvoyer la requête
        $me = $this->makeUser('Me');
        $this->actingAs($me)->postJson('/api/notifications/push', [])->assertStatus(400)->assertJsonPath('error.code', 'VALIDATION_ERROR');
        $this->actingAs($me)->postJson('/api/notifications/push', ['userId' => 'x', 'notification' => ['title' => 'T']])
            ->assertCreated()->assertJsonPath('data.sent', true);
        $this->assertSame([], $this->pushes);
    }

    public function test_envoi_de_push_a_un_utilisateur(): void
    {
        $me = $this->makeUser('Me', ['role' => 'admin']);
        $target = $this->makeUser('Target');
        $noToken = $this->makeUser('NoToken', [], false);

        $this->actingAs($me)->postJson('/api/notifications/send', [])->assertStatus(400)->assertJsonPath('error.code', 'VALIDATION_ERROR');
        $this->actingAs($me)->postJson('/api/notifications/send', ['recipientId' => $noToken->id, 'title' => 'T', 'body' => 'B'])
            ->assertNotFound()->assertJsonPath('error.code', 'NO_TOKENS_FOUND');

        $this->actingAs($me)->postJson('/api/notifications/send', ['recipientId' => $target->id, 'title' => 'Salut', 'body' => 'Corps', 'data' => ['k' => 'v']])
            ->assertOk()->assertJsonPath('data.tokensCount', 1);
        $this->assertSame([['title' => 'Salut', 'body' => 'Corps', 'data' => ['k' => 'v']]], $this->pushesFor($target));
    }

    public function test_envoi_de_push_reserve(): void
    {
        $me = $this->makeUser('Me');
        $stranger = $this->makeUser('Stranger');
        $this->actingAs($me)->postJson('/api/notifications/send', ['recipientId' => $stranger->id, 'title' => 'Gagné !', 'body' => 'Clique'])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'FORBIDDEN')
            ->assertJsonPath('error.message', 'Réservé aux administrateurs.');
        // Même vers soi-même (bouton de test du menu dev de l'app)
        $this->actingAs($me)->postJson('/api/notifications/send', ['recipientId' => $me->id, 'title' => 'T', 'body' => 'B'])
            ->assertForbidden();
        $this->assertSame([], $this->pushesFor($stranger));
        $this->assertSame([], $this->pushesFor($me));
    }

    // --- Jetons push (compléments) ----------------------------------------------------------

    public function test_jeton_push_validation_et_idempotence(): void
    {
        $me = $this->makeUser('Me', [], false);
        $token = 'ExponentPushToken[abcdefghijklmnop]';

        $this->actingAs($me)->postJson('/api/push-tokens', ['token' => $token, 'platform' => 'windows'])->assertStatus(400);
        $this->actingAs($me)->postJson('/api/push-tokens', [])->assertStatus(400)->assertJsonPath('error.code', 'VALIDATION_ERROR');
        $this->actingAs($me)->deleteJson('/api/push-tokens', [])->assertStatus(400);

        $this->actingAs($me)->postJson('/api/push-tokens', ['token' => $token, 'platform' => 'ios', 'device_id' => 'iphone-1'])->assertOk();
        $this->actingAs($me)->postJson('/api/push-tokens', ['token' => $token, 'platform' => 'ios', 'device_id' => 'iphone-1'])->assertOk();

        $this->assertSame(1, PushTokenModel::where('token', $token)->count());
        $this->assertDatabaseHas('push_tokens', ['token' => $token, 'user_id' => $me->id, 'platform' => 'ios', 'device_id' => 'iphone-1', 'is_active' => true]);
    }
}
