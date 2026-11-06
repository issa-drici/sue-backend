<?php

namespace Tests\Feature\Alerts;

use App\Models\PhoneVerificationCodeModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class OtpAbuseProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('otp-sms-global');
        config([
            'services.smsgate.username' => 'user',
            'services.smsgate.password' => 'secret',
        ]);
        Http::fake(['api.sms-gate.app/*' => Http::response(['id' => 'x'], 202)]);
    }

    private function sendOtp(string $phone, string $ip = '10.0.0.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/auth/phone/send-otp', ['phone' => $phone]);
    }

    public function test_refuse_les_numeros_etrangers_et_fixes_sans_envoyer_de_sms(): void
    {
        foreach (['+6287737514644', '+254778409941', '+79999212883', '+33144556677', '+33899123456'] as $i => $phone) {
            $this->sendOtp($phone, "10.0.1.$i")->assertStatus(422);
        }

        Http::assertNothingSent();
        $this->assertSame(0, PhoneVerificationCodeModel::count());
    }

    public function test_accepte_les_mobiles_francais(): void
    {
        $this->sendOtp('0612345678')->assertOk();
        $this->sendOtp('+33712345678', '10.0.0.2')->assertOk();

        Http::assertSentCount(2);
    }

    public function test_plafond_horaire_global(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->sendOtp(sprintf('+336%08d', $i), "10.0.2.$i")->assertOk();
        }
        $this->sendOtp('+33699999999', '10.0.3.1')->assertStatus(500);

        Http::assertSentCount(30);
    }
}
