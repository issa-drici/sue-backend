<?php

namespace Tests\Feature\Chat;

use App\Events\ChatUserTyping;
use App\Models\SessionChatStateModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Concerns\SueTestHelpers;
use Tests\TestCase;

/**
 * Chat de session : cas non couverts par SessionChatTest (indicateur de frappe, validations,
 * session inconnue).
 */
class SessionChatEndpointsTest extends TestCase
{
    use RefreshDatabase, SueTestHelpers;

    public function test_indicateur_de_frappe_reserve_aux_membres(): void
    {
        Event::fake([ChatUserTyping::class]);
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $outsider = $this->makeUser('Zed');
        $session = $this->makeSession($orga, [[$paul, 'declined']]);

        $this->actingAs($paul)->postJson("/api/sessions/{$session->id}/chat/typing")->assertOk()->assertJson(['success' => true, 'data' => null]);
        Event::assertDispatched(ChatUserTyping::class, fn ($e) => $e->userId === $paul->id && $e->firstname === 'Paul'
            && $e->broadcastOn()[0]->name === "private-session-chat.{$session->id}");

        $this->actingAs($outsider)->postJson("/api/sessions/{$session->id}/chat/typing")->assertForbidden()->assertJsonPath('error.code', 'FORBIDDEN');
        $this->actingAs($paul)->postJson('/api/sessions/' . Str::uuid() . '/chat/typing')->assertNotFound()->assertJsonPath('error.code', 'SESSION_NOT_FOUND');
        Event::assertDispatchedTimes(ChatUserTyping::class, 1);
    }

    public function test_validations_du_chat(): void
    {
        $orga = $this->makeUser('Orga');
        $session = $this->makeSession($orga);
        $base = "/api/sessions/{$session->id}/chat";

        $this->actingAs($orga)->getJson("{$base}/messages?before=0")->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_ERROR');
        $this->actingAs($orga)->getJson("{$base}/messages?after=-1")->assertStatus(422);
        $this->actingAs($orga)->postJson("{$base}/messages", ['body' => 'x'])->assertStatus(422);
        $this->actingAs($orga)->postJson("{$base}/messages", ['clientId' => 'pas-un-uuid', 'body' => 'x'])->assertStatus(422);
        $this->actingAs($orga)->postJson("{$base}/messages", ['clientId' => (string) Str::uuid(), 'body' => 'x', 'mentions' => ['x']])->assertStatus(422);
        $this->actingAs($orga)->postJson("{$base}/read", [])->assertStatus(422);
        $this->actingAs($orga)->putJson("{$base}/mute", [])->assertStatus(422);
        $this->actingAs($orga)->putJson("{$base}/mute", ['muted' => 'peut-être'])->assertStatus(422);
    }

    public function test_couper_et_reactiver_le_chat(): void
    {
        $orga = $this->makeUser('Orga');
        $session = $this->makeSession($orga);

        $this->actingAs($orga)->putJson("/api/sessions/{$session->id}/chat/mute", ['muted' => true])->assertOk()->assertJsonPath('data.muted', true);
        $this->actingAs($orga)->getJson("/api/sessions/{$session->id}/chat")->assertJsonPath('data.muted', true);
        $this->actingAs($orga)->putJson("/api/sessions/{$session->id}/chat/mute", ['muted' => false])->assertOk()->assertJsonPath('data.muted', false);
        $this->assertSame(1, SessionChatStateModel::where('session_id', $session->id)->where('user_id', $orga->id)->count());
    }

    public function test_session_inconnue(): void
    {
        $user = $this->makeUser('Orga');
        $id = (string) Str::uuid();

        $this->actingAs($user)->getJson("/api/sessions/{$id}/chat")->assertNotFound();
        $this->actingAs($user)->getJson("/api/sessions/{$id}/chat/messages")->assertNotFound();
        $this->actingAs($user)->postJson("/api/sessions/{$id}/chat/messages", ['clientId' => (string) Str::uuid(), 'body' => 'x'])->assertNotFound();
        $this->actingAs($user)->postJson("/api/sessions/{$id}/chat/read", ['messageId' => 1])->assertNotFound();
        $this->actingAs($user)->putJson("/api/sessions/{$id}/chat/mute", ['muted' => true])->assertNotFound();
    }
}
