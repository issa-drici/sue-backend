<?php

namespace Tests\Feature\Auth;

use App\Models\PhoneVerificationCodeModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\Concerns\SueTestHelpers;
use Tests\TestCase;

/**
 * Connexion / inscription par numéro de téléphone + code SMS.
 */
class PhoneAuthTest extends TestCase
{
    use RefreshDatabase, SueTestHelpers;

    private function enableSmsGateway(int $status = 202): void
    {
        config(['services.smsgate.username' => 'user', 'services.smsgate.password' => 'secret']);
        Http::fake(['*' => Http::response(['id' => 'x'], $status)]);
    }

    /** Envoie un code et le lit dans le SMS envoyé */
    private function requestCode(string $phone): string
    {
        $this->enableSmsGateway();
        $this->postJson('/api/auth/phone/send-otp', ['phone' => $phone])->assertOk();

        $code = null;
        Http::assertSent(function ($request) use (&$code) {
            preg_match('/(\d{6})/', $request['textMessage']['text'], $m);
            $code = $m[1] ?? null;
            return true;
        });

        return $code;
    }

    // --- Envoi du code ------------------------------------------------------------------

    public function test_envoi_du_code_normalise_le_numero_et_stocke_un_code_hache(): void
    {
        $this->enableSmsGateway();

        $this->postJson('/api/auth/phone/send-otp', ['phone' => '06 12 34 56 78'])
            ->assertOk()->assertJson(['success' => true]);

        $record = PhoneVerificationCodeModel::where('phone', '+33612345678')->first();
        $this->assertNotNull($record);
        $this->assertSame(0, $record->attempts);
        $this->assertTrue($record->expires_at->between(now()->addMinutes(4), now()->addMinutes(6)));
        Http::assertSent(fn ($request) => $request['phoneNumbers'] === ['+33612345678']
            && !str_contains($record->code_hash, $request['textMessage']['text']));
    }

    public function test_envoi_du_code_validation(): void
    {
        $this->postJson('/api/auth/phone/send-otp', [])->assertStatus(422)->assertJsonStructure(['errors' => ['phone']]);
        $this->postJson('/api/auth/phone/send-otp', ['phone' => '123'])->assertStatus(422);
        $this->assertSame(0, PhoneVerificationCodeModel::count());
    }

    public function test_renvoi_trop_rapide_puis_autorise_apres_45_secondes(): void
    {
        $this->enableSmsGateway();
        $this->postJson('/api/auth/phone/send-otp', ['phone' => '0612345678'])->assertOk();

        $this->postJson('/api/auth/phone/send-otp', ['phone' => '0612345678'])
            ->assertStatus(429)->assertJsonStructure(['retry_after']);

        $this->travel(46)->seconds();
        $this->postJson('/api/auth/phone/send-otp', ['phone' => '0612345678'])->assertOk();
    }

    public function test_echec_d_envoi_du_sms_ne_laisse_pas_de_code_ni_de_delai(): void
    {
        config(['services.smsgate.username' => 'user', 'services.smsgate.password' => 'secret']);
        Http::fake(['*' => Http::sequence()->push(['error' => 'down'], 500)->push(['id' => 'x'], 202)]);

        $this->postJson('/api/auth/phone/send-otp', ['phone' => '0612345678'])->assertStatus(500);
        $this->assertSame(0, PhoneVerificationCodeModel::count());

        // L'utilisateur peut réessayer tout de suite
        $this->postJson('/api/auth/phone/send-otp', ['phone' => '0612345678'])->assertOk();
    }

