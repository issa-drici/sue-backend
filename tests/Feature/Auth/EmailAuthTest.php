<?php

namespace Tests\Feature\Auth;

use App\Models\UserModel;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SueTestHelpers;
use Tests\TestCase;

/**
 * Authentification historique par e-mail / mot de passe (API + routes web de routes/auth.php).
 */
class EmailAuthTest extends TestCase
{
    use RefreshDatabase, SueTestHelpers;

    // --- Inscription ------------------------------------------------------------------------

    public function test_inscription_cree_le_compte_et_renvoie_un_jeton(): void
    {
        $response = $this->postJson('/api/register', [
            'email' => 'alice@example.test',
            'password' => 'MotDePasse123!',
            'firstname' => 'Alice',
            'lastname' => "  de la FONTAINE-o'neil ",
            'phone' => '+33612345678',
            'device_name' => 'iPhone',
        ])->assertCreated()->assertJsonStructure(['token', 'user' => ['id', 'email']]);

        $user = UserModel::where('email', 'alice@example.test')->first();
        $this->assertSame("De La Fontaine-O'Neil", $user->lastname);
        $this->assertSame('player', $user->role);
        $this->assertTrue(Hash::check('MotDePasse123!', $user->password));
        $this->assertNotNull(PersonalAccessToken::findToken($response->json('token')));
        $this->assertArrayNotHasKey('password', $response->json('user'));
    }

    public function test_inscription_validation(): void
    {
        $this->makeUser('Bob', ['email' => 'bob@example.test', 'phone' => '+33600000009']);
        $valid = [
            'email' => 'new@example.test', 'password' => 'MotDePasse123!', 'firstname' => 'N', 'lastname' => 'L',
            'phone' => '+33612345678', 'device_name' => 'x',
        ];

        $this->postJson('/api/register', [])->assertStatus(422)
            ->assertJsonStructure(['errors' => ['email', 'password', 'firstname', 'lastname', 'phone', 'device_name']]);
        $this->postJson('/api/register', ['email' => 'bob@example.test'] + $valid)->assertStatus(422)->assertJsonStructure(['errors' => ['email']]);
        $this->postJson('/api/register', ['phone' => '+33600000009'] + $valid)->assertStatus(422)
            ->assertJsonPath('errors.phone.0', 'Ce numéro de téléphone est déjà utilisé.');
        $this->postJson('/api/register', ['phone' => 'abc'] + $valid)->assertStatus(422)
            ->assertJsonPath('errors.phone.0', 'Le format du numéro de téléphone est invalide.');
        $this->assertSame(1, UserModel::count());
    }

    // --- Connexion / déconnexion ----------------------------------------------------------

