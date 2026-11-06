<?php

namespace Tests\Feature\Alerts;

use App\Models\PhoneVerificationCodeModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckSignupAlertsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.telegram.bot_token' => 'token',
            'services.telegram.chat_id' => '42',
            'services.smsgate.username' => 'user',
            'services.smsgate.password' => 'secret',
        ]);
    }

    private function code(string $phone, int $minutesAgo, bool $verified = false): void
    {
        PhoneVerificationCodeModel::create([
            'phone' => $phone,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->subMinutes($minutesAgo)->addMinutes(5),
            'attempts' => 0,
            'verified_at' => $verified ? now()->subMinutes($minutesAgo) : null,
            'last_sent_at' => now()->subMinutes($minutesAgo),
        ]);
    }

    private function telegramTexts(): array
    {
        return collect(Http::recorded())
            ->map(fn ($pair) => $pair[0])
            ->filter(fn (Request $r) => str_contains($r->url(), 'api.telegram.org'))
            ->map(fn (Request $r) => $r['text'])
            ->values()->all();
    }

    public function test_signale_une_seule_fois_les_personnes_bloquees(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true]),
            'api.sms-gate.app/*' => Http::response([
                ['id' => 'm1', 'state' => 'Failed', 'states' => ['Failed' => now()->subMinutes(3)->toIso8601String()],
                    'recipients' => [['state' => 'Failed', 'error' => 'no SEND_SMS permission']]],
                ['id' => 'm0', 'state' => 'Failed', 'states' => ['Failed' => now()->subDays(2)->toIso8601String()],
                    'recipients' => [['state' => 'Failed', 'error' => 'vieux']]],
                ['id' => 'm2', 'state' => 'Delivered', 'states' => ['Delivered' => now()->toIso8601String()]],
            ]),
        ]);

        $this->code('+33611111111', 15);              // jamais validé → alerte
        $this->code('+33622222222', 20, true);        // validé, pas inscrit → alerte
        $this->code('+33633333333', 1);               // trop récent → rien
        $this->code('+33644444444', 120);             // trop ancien → rien

        $this->artisan('alerts:check-signups')->assertSuccessful();
        $this->artisan('alerts:check-signups')->assertSuccessful(); // 2e passage : pas de doublon

        $texts = $this->telegramTexts();
        $this->assertCount(3, $texts);
        $this->assertStringContainsString('+33611111111', implode("\n", $texts));
        $this->assertStringContainsString('Inscription pas terminée', implode("\n", $texts));
        $this->assertStringContainsString('SEND_SMS', implode("\n", $texts));
        $this->assertStringNotContainsString('+33633333333', implode("\n", $texts));
        $this->assertStringNotContainsString('vieux', implode("\n", $texts));
    }

    public function test_alerte_a_l_inscription(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $this->code('+33655555555', 1, true);

        $this->postJson('/api/auth/phone/register', [
            'phone' => '+33655555555', 'firstname' => 'Léa', 'lastname' => 'Martin',
        ])->assertOk();

        $this->assertStringContainsString('Nouvel inscrit : Léa', implode("\n", $this->telegramTexts()));
    }
}
