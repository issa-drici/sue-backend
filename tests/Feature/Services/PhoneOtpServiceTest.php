<?php

namespace Tests\Feature\Services;

use App\Exceptions\InvalidOtpException;
use App\Exceptions\OtpResendTooSoonException;
use App\Exceptions\PhoneNotVerifiedException;
use App\Models\PhoneVerificationCodeModel;
use App\Services\PhoneOtpService;
use App\Services\SmsGateSmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\SueTestHelpers;
use Tests\TestCase;

/**
 * Règles du code SMS : 6 chiffres, 5 min de validité, 3 essais, 45 s entre deux envois,
 * 10 min pour finir l'inscription, plafond global de 30 SMS/h.
 */
class PhoneOtpServiceTest extends TestCase
{
    use RefreshDatabase, SueTestHelpers;

    /** SMS « envoyés » : [[to, message]] */
    private array $sent = [];
    private bool $smsFails = false;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('otp-sms-global');
        $sent = &$this->sent;
        $fails = &$this->smsFails;
        $this->app->instance(SmsGateSmsService::class, new class($sent, $fails) extends SmsGateSmsService {
            public function __construct(private array &$sent, private bool &$fails) {}
            public function send(string $to, string $message): void
            {
                if ($this->fails) {
                    // Le code est déjà enregistré au moment de l'envoi (cf. PhoneOtpService::sendCode)
                    \PHPUnit\Framework\Assert::assertSame(1, PhoneVerificationCodeModel::where('phone', $to)->count());
                    throw new \RuntimeException('SMS KO');
                }
                $this->sent[] = [$to, $message];
            }
        });
    }

    private function service(): PhoneOtpService
    {
        return app(PhoneOtpService::class);
    }

    private function sentCode(): string
    {
        preg_match('/(\d{6})$/', end($this->sent)[1], $m);
        return $m[1];
    }

    public function test_envoi_genere_un_code_a_6_chiffres_stocke_hache(): void
    {
        $this->service()->sendCode('+33612345678');

        $this->assertCount(1, $this->sent);
        $code = $this->sentCode();
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $record = PhoneVerificationCodeModel::first();
        $this->assertNotSame($code, $record->code_hash);
        $this->assertTrue(Hash::check($code, $record->code_hash));
        $this->assertNull($record->verified_at);
    }

    public function test_numero_non_mobile_francais_refuse_sans_sms(): void
    {
        $this->expectException(ValidationException::class);
        try {
            $this->service()->sendCode('+14155550100');
        } finally {
            $this->assertSame([], $this->sent);
        }
    }

    public function test_delai_de_45_secondes_entre_deux_envois(): void
    {
        $this->service()->sendCode('+33612345678');
        $this->travel(30)->seconds();

        try {
            $this->service()->sendCode('+33612345678');
            $this->fail('OtpResendTooSoonException attendue');
        } catch (OtpResendTooSoonException $e) {
            $this->assertSame(15, $e->secondsRemaining);
        }

        $this->travel(16)->seconds();
        $this->service()->sendCode('+33612345678');
        $this->assertCount(2, $this->sent);
        // Un nouveau code remplace l'ancien (une seule ligne, essais remis à zéro)
        $this->assertSame(1, PhoneVerificationCodeModel::count());
    }

    public function test_echec_d_envoi_supprime_le_code(): void
    {
        $this->smsFails = true;
        try {
            $this->service()->sendCode('+33612345678');
            $this->fail('Exception attendue');
        } catch (\RuntimeException $e) {
            $this->assertSame('SMS KO', $e->getMessage());
        }
        $this->assertSame(0, PhoneVerificationCodeModel::count());
    }

    public function test_plafond_global_de_30_sms_par_heure(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->service()->sendCode(sprintf('+336%08d', $i));
        }
        try {
            $this->service()->sendCode('+33699999999');
            $this->fail('Plafond attendu');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Plafond horaire', $e->getMessage());
        }
        $this->assertCount(30, $this->sent);

        // Une heure plus tard, les envois reprennent
        $this->travel(61)->minutes();
        $this->service()->sendCode('+33699999999');
        $this->assertCount(31, $this->sent);
    }

    public function test_verification_du_code(): void
    {
        $this->service()->sendCode('+33612345678');
        $code = $this->sentCode();

        $this->service()->verifyCode('+33612345678', $code);
        $this->assertNotNull(PhoneVerificationCodeModel::first()->verified_at);
    }

    public function test_raisons_d_echec_de_verification(): void
    {
        $reason = function (callable $fn) {
            try {
                $fn();
            } catch (InvalidOtpException $e) {
                return $e->reason;
            }
            return null;
        };

        $this->assertSame('no_code', $reason(fn () => $this->service()->verifyCode('+33612345678', '123456')));

        $this->storeOtp('+33612345678', '123456');
        $this->assertSame('mismatch', $reason(fn () => $this->service()->verifyCode('+33612345678', '000000')));
        $this->assertSame('mismatch', $reason(fn () => $this->service()->verifyCode('+33612345678', '000000')));
        $this->assertSame('mismatch', $reason(fn () => $this->service()->verifyCode('+33612345678', '000000')));
        $this->assertSame('too_many_attempts', $reason(fn () => $this->service()->verifyCode('+33612345678', '123456')));

        $this->storeOtp('+33612345678', '123456');
        $this->travel(301)->seconds();
        $this->assertSame('expired', $reason(fn () => $this->service()->verifyCode('+33612345678', '123456')));
    }

    public function test_fenetre_de_10_minutes_apres_verification(): void
    {
        try {
            $this->service()->assertRecentlyVerified('+33612345678');
            $this->fail();
        } catch (PhoneNotVerifiedException) {
        }

        $this->storeOtp('+33612345678', '123456', now());
        $this->service()->assertRecentlyVerified('+33612345678');

        $this->travel(11)->minutes();
        $this->expectException(PhoneNotVerifiedException::class);
        $this->service()->assertRecentlyVerified('+33612345678');
    }

    public function test_consommer_supprime_le_code(): void
    {
        $this->storeOtp('+33612345678');
        $this->storeOtp('+33687654321');
        $this->service()->consume('+33612345678');
        $this->assertSame(['+33687654321'], PhoneVerificationCodeModel::pluck('phone')->all());
    }

    public function test_numero_de_demo_code_fixe_et_compte_rempli(): void
    {
        config(['services.review_account.phone' => '+33600000042', 'services.review_account.code' => '424242']);
        Artisan::spy();

        $this->service()->sendCode('+33600000042');
        $this->service()->sendCode('+33600000042'); // pas de délai entre deux envois pour la démo

        $this->assertSame([], $this->sent);
        Artisan::shouldHaveReceived('call')->with('demo:review-account')->twice();
        $this->service()->verifyCode('+33600000042', '424242');
        $this->assertNotNull(PhoneVerificationCodeModel::first()->verified_at);
    }
}