    public function test_connexion_par_email(): void
    {
        $user = $this->makeUser('Alice', ['email' => 'alice@example.test', 'password' => 'secret123']);

        $this->postJson('/api/login', ['email' => 'alice@example.test', 'password' => 'secret123', 'device_name' => 'iPhone'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'email', 'firstname', 'lastname', 'phone', 'role']])
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_connexion_refusee(): void
    {
        $this->makeUser('Alice', ['email' => 'alice@example.test', 'password' => 'secret123']);

        $this->postJson('/api/login', ['email' => 'alice@example.test', 'password' => 'faux', 'device_name' => 'x'])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['email']]);
        $this->postJson('/api/login', ['email' => 'inconnu@example.test', 'password' => 'secret123', 'device_name' => 'x'])
            ->assertStatus(422);
        $this->postJson('/api/login', ['email' => 'pas-un-email'])->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password', 'device_name']);
    }

    public function test_deconnexion_revoque_le_jeton_courant(): void
    {
        $user = $this->makeUser('Alice');
        $token = $user->createToken('app')->plainTextToken;
        $other = $user->createToken('autre')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertOk()->assertJson(['message' => 'Successfully logged out']);

        $this->assertNull(PersonalAccessToken::findToken($token));
        $this->assertNotNull(PersonalAccessToken::findToken($other));
        // Le jeton révoqué ne permet plus de s'authentifier
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->postJson('/api/logout')->assertUnauthorized();
    }

    public function test_rafraichir_le_jeton(): void
    {
        $this->postJson('/api/auth/refresh')->assertUnauthorized();

        $user = $this->makeUser('Alice');
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/refresh')
            ->assertOk()
            ->assertJson(['success' => true, 'data' => ['user' => ['id' => $user->id, 'firstname' => 'Alice', 'avatar' => null]]]);
        $this->assertNotNull(PersonalAccessToken::findToken($response->json('data.token')));
    }

    // --- Mot de passe oublié --------------------------------------------------------------

    public function test_lien_de_reinitialisation_envoye(): void
    {
        Notification::fake();
        $user = $this->makeUser('Alice', ['email' => 'alice@example.test']);

        $this->postJson('/api/forgot-password', ['email' => 'alice@example.test'])->assertOk()->assertJsonStructure(['status']);
        Notification::assertSentTo($user, ResetPassword::class);

        // Route web équivalente (invité)
        $this->travel(2)->minutes(); // throttle du broker : 60 s entre deux liens
        $this->postJson('/forgot-password', ['email' => 'alice@example.test'])->assertOk();
    }

    public function test_lien_de_reinitialisation_erreurs(): void
    {
        Notification::fake();
        $this->postJson('/api/forgot-password', [])->assertStatus(422)->assertJsonValidationErrors('email');
        $this->postJson('/api/forgot-password', ['email' => 'inconnu@example.test'])->assertStatus(422)->assertJsonValidationErrors('email');
        Notification::assertNothingSent();
    }

    public function test_reinitialiser_le_mot_de_passe(): void
    {
        $user = $this->makeUser('Alice', ['email' => 'alice@example.test']);
        $token = Password::createToken($user);

        $this->postJson('/api/reset-password', [
            'token' => 'mauvais', 'email' => 'alice@example.test', 'password' => 'NouveauMdp123!', 'password_confirmation' => 'NouveauMdp123!',
        ])->assertStatus(422)->assertJsonValidationErrors('email');

        $this->postJson('/api/reset-password', [
            'token' => $token, 'email' => 'alice@example.test', 'password' => 'NouveauMdp123!', 'password_confirmation' => 'autre',
        ])->assertStatus(422)->assertJsonValidationErrors('password');

        $this->postJson('/api/reset-password', [
            'token' => $token, 'email' => 'alice@example.test', 'password' => 'NouveauMdp123!', 'password_confirmation' => 'NouveauMdp123!',
        ])->assertOk()->assertJsonStructure(['status']);

        $this->assertTrue(Hash::check('NouveauMdp123!', $user->fresh()->password));
    }

    public function test_reinitialiser_le_mot_de_passe_route_web(): void
    {
        $user = $this->makeUser('Alice', ['email' => 'alice@example.test']);
        $token = Password::createToken($user);

        $this->postJson('/reset-password', [
            'token' => $token, 'email' => 'alice@example.test', 'password' => 'NouveauMdp123!', 'password_confirmation' => 'NouveauMdp123!',
        ])->assertOk();
        $this->assertTrue(Hash::check('NouveauMdp123!', $user->fresh()->password));
    }

    public function test_routes_web_invite_et_authentifie(): void
    {
        $user = $this->makeUser('Alice');

        // Déconnexion web sans session : redirigé / refusé
        $this->postJson('/logout')->assertUnauthorized();

        // Connecté (session web) : les routes « guest » refusent
        $this->actingAs($user, 'web')->postJson('/forgot-password', ['email' => $user->email])->assertRedirect();
    }

    // --- Vérification d'e-mail (retirée : auth par téléphone) ---------------------------------

    public function test_les_routes_de_verification_d_email_n_existent_plus(): void
    {
        $user = $this->makeUser('Alice', ['email' => 'alice@example.test']);
        $hash = sha1($user->email);

        $this->actingAs($user)->getJson("/api/verify-email/{$user->id}/{$hash}")->assertNotFound();
        $this->actingAs($user)->postJson('/api/email/verification-notification')->assertNotFound();
        $this->actingAs($user)->getJson("/verify-email/{$user->id}/{$hash}")->assertNotFound();
        $this->actingAs($user)->postJson('/email/verification-notification')->assertNotFound();
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('verification.verify'));
    }
}
