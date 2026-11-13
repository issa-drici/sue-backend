<?php

namespace Tests\Feature\Console;

use App\Console\Commands\SeedReviewAccountCommand;
use App\Models\FriendModel;
use App\Models\NotificationModel;
use App\Models\SessionSlotAvailabilityModel;
use App\Models\SessionSlotProposalModel;
use App\Models\SportSessionModel;
use App\Models\UserModel;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\SueTestHelpers;
use Tests\TestCase;

/**
 * Commandes artisan : rappels de session, rappels « trouver un créneau », compte de démo,
 * et planification (routes/console.php).
 */
class ScheduledCommandsTest extends TestCase
{
    use RefreshDatabase, SueTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        // Instant fixe, en début de minute, pour des fenêtres de rappel déterministes
        $this->travelTo(Carbon::parse('2026-12-01 09:00:00', 'UTC'));
    }

    private function sessionStartingAt(Carbon $start, UserModel $orga, array $participants = [], array $attributes = []): SportSessionModel
    {
        return $this->makeSession($orga, $participants, ['start_date' => $start, 'end_date' => $start->copy()->addHours(2)] + $attributes);
    }

    // --- Planification -----------------------------------------------------------------

    public function test_planification_des_commandes(): void
    {
        $events = collect(app(Schedule::class)->events());
        $find = fn (string $command) => $events->first(fn ($event) => str_contains($event->command ?? '', $command));

        foreach (['sessions:send-reminders' => '* * * * *', 'alerts:check-signups' => '* * * * *', 'sessions:send-slot-reminders' => '*/5 * * * *'] as $command => $cron) {
            $event = $find($command);
            $this->assertNotNull($event, "{$command} n'est pas planifiée");
            $this->assertSame($cron, $event->expression, $command);
            $this->assertTrue($event->withoutOverlapping, $command);
        }
    }

    public function test_commande_inspire(): void
    {
        $this->artisan('inspire')->assertSuccessful();
    }

    // --- Rappels de session -------------------------------------------------------------

    public function test_rappels_24h_1h_et_demarrage_aux_participants_acceptes(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $dina = $this->makeUser('Dina');
        $noToken = $this->makeUser('NoToken', [], false);

        $s24 = $this->sessionStartingAt(now()->addHours(24), $orga, [[$paul, 'accepted'], [$dina, 'pending'], [$noToken, 'accepted']], ['sport' => 'tennis']);
        $s1 = $this->sessionStartingAt(now()->addHour(), $orga, [[$paul, 'accepted']], ['sport' => 'padel']);
        $s0 = $this->sessionStartingAt(now(), $orga, [[$paul, 'accepted']], ['sport' => 'football']);
        // Pas de rappel : annulée, créneau à définir, hors fenêtre
        $this->sessionStartingAt(now()->addHours(24), $orga, [[$paul, 'accepted']], ['status' => 'cancelled']);
        $this->sessionStartingAt(now()->addHour(), $orga, [[$paul, 'accepted']], ['scheduling' => 'finding_slot']);
        $this->sessionStartingAt(now()->addHours(5), $orga, [[$paul, 'accepted']]);

        $this->artisan('sessions:send-reminders')->assertSuccessful()
            ->expectsOutputToContain('Rappels 24h avant:')
            ->expectsOutputToContain('Envoi des rappels terminé avec succès.');

        $types = fn (UserModel $user) => collect($this->pushesFor($user))->pluck('data.reminder_type')->sort()->values()->all();
        $this->assertSame(['reminder_1h', 'reminder_24h', 'reminder_start'], $types($paul));
        $this->assertSame(['reminder_1h', 'reminder_24h', 'reminder_start'], $types($orga));
        $this->assertSame([], $this->pushesFor($dina));
        $this->assertSame(0, NotificationModel::where('user_id', $noToken->id)->count()); // pas de jeton : rien

        foreach ([[$s24, 'reminder_24h'], [$s1, 'reminder_1h'], [$s0, 'reminder_start']] as [$session, $type]) {
            $notification = NotificationModel::where('session_id', $session->id)->where('user_id', $paul->id)->first();
            $this->assertSame('reminder', $notification->type);
            $this->assertSame($type, $notification->push_data['reminder_type']);
            $this->assertTrue($notification->push_sent);
        }
    }

    public function test_rappels_non_dupliques_d_une_minute_a_l_autre(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $this->sessionStartingAt(now()->addHours(24), $orga, [[$paul, 'accepted']]);

        $this->artisan('sessions:send-reminders')->assertSuccessful();
        $this->travel(1)->minutes(); // toujours dans la marge d'une minute
        $this->artisan('sessions:send-reminders')->assertSuccessful();

        $this->assertCount(1, $this->pushesFor($paul));
        $this->assertSame(1, NotificationModel::where('user_id', $paul->id)->count());
    }

    // --- Rappels « trouver un créneau » ------------------------------------------------

    private function findingSlotSession(UserModel $orga, array $slotStarts, Carbon $createdAt, array $participants = []): SportSessionModel
    {
        $session = $this->makeSession($orga, $participants, ['scheduling' => 'finding_slot']);
        $session->forceFill(['created_at' => $createdAt])->save();
        foreach ($slotStarts as $start) {
            SessionSlotProposalModel::create([
                'id' => (string) Str::uuid(), 'session_id' => $session->id,
                'start_date' => $start, 'end_date' => $start->copy()->addHour(),
            ]);
        }
        return $session;
    }

    public function test_rappel_de_choisir_un_creneau_48h_avant_le_premier_creneau_une_seule_fois(): void
    {
        $orga = $this->makeUser('Orga');
        $paul = $this->makeUser('Paul');
        $due = $this->findingSlotSession($orga, [now()->addHours(47), now()->addHours(70)], now()->subDay(), [[$paul, 'pending']]);
        SessionSlotAvailabilityModel::create(['id' => (string) Str::uuid(), 'slot_id' => SessionSlotProposalModel::where('session_id', $due->id)->orderBy('start_date')->value('id'), 'user_id' => $paul->id]);
        $notYet = $this->findingSlotSession($orga, [now()->addHours(72)], now()->subDay());

        $this->artisan('sessions:send-slot-reminders')->assertSuccessful()->expectsOutputToContain('Rappels : 1, sans créneau : 0');

        $push = $this->pushesFor($orga)[0];
        $this->assertStringStartsWith('Choisis le créneau de ta session', $push['title']);
        $this->assertStringContainsString('(1 dispo)', $push['body']);
        $this->assertSame('slot_reminder', $push['data']['type']);
        $this->assertNotNull($due->fresh()->slot_reminder_sent_at);
        $this->assertNull($notYet->fresh()->slot_reminder_sent_at);

        $this->artisan('sessions:send-slot-reminders')->expectsOutputToContain('Rappels : 0, sans créneau : 0');
        $this->assertCount(1, $this->pushesFor($orga));

        // 24 h plus tard, c'est au tour de la seconde session
        $this->travel(25)->hours();
        $this->artisan('sessions:send-slot-reminders')->expectsOutputToContain('Rappels : 1');
    }

    public function test_pas_de_rappel_juste_apres_la_creation(): void
    {
        $orga = $this->makeUser('Orga');
        // Créée à l'instant, premier créneau dans 4 h : rappel au plus tôt 3 h après la création
        $session = $this->findingSlotSession($orga, [now()->addHours(4)], now());

        $this->artisan('sessions:send-slot-reminders')->assertSuccessful();
        $this->assertSame([], $this->pushes);

        $this->travel(121)->minutes();
        $this->artisan('sessions:send-slot-reminders')->assertSuccessful();
        $this->assertCount(1, $this->pushesFor($orga));
        $this->assertStringContainsString("Personne n'a encore répondu", $this->pushesFor($orga)[0]['body']);
    }

    public function test_plus_aucun_creneau_a_venir_previent_une_fois(): void
    {
        $orga = $this->makeUser('Orga');
        $session = $this->findingSlotSession($orga, [now()->subHour()], now()->subDays(3));
        $this->findingSlotSession($orga, [now()->subHour()], now()->subDays(3))->update(['status' => 'cancelled']);

        $this->artisan('sessions:send-slot-reminders')->expectsOutputToContain('sans créneau : 1');
        $this->artisan('sessions:send-slot-reminders')->expectsOutputToContain('sans créneau : 0');

        $this->assertCount(1, $this->pushesFor($orga));
        $this->assertSame("Aucun créneau n'a été confirmé", $this->pushesFor($orga)[0]['title']);
        $this->assertNotNull($session->fresh()->slot_expired_notified_at);
    }

    // --- Compte de démo -------------------------------------------------------------------

    public function test_compte_de_demo_sans_configuration_ne_fait_rien(): void
    {
        $this->artisan('demo:review-account')->assertSuccessful()->expectsOutputToContain('rien à faire');
        $this->assertSame(0, UserModel::count());
    }

    public function test_compte_de_demo_rempli_et_rafraichi_sans_doublon(): void
    {
        config(['services.review_account.phone' => '+33600000042']);

        $this->artisan('demo:review-account')->assertSuccessful()->expectsOutputToContain('Compte de démo prêt.');
        $me = UserModel::where('phone', '+33600000042')->first();
        $this->assertNotNull($me);
        $this->assertSame(9, UserModel::count());
        $this->assertSame(8, FriendModel::where('user_id', $me->id)->count());
        $this->assertSame(8, SportSessionModel::count());
        $this->assertSame(2, SportSessionModel::where('scheduling', 'finding_slot')->count());
        $this->assertSame(5, SessionSlotProposalModel::count());
        $this->assertSame(3, NotificationModel::where('user_id', $me->id)->count());
        // Lien fixe ouvert par les tests de bout en bout : session dont le compte n'est pas membre
        $volley = SportSessionModel::where('share_token', SeedReviewAccountCommand::E2E_JOIN_TOKEN)->first();
        $this->assertNotNull($volley);
        $this->assertFalse($volley->participants()->where('user_id', $me->id)->exists());

        // Un nom changé pendant une démo est remis au passage suivant
        $me->update(['firstname' => 'Autre', 'lastname' => 'Nom']);

        // Deuxième passage : remis à neuf, pas de doublon
        $this->artisan('demo:review-account')->assertSuccessful();
        $this->assertSame(9, UserModel::count());
        $this->assertSame(8, FriendModel::where('user_id', $me->id)->count());
        $this->assertSame(8, SportSessionModel::count());
        $this->assertSame(5, SessionSlotProposalModel::count());
        $this->assertSame(3, NotificationModel::where('user_id', $me->id)->count());
        $this->assertSame(['Alex', 'Martin'], [$me->fresh()->firstname, $me->fresh()->lastname]);

        // Les sessions à venir de la démo sont bien listées pour le compte (pas le volley)
        $this->actingAs($me)->getJson('/api/sessions')->assertOk()->assertJsonPath('pagination.total', 5);
    }
}
