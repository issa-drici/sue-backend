<?php

namespace Tests\Feature;

use App\Models\UserBadgeModel;
use App\Models\UserReferralModel;
use App\Services\BadgeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\SueTestHelpers;
use Tests\TestCase;

class BadgesTest extends TestCase
{
    use RefreshDatabase, SueTestHelpers;

    private function badge(array $badges, string $id): array
    {
        return collect($badges)->firstWhere('id', $id);
    }

    private function playedSession($organizer, array $participants, string $sport, Carbon $start)
    {
        return $this->makeSession($organizer, $participants, [
            'sport' => $sport,
            'start_date' => $start,
            'end_date' => $start->copy()->addHour(),
        ]);
    }

    public function test_routes_badges_exigent_une_authentification(): void
    {
        $this->getJson('/api/badges')->assertUnauthorized();
        $this->postJson('/api/badges/seen', ['badgeIds' => ['x']])->assertUnauthorized();
        $this->getJson('/api/users/' . Str::uuid() . '/badges')->assertUnauthorized();
    }

    public function test_nouveau_joueur_aucun_badge_debloque(): void
    {
        $me = $this->makeUser('Me');

        $data = $this->actingAs($me)->getJson('/api/badges')->assertOk()->assertJsonPath('success', true)->json('data');

        $this->assertCount(12, $data);
        foreach ($data as $badge) {
            $this->assertFalse($badge['unlocked']);
            $this->assertSame(0, $badge['progress']);
            $this->assertFalse($badge['seen']);
        }
        $this->assertSame(0, UserBadgeModel::count());
    }

    public function test_progression_et_deblocage_des_badges(): void
    {
        $me = $this->makeUser('Me');
        $orga = $this->makeUser('Orga');
        // 3 sports différents, joués 4 semaines d'affilée (sessions terminées)
        $base = Carbon::now('Europe/Paris')->startOfWeek()->subWeeks(5)->setTime(12, 0);
        $this->playedSession($orga, [[$me, 'accepted']], 'tennis', $base->copy());
        $this->playedSession($orga, [[$me, 'accepted']], 'padel', $base->copy()->addWeek());
        $this->playedSession($me, [], 'football', $base->copy()->addWeeks(2));
        $this->playedSession($orga, [[$me, 'accepted']], 'tennis', $base->copy()->addWeeks(3));
        // Non comptées : à venir, annulée, refusée
        $this->makeSession($orga, [[$me, 'accepted']]);
        $this->playedSession($orga, [[$me, 'accepted']], 'golf', $base->copy()->addDay())->update(['status' => 'cancelled']);
        $this->playedSession($orga, [[$me, 'declined']], 'golf', $base->copy()->addDays(2));

        $data = $this->actingAs($me)->getJson('/api/badges')->assertOk()->json('data');

        $this->assertTrue($this->badge($data, 'first-session')['unlocked']);
        $this->assertSame(4, $this->badge($data, 'regular')['progress']);
        $this->assertFalse($this->badge($data, 'regular')['unlocked']);
        $this->assertTrue($this->badge($data, 'organizer')['unlocked']);
        $this->assertTrue($this->badge($data, 'multisport')['unlocked']);
        $this->assertSame(3, $this->badge($data, 'all-rounder')['progress']);
        $this->assertTrue($this->badge($data, 'streak')['unlocked']);
        $this->assertSame(1, $this->badge($data, 'captain')['progress']);
        $this->assertNotNull($this->badge($data, 'first-session')['unlockedAt']);

        $this->assertEqualsCanonicalizing(
            ['first-session', 'organizer', 'multisport', 'streak'],
            UserBadgeModel::where('user_id', $me->id)->pluck('badge')->all()
        );
    }

    public function test_badge_debloque_reste_acquis_et_marquer_comme_vu(): void
    {
        $me = $this->makeUser('Me');
        $session = $this->makeSession($me); // badge organisateur
        $this->actingAs($me)->getJson('/api/badges');

        $session->update(['status' => 'cancelled']);
        $data = $this->actingAs($me)->getJson('/api/badges')->json('data');
        $this->assertTrue($this->badge($data, 'organizer')['unlocked']);
        $this->assertSame(0, $this->badge($data, 'organizer')['progress']);
        $this->assertFalse($this->badge($data, 'organizer')['seen']);

        $this->actingAs($me)->postJson('/api/badges/seen', ['badgeIds' => ['organizer', 'inconnu']])
            ->assertOk()->assertJson(['success' => true]);

        $data = $this->actingAs($me)->getJson('/api/badges')->json('data');
        $this->assertTrue($this->badge($data, 'organizer')['seen']);
        $this->assertSame(1, UserBadgeModel::where('user_id', $me->id)->count());
    }

    public function test_marquer_vu_valide_la_requete(): void
    {
        $me = $this->makeUser('Me');
        $this->actingAs($me)->postJson('/api/badges/seen', [])->assertStatus(422);
        $this->actingAs($me)->postJson('/api/badges/seen', ['badgeIds' => 'organizer'])->assertStatus(422);
        $this->actingAs($me)->postJson('/api/badges/seen', ['badgeIds' => [str_repeat('x', 41)]])->assertStatus(422);
    }

    public function test_session_complete_amis_et_parrainages(): void
    {
        $me = $this->makeUser('Me');
        $friends = array_map(fn ($i) => $this->makeUser("Ami$i"), range(1, 10));
        foreach ($friends as $friend) {
            $this->befriend($me, $friend);
        }
        $this->makeSession($me, [[$friends[0], 'accepted']], ['max_participants' => 2]);
        foreach (array_slice($friends, 0, 5) as $friend) {
            UserReferralModel::create(['id' => (string) Str::uuid(), 'referrer_id' => $me->id, 'referred_id' => $friend->id]);
        }

        $data = $this->actingAs($me)->getJson('/api/badges')->json('data');

        foreach (['full-session', 'friends', 'recruiter', 'ambassador'] as $id) {
            $this->assertTrue($this->badge($data, $id)['unlocked'], $id);
        }
    }

    public function test_badges_d_un_autre_joueur_sans_le_champ_vu(): void
    {
        $me = $this->makeUser('Me');
        $other = $this->makeUser('Other');
        $this->makeSession($other);

        $data = $this->actingAs($me)->getJson("/api/users/{$other->id}/badges")->assertOk()->json('data');
        $this->assertTrue($this->badge($data, 'organizer')['unlocked']);
        $this->assertArrayNotHasKey('seen', $data[0]);

        // Ses propres badges via l'URL avec id : le champ vu est présent
        $own = $this->actingAs($me)->getJson("/api/users/{$me->id}/badges")->json('data');
        $this->assertArrayHasKey('seen', $own[0]);

        $this->actingAs($me)->getJson('/api/users/' . Str::uuid() . '/badges')
            ->assertNotFound()->assertJsonPath('error.code', 'USER_NOT_FOUND');
    }

    public function test_parrainage_compte_une_seule_fois_et_jamais_soi_meme(): void
    {
        [$a, $b, $new] = [$this->makeUser('A'), $this->makeUser('B'), $this->makeUser('New')];
        $service = app(BadgeService::class);

        $service->recordReferral($a->id, $a->id, null);
        $this->assertSame(0, UserReferralModel::count());

        $service->recordReferral($a->id, $new->id, null);
        $service->recordReferral($b->id, $new->id, null);
        $this->assertSame(1, UserReferralModel::count());
        $this->assertSame($a->id, UserReferralModel::first()->referrer_id);
    }
}