    public function test_throttle_de_la_route_d_envoi(): void
    {
        $this->enableSmsGateway();
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/phone/send-otp', ['phone' => sprintf('+3361234560%d', $i)])->assertOk();
        }
        $this->postJson('/api/auth/phone/send-otp', ['phone' => '+33612345699'])->assertStatus(429);
    }

    // --- Vérification ------------------------------------------------------------------

    public function test_numero_non_inscrit_demande_l_inscription(): void
    {
        $code = $this->requestCode('0612345678');

        $this->postJson('/api/auth/phone/verify', ['phone' => '+33612345678', 'code' => $code])
            ->assertOk()->assertJson(['isRegistered' => false]);

        // Code conservé et marqué vérifié pour l'inscription
        $this->assertNotNull(PhoneVerificationCodeModel::where('phone', '+33612345678')->value('verified_at'));
    }

    public function test_numero_inscrit_connecte_et_consomme_le_code(): void
    {
        $user = $this->makeUser('Alice', ['phone' => '+33612345678']);
        $code = $this->requestCode('0612345678');

        $response = $this->postJson('/api/auth/phone/verify', ['phone' => '0612345678', 'code' => $code, 'device_name' => 'iPhone'])
            ->assertOk()
            ->assertJson(['isRegistered' => true, 'user' => ['id' => $user->id, 'phone' => '+33612345678']]);

        $token = PersonalAccessToken::findToken($response->json('token'));
        $this->assertSame('iPhone', $token->name);
        $this->assertSame(0, PhoneVerificationCodeModel::count());

        // Code à usage unique
        $this->postJson('/api/auth/phone/verify', ['phone' => '0612345678', 'code' => $code])->assertStatus(400);
    }

    public function test_verification_erreurs_de_validation(): void
    {
        $this->postJson('/api/auth/phone/verify', [])->assertStatus(422)->assertJsonStructure(['errors' => ['phone', 'code']]);
        $this->postJson('/api/auth/phone/verify', ['phone' => '0612345678', 'code' => '12ab56'])->assertStatus(422);
        $this->postJson('/api/auth/phone/verify', ['phone' => '12', 'code' => '123456'])->assertStatus(422);
    }

    public function test_code_faux_trois_tentatives_maximum(): void
    {
        $this->storeOtp('+33612345678', '123456');

        foreach (range(1, 3) as $i) {
            $this->postJson('/api/auth/phone/verify', ['phone' => '0612345678', 'code' => '000000'])->assertStatus(400);
        }
        $this->assertSame(3, PhoneVerificationCodeModel::first()->attempts);

        // Même le bon code est refusé après 3 échecs
        $this->postJson('/api/auth/phone/verify', ['phone' => '0612345678', 'code' => '123456'])->assertStatus(400);
    }

    public function test_code_expire_ou_absent(): void
    {
        $this->postJson('/api/auth/phone/verify', ['phone' => '0612345678', 'code' => '123456'])->assertStatus(400);

        $this->storeOtp('+33612345678', '123456', null, ['expires_at' => now()->subSecond()]);
        $this->postJson('/api/auth/phone/verify', ['phone' => '0612345678', 'code' => '123456'])->assertStatus(400);
    }

    // --- Inscription ---------------------------------------------------------------------

    public function test_inscription_apres_verification(): void
    {
        $code = $this->requestCode('0612345678');
        $this->postJson('/api/auth/phone/verify', ['phone' => '0612345678', 'code' => $code])->assertOk();

        $response = $this->postJson('/api/auth/phone/register', [
            'phone' => '0612345678', 'firstname' => '  Alice ', 'lastname' => 'DUPONT-martin',
        ])->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'phone', 'firstname', 'lastname']]);

        $user = UserModel::where('phone', '+33612345678')->first();
        $this->assertSame('Alice', $user->firstname);
        $this->assertSame('Dupont-Martin', $user->lastname);
        $this->assertNull($user->email);
        $this->assertSame('player', $user->role);
        $this->assertSame('SUE Mobile App', PersonalAccessToken::findToken($response->json('token'))->name);
        $this->assertSame(0, PhoneVerificationCodeModel::count());

        // Code consommé : impossible de réinscrire avec la même vérification
        $this->postJson('/api/auth/phone/register', ['phone' => '0612345678', 'firstname' => 'X', 'lastname' => 'Y'])->assertStatus(401);
    }

    public function test_inscription_sans_verification_ou_verification_trop_ancienne(): void
    {
        $this->postJson('/api/auth/phone/register', ['phone' => '0612345678', 'firstname' => 'A', 'lastname' => 'B'])->assertStatus(401);

        $this->storeOtp('+33612345678', '123456'); // envoyé mais pas vérifié
        $this->postJson('/api/auth/phone/register', ['phone' => '0612345678', 'firstname' => 'A', 'lastname' => 'B'])->assertStatus(401);

        $this->storeOtp('+33612345678', '123456', now()->subMinutes(11));
        $this->postJson('/api/auth/phone/register', ['phone' => '0612345678', 'firstname' => 'A', 'lastname' => 'B'])->assertStatus(401);

        $this->assertSame(0, UserModel::count());
    }

    public function test_inscription_validation(): void
    {
        $this->postJson('/api/auth/phone/register', [])->assertStatus(422)
            ->assertJsonStructure(['errors' => ['phone', 'firstname', 'lastname']]);
        $this->postJson('/api/auth/phone/register', ['phone' => '0612345678', 'firstname' => str_repeat('a', 101), 'lastname' => 'B'])
            ->assertStatus(422);
        $this->postJson('/api/auth/phone/register', ['phone' => 'abc', 'firstname' => 'A', 'lastname' => 'B'])->assertStatus(422);
    }

    public function test_inscription_d_un_numero_deja_inscrit_connecte_simplement(): void
    {
        $user = $this->makeUser('Alice', ['phone' => '+33612345678']);
        $this->storeOtp('+33612345678', '123456', now());

        $this->postJson('/api/auth/phone/register', ['phone' => '0612345678', 'firstname' => 'Autre', 'lastname' => 'Nom'])
            ->assertOk()->assertJsonPath('user.id', $user->id)->assertJsonPath('user.firstname', 'Alice');
        $this->assertSame(1, UserModel::count());
    }

    // --- Compte de démo (review App Store) ---------------------------------------------

    public function test_compte_de_demo_code_fixe_sans_sms(): void
    {
        config(['services.review_account.phone' => '+33600000042', 'services.review_account.code' => '424242']);

        $this->postJson('/api/auth/phone/send-otp', ['phone' => '0600000042'])->assertOk();
        Http::assertNothingSent();

        $this->postJson('/api/auth/phone/verify', ['phone' => '0600000042', 'code' => '424242'])
            ->assertOk()->assertJson(['isRegistered' => true, 'user' => ['firstname' => 'Alex', 'lastname' => 'Martin']]);
    }

    public function test_compte_de_demo_ignore_si_le_code_configure_est_invalide(): void
    {
        // Sans code à 6 chiffres, le numéro de démo est traité comme un numéro normal
        config(['services.review_account.phone' => '+33600000042', 'services.review_account.code' => 'abc']);
        $this->enableSmsGateway();

        $this->postJson('/api/auth/phone/send-otp', ['phone' => '0600000042'])->assertOk();
        Http::assertSentCount(1);
        $this->assertSame(0, UserModel::count());
    }
}
