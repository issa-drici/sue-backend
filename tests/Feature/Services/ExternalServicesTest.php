<?php

namespace Tests\Feature\Services;

use App\Jobs\SendSmsJob;
use App\Services\ClickSendSmsService;
use App\Services\ExpoPushNotificationService;
use App\Services\InfiniReachSmsService;
use App\Services\SmsGateSmsService;
use App\Services\TelegramAlertService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

/**
 * Intégrations externes, toutes simulées (Http::fake) : alertes Telegram (dont les erreurs
 * serveur), fournisseurs SMS de secours, Expo, job SMS.
 */
class ExternalServicesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config([
            'services.telegram.bot_token' => null,
            'services.telegram.chat_id' => null,
        ]);
    }

    private function configureTelegram(): void
    {
        config(['services.telegram.bot_token' => 'BOT', 'services.telegram.chat_id' => '42']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    }

    // --- Telegram ------------------------------------------------------------------------

    public function test_telegram_non_configure_n_envoie_rien(): void
    {
        Http::fake();
        $alerts = new TelegramAlertService();
        $this->assertFalse($alerts->isConfigured());
        $alerts->send('x');
        $alerts->sendOnce('k', 'x');
        Http::assertNothingSent();
    }

    public function test_telegram_envoi_et_deduplication(): void
    {
        $this->configureTelegram();
        $alerts = new TelegramAlertService();

        $alerts->send('Bonjour');
        $alerts->sendOnce('cle', 'Une fois');
        $alerts->sendOnce('cle', 'Une fois');
        $alerts->sendOnce('autre', 'Autre');

        Http::assertSentCount(3);
        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.telegram.org/botBOT/sendMessage'
            && $r['chat_id'] === '42' && $r['text'] === 'Bonjour');

        // Après la fenêtre de 30 min, la même alerte repart
        $this->travel(31)->minutes();
        $alerts->sendOnce('cle', 'Une fois');
        Http::assertSentCount(4);
    }

    public function test_telegram_ne_leve_jamais_d_exception(): void
    {
        config(['services.telegram.bot_token' => 'BOT', 'services.telegram.chat_id' => '42']);
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('down'));

        (new TelegramAlertService())->send('x');
        $this->assertTrue(true);
    }

    public function test_erreur_serveur_non_geree_alerte_telegram_une_seule_fois(): void
    {
        $this->configureTelegram();
        Route::get('/_test/boom', fn () => throw new RuntimeException('Kaboom'));
        Route::get('/_test/not-found', fn () => abort(404));

        $this->get('/_test/boom')->assertStatus(500);
        $this->get('/_test/boom')->assertStatus(500);
        $this->get('/_test/not-found')->assertNotFound();

        Http::assertSentCount(1);
        // (en test, runningInConsole() est vrai : l'URL est remplacée par « console »)
        Http::assertSent(fn (Request $r) => str_contains($r['text'], 'RuntimeException: Kaboom') && str_contains($r['text'], 'Erreur serveur'));
    }

    // --- SMS -------------------------------------------------------------------------------

    public function test_job_sms_envoie_via_sms_gateway(): void
    {
        config(['services.smsgate.username' => 'u', 'services.smsgate.password' => 'p', 'services.smsgate.url' => 'https://sms.test']);
        Http::fake(['sms.test/*' => Http::response(['id' => 1], 202)]);

        (new SendSmsJob('+33612345678', 'Bonjour'))->handle(app(SmsGateSmsService::class));
        Http::assertSent(fn (Request $r) => $r['phoneNumbers'] === ['+33612345678'] && $r['textMessage']['text'] === 'Bonjour');

        $job = new SendSmsJob('+33612345678', 'Bonjour');
        $this->assertSame(3, $job->tries);
        $job->failed(new RuntimeException('KO')); // journalise seulement
    }

    public function test_clicksend(): void
    {
        Http::fake(['rest.clicksend.com/*' => Http::sequence()
            ->push(['response_code' => 'SUCCESS', 'data' => ['messages' => [['status' => 'SUCCESS']]]])
            ->push(['response_code' => 'SUCCESS', 'data' => ['messages' => [['status' => 'INVALID_RECIPIENT']]]])]);
        $service = new ClickSendSmsService();

        config(['services.clicksend.username' => null, 'services.clicksend.key' => null]);
        $service->send('+33612345678', 'dev'); // non configuré : journalisé
        Http::assertNothingSent();

        config(['services.clicksend.username' => 'u', 'services.clicksend.key' => 'k', 'services.clicksend.from' => 'SUE']);
        $service->send('+33612345678', 'ok');
        Http::assertSent(fn (Request $r) => $r['messages'][0]['from'] === 'SUE' && $r['messages'][0]['to'] === '+33612345678');

        $this->expectException(RuntimeException::class);
        $service->send('+33612345678', 'ko');
    }

    public function test_infinireach(): void
    {
        Http::fake(['api.infinireach.io/*' => Http::sequence()->push(['id' => 1])->push(['error' => 'x'], 400)]);
        $service = new InfiniReachSmsService();

        config(['services.infinireach.api_key' => null, 'services.infinireach.from' => null]);
        $service->send('+33612345678', 'dev');
        Http::assertNothingSent();

        config(['services.infinireach.api_key' => 'key', 'services.infinireach.from' => 'SUE']);
        $service->send('+33612345678', 'ok');
        Http::assertSent(fn (Request $r) => $r->hasHeader('X-API-Key', 'key') && $r['channel'] === 'sms');

        $this->expectException(RuntimeException::class);
        $service->send('+33612345678', 'ko');
    }

    // --- Expo ------------------------------------------------------------------------------

    public function test_expo_validation_des_jetons_sans_appel_reseau(): void
    {
        $expo = new ExpoPushNotificationService();

        $this->assertTrue($expo->isValidToken('ExponentPushToken[abc]'));
        $this->assertFalse($expo->isValidToken('ExponentPushToken[]'));
        $this->assertFalse($expo->isValidToken('fcm-token'));
        $this->assertSame([0 => 'ExponentPushToken[a]'], $expo->filterValidTokens(['ExponentPushToken[a]', 'x']));
        $this->assertSame(['success' => false, 'error' => 'Aucun token fourni'], $expo->sendNotification([], 'T', 'B'));
        $this->assertFalse($expo->sendToUser('u', 'T', 'B')['success']);
    }
}
