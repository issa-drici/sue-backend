<?php

namespace Tests\Unit\Sms;

use App\Services\SmsGateSmsService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class SmsGateSmsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.smsgate.url' => 'https://api.sms-gate.app',
            'services.smsgate.username' => 'user',
            'services.smsgate.password' => 'secret',
        ]);
    }

    public function test_envoie_le_sms_avec_auth_basic_et_expiration(): void
    {
        Http::fake(['api.sms-gate.app/*' => Http::response(['id' => 'abc', 'state' => 'Pending'], 202)]);

        (new SmsGateSmsService())->send('+32470123456', 'Votre code de vérification SUE est : 123456');

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.sms-gate.app/3rdparty/v1/messages'
                && $request->hasHeader('Authorization', 'Basic ' . base64_encode('user:secret'))
                && $request['phoneNumbers'] === ['+32470123456']
                && $request['textMessage'] === ['text' => 'Votre code de vérification SUE est : 123456']
                && $request['ttl'] === 300
                && $request['priority'] === 100;
        });
    }

    public function test_leve_une_exception_si_l_api_refuse(): void
    {
        Http::fake(['api.sms-gate.app/*' => Http::response(['message' => 'Unauthorized'], 401)]);

        $this->expectException(RuntimeException::class);
        (new SmsGateSmsService())->send('+33612345678', 'code');
    }

    public function test_sans_identifiants_le_sms_est_journalise_sans_appel(): void
    {
        config(['services.smsgate.username' => null]);
        Http::fake();

        (new SmsGateSmsService())->send('+33612345678', 'code');

        Http::assertNothingSent();
    }
}
