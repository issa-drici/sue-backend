<?php

namespace Tests\Feature\SportSession;

use App\Models\NotificationModel;
use App\Models\SportSessionModel;
use App\Models\SportSessionParticipantModel;
use App\Models\UserModel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CancelParticipationNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-05 10:00', 'Europe/Paris'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function user(string $firstname): UserModel
    {
        return UserModel::create([
            'phone' => '+3363998' . random_int(1000, 9999),
            'firstname' => $firstname,
            'lastname' => 'Test',
            'role' => 'player',
        ]);
    }

    /** @param array<int, array{0: UserModel, 1: string}> $participants */
    private function sportSession(UserModel $organizer, ?int $max, array $participants, string $scheduling = 'scheduled', int $days = 1): SportSessionModel
    {
        $start = Carbon::parse('2026-11-05 20:00', 'Europe/Paris')->addDays($days);
        $session = SportSessionModel::create([
            'id' => Str::uuid(),
            'sport' => 'football',
            'start_date' => $start->copy()->utc(),
            'end_date' => $start->copy()->addHour()->utc(),
            'location' => 'Five du Havre',
            'max_participants' => $max,
            'organizer_id' => $organizer->id,
            'status' => 'active',
            'scheduling' => $scheduling,
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

    private function cancel(UserModel $user, SportSessionModel $session): void
    {
        $this->actingAs($user)
            ->patchJson("/api/sessions/{$session->id}/cancel-participation")
            ->assertStatus(200);
    }

    private function notificationFor(UserModel $user): ?NotificationModel
    {
        return NotificationModel::where('user_id', $user->id)->first();
    }

    public function test_session_complete_previent_confirmes_et_invites_en_attente(): void
    {
        [$orga, $marie, $hugo, $lea, $sarah] = array_map(fn ($n) => $this->user($n), ['Orga', 'Marie', 'Hugo', 'Lea', 'Sarah']);
        $session = $this->sportSession($orga, 3, [[$marie, 'accepted'], [$hugo, 'accepted'], [$lea, 'pending'], [$sarah, 'declined']]);

        $this->cancel($marie, $session);

        foreach ([$orga, $hugo] as $confirmed) {
            $notification = $this->notificationFor($confirmed);
            $this->assertSame('Marie ne vient plus', $notification->title);
            $this->assertStringContainsString('il manque 1 joueur', $notification->message);
            $this->assertStringContainsString('Football, vendredi 6 novembre', $notification->message);
        }
        $this->assertSame('Une place vient de se libérer', $this->notificationFor($lea)->title);
        $this->assertNull($this->notificationFor($sarah));
        $this->assertNull($this->notificationFor($marie));
    }

    public function test_session_pas_complete_ne_relance_pas_les_invites_en_attente(): void
    {
        [$orga, $marie, $lea] = array_map(fn ($n) => $this->user($n), ['Orga', 'Marie', 'Lea']);
        $session = $this->sportSession($orga, 10, [[$marie, 'accepted'], [$lea, 'pending']]);

        $this->cancel($marie, $session);

        $this->assertStringContainsString('il manque 9 joueurs', $this->notificationFor($orga)->message);
        $this->assertNull($this->notificationFor($lea));
    }

    public function test_session_sans_limite_ne_parle_pas_de_place(): void
    {
        [$orga, $marie, $lea] = array_map(fn ($n) => $this->user($n), ['Orga', 'Marie', 'Lea']);
        $session = $this->sportSession($orga, null, [[$marie, 'accepted'], [$lea, 'pending']]);

        $this->cancel($marie, $session);

        $this->assertStringNotContainsString('il manque', $this->notificationFor($orga)->message);
        $this->assertNull($this->notificationFor($lea));
    }

    public function test_desistement_le_jour_meme_avant_la_session(): void
    {
        [$orga, $marie] = array_map(fn ($n) => $this->user($n), ['Orga', 'Marie']);
        $session = $this->sportSession($orga, null, [[$marie, 'accepted']], days: 0);

        $this->cancel($marie, $session);

        $this->assertSame('Marie ne vient plus', $this->notificationFor($orga)->title);
    }

    public function test_session_deja_terminee_refuse_le_desistement(): void
    {
        [$orga, $marie] = array_map(fn ($n) => $this->user($n), ['Orga', 'Marie']);
        $session = $this->sportSession($orga, null, [[$marie, 'accepted']], days: 0);

        Carbon::setTestNow(Carbon::parse('2026-11-05 21:30', 'Europe/Paris'));

        $this->actingAs($marie)
            ->patchJson("/api/sessions/{$session->id}/cancel-participation")
            ->assertStatus(409);
        $this->assertNull($this->notificationFor($orga));
    }
}
