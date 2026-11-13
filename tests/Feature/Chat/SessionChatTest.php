<?php

namespace Tests\Feature\Chat;

use App\Events\ChatMessageSent;
use App\Models\NotificationModel;
use App\Models\PushTokenModel;
use App\Models\SessionChatMessageModel;
use App\Models\SportSessionModel;
use App\Models\SportSessionParticipantModel;
use App\Models\UserModel;
use App\Services\ExpoPushNotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class SessionChatTest extends TestCase
{
    use RefreshDatabase;

    /** Pushs « envoyés », par jeton : [token => [titres]] */
    private array $pushes = [];

    protected function setUp(): void
    {
        parent::setUp();
        $pushes = &$this->pushes;
        $this->app->instance(ExpoPushNotificationService::class, new class($pushes) extends ExpoPushNotificationService {
            public function __construct(private array &$pushes) {}
            public function sendNotification(array $tokens, string $title, string $body, array $data = []): array
            {
                foreach ($tokens as $token) {
                    $this->pushes[$token][] = ['title' => $title, 'type' => $data['type'] ?? null];
                }
                return ['success' => true];
            }
        });
    }

    private function user(string $firstname): UserModel
    {
        $user = UserModel::create([
            'phone' => '+3363998' . random_int(1000, 9999),
            'firstname' => $firstname,
            'lastname' => 'Test',
            'role' => 'player',
        ]);
        PushTokenModel::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[' . $firstname . ']',
            'platform' => 'expo',
            'is_active' => true,
        ]);
        return $user;
    }

    /** @param array<int, array{0: UserModel, 1: string}> $participants */
    private function sportSession(UserModel $organizer, array $participants): SportSessionModel
    {
        $start = Carbon::now()->addDay();
        $session = SportSessionModel::create([
            'id' => Str::uuid(),
            'sport' => 'football',
            'start_date' => $start,
            'end_date' => $start->copy()->addHour(),
            'location' => 'Five',
            'organizer_id' => $organizer->id,
            'status' => 'active',
            'scheduling' => 'scheduled',
        ]);
        foreach ([[$organizer, 'accepted'], ...$participants] as [$user, $status]) {
            SportSessionParticipantModel::create([
                'id' => Str::uuid(),
                'session_id' => $session->id,
                'user_id' => $user->id,
                'status' => $status,
            ]);
        }
        return $session;
    }

    private function send(UserModel $user, SportSessionModel $session, string $body, array $mentions = [], ?string $clientId = null)
    {
        return $this->actingAs($user)->postJson("/api/sessions/{$session->id}/chat/messages", [
            'clientId' => $clientId ?? (string) Str::uuid(),
            'body' => $body,
            'mentions' => $mentions,
        ]);
    }

    private function pushTitles(UserModel $user): array
    {
        return array_column($this->pushes['ExponentPushToken[' . $user->firstname . ']'] ?? [], 'title');
    }

    public function test_acces_organisateur_et_tous_les_invites_meme_refuses(): void
    {
        [$orga, $pending, $declined, $outsider] = array_map(fn ($n) => $this->user($n), ['Orga', 'Paul', 'Dina', 'Xavier']);
        $session = $this->sportSession($orga, [[$pending, 'pending'], [$declined, 'declined']]);

        foreach ([$orga, $pending, $declined] as $member) {
            $this->actingAs($member)->getJson("/api/sessions/{$session->id}/chat")
                ->assertOk()->assertJsonCount(3, 'data.members');
        }
        $this->send($declined, $session, 'Finalement je peux si on décale')->assertCreated();

        $this->actingAs($outsider)->getJson("/api/sessions/{$session->id}/chat/messages")->assertForbidden();
        $this->send($outsider, $session, 'intrus')->assertForbidden();
    }

    public function test_un_message_renvoye_avec_le_meme_client_id_n_est_pas_duplique(): void
    {
        [$orga, $paul] = array_map(fn ($n) => $this->user($n), ['Orga', 'Paul']);
        $session = $this->sportSession($orga, [[$paul, 'accepted']]);
        $clientId = (string) Str::uuid();

        $first = $this->send($paul, $session, 'Salut', [], $clientId)->assertCreated()->json('data');
        $again = $this->send($paul, $session, 'Salut', [], $clientId)->assertCreated()->json('data');

        $this->assertSame($first['id'], $again['id']);
        $this->assertSame($paul->id, $first['senderId']);
        $this->assertSame(1, SessionChatMessageModel::count());
        $this->assertCount(1, $this->pushTitles($orga)); // pas de 2e notification
    }

    public function test_historique_pagine_et_rattrapage(): void
    {
        [$orga, $paul] = array_map(fn ($n) => $this->user($n), ['Orga', 'Paul']);
        $session = $this->sportSession($orga, [[$paul, 'accepted']]);
        $ids = [];
        for ($i = 1; $i <= 45; $i++) {
            $ids[] = SessionChatMessageModel::create([
                'session_id' => $session->id, 'user_id' => $paul->id, 'client_id' => Str::uuid(), 'body' => "m$i",
            ])->id;
        }

        $latest = $this->actingAs($orga)->getJson("/api/sessions/{$session->id}/chat/messages")->assertOk()->json('data');
        $this->assertCount(40, $latest['messages']);
        $this->assertTrue($latest['hasMore']);
        $this->assertSame('m6', $latest['messages'][0]['body']);
        $this->assertSame('m45', $latest['messages'][39]['body']);

        $older = $this->getJson("/api/sessions/{$session->id}/chat/messages?before={$ids[5]}")->json('data');
        $this->assertSame(['m1', 'm2', 'm3', 'm4', 'm5'], array_column($older['messages'], 'body'));
        $this->assertFalse($older['hasMore']);

        $newer = $this->getJson("/api/sessions/{$session->id}/chat/messages?after={$ids[42]}")->json('data');
        $this->assertSame(['m44', 'm45'], array_column($newer['messages'], 'body'));
    }

    public function test_notifications_refuses_sourdine_et_mentions(): void
    {
        [$orga, $paul, $muted, $declined, $declinedTagged] = array_map(fn ($n) => $this->user($n), ['Orga', 'Paul', 'Mia', 'Dina', 'Theo']);
        $session = $this->sportSession($orga, [[$paul, 'accepted'], [$muted, 'pending'], [$declined, 'declined'], [$declinedTagged, 'declined']]);
        $this->actingAs($muted)->putJson("/api/sessions/{$session->id}/chat/mute", ['muted' => true])->assertOk();

        $this->send($paul, $session, 'On est combien ? @Theo tu viens ?', [$declinedTagged->id])->assertCreated();

        $this->assertSame(['Football · Paul'], $this->pushTitles($orga));
        $this->assertSame([], $this->pushTitles($paul));     // l'auteur
        $this->assertSame([], $this->pushTitles($muted));    // chat coupé
        $this->assertSame([], $this->pushTitles($declined)); // a refusé
        $this->assertSame(["Paul t'a mentionné · Football"], $this->pushTitles($declinedTagged));

        // Seule la mention apparaît dans l'onglet notifications
        $this->assertSame(1, NotificationModel::count());
        $this->assertSame($declinedTagged->id, NotificationModel::first()->user_id);
    }

    public function test_la_mention_passe_meme_chat_coupe_et_ignore_les_non_membres(): void
    {
        [$orga, $paul, $outsider] = array_map(fn ($n) => $this->user($n), ['Orga', 'Paul', 'Xavier']);
        $session = $this->sportSession($orga, [[$paul, 'accepted']]);
        $this->actingAs($orga)->putJson("/api/sessions/{$session->id}/chat/mute", ['muted' => true])->assertOk();

        $message = $this->send($paul, $session, '@Orga ça te va ?', [$orga->id, $outsider->id, $paul->id])->json('data');

        $this->assertSame([$orga->id], $message['mentions']);
        $this->assertSame(["Paul t'a mentionné · Football"], $this->pushTitles($orga));
        $this->assertSame([], $this->pushTitles($outsider));
    }

    public function test_non_lus_et_diffusion_temps_reel(): void
    {
        Event::fake([ChatMessageSent::class, \App\Events\ChatReadUpdated::class]);
        [$orga, $paul] = array_map(fn ($n) => $this->user($n), ['Orga', 'Paul']);
        $session = $this->sportSession($orga, [[$paul, 'accepted']]);

        $this->send($paul, $session, 'Un');
        $last = $this->send($paul, $session, 'Deux')->json('data');

        Event::assertDispatched(ChatMessageSent::class, 2);
        $this->assertSame(2, $this->actingAs($orga)->getJson("/api/sessions/{$session->id}/chat")->json('data.unreadCount'));
        $this->assertSame(0, $this->actingAs($paul)->getJson("/api/sessions/{$session->id}/chat")->json('data.unreadCount'));

        $this->actingAs($orga)->postJson("/api/sessions/{$session->id}/chat/read", ['messageId' => $last['id']])->assertOk();
        $this->assertSame(0, $this->getJson("/api/sessions/{$session->id}/chat")->json('data.unreadCount'));

        // « Vu » : jusqu'où chacun a lu, visible par les autres, diffusé en direct
        Event::assertDispatched(\App\Events\ChatReadUpdated::class, fn ($e) => $e->userId === $orga->id && $e->lastReadMessageId === $last['id']);
        $reads = $this->actingAs($paul)->getJson("/api/sessions/{$session->id}/chat")->json('data.reads');
        $this->assertSame($last['id'], $reads[$orga->id]);

        // Pas de « vu » au-delà du dernier message existant
        $this->actingAs($orga)->postJson("/api/sessions/{$session->id}/chat/read", ['messageId' => 999999])->assertOk();
        $this->assertSame($last['id'], $this->actingAs($paul)->getJson("/api/sessions/{$session->id}/chat")->json("data.reads.{$orga->id}"));
    }

    public function test_message_vide_ou_trop_long_refuse(): void
    {
        [$orga] = [$this->user('Orga')];
        $session = $this->sportSession($orga, []);

        $this->send($orga, $session, '   ')->assertStatus(422);
        $this->send($orga, $session, str_repeat('a', 2001))->assertStatus(422);
        $this->assertSame(0, SessionChatMessageModel::count());
    }

    public function test_canal_temps_reel_reserve_aux_membres(): void
    {
        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher.key' => 'key',
            'broadcasting.connections.pusher.secret' => 'secret',
            'broadcasting.connections.pusher.app_id' => 'app',
        ]);
        require base_path('routes/channels.php'); // canaux enregistrés sur le pilote pusher
        [$orga, $declined, $outsider] = array_map(fn ($n) => $this->user($n), ['Orga', 'Dina', 'Xavier']);
        $session = $this->sportSession($orga, [[$declined, 'declined']]);
        $payload = ['socket_id' => '123.456', 'channel_name' => "private-session-chat.{$session->id}"];

        $this->actingAs($declined)->postJson('/broadcasting/auth', $payload)->assertOk()->assertJsonStructure(['auth']);
        $this->actingAs($outsider)->postJson('/broadcasting/auth', $payload)->assertForbidden();
    }
}
