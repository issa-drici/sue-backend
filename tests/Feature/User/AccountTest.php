<?php

namespace Tests\Feature\User;

use App\Models\FileModel;
use App\Models\PhoneVerificationCodeModel;
use App\Models\SportSessionModel;
use App\Models\SupportRequestModel;
use App\Models\UserModel;
use App\Models\UserProfileModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\SueTestHelpers;
use Tests\TestCase;

/**
 * Compte : profil, e-mail, mot de passe, avatar, suppression, contacts, changement de numéro.
 */
class AccountTest extends TestCase
{
    use RefreshDatabase, SueTestHelpers;

    public function test_routes_du_compte_exigent_une_authentification(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
        $this->getJson('/api/profile')->assertUnauthorized();
        $this->getJson('/api/users/profile')->assertUnauthorized();
        $this->putJson('/api/users/profile', [])->assertUnauthorized();
        $this->postJson('/api/profile/avatar')->assertUnauthorized();
        $this->putJson('/api/profile/avatar')->assertUnauthorized();
        $this->postJson('/api/users/update-email')->assertUnauthorized();
        $this->postJson('/api/users/update-password')->assertUnauthorized();
        $this->deleteJson('/api/users')->assertUnauthorized();
        $this->deleteJson('/api/users/data')->assertUnauthorized();
        $this->postJson('/api/users/check-contacts')->assertUnauthorized();
        $this->postJson('/api/users/phone/send-otp')->assertUnauthorized();
        $this->postJson('/api/users/phone/verify-current')->assertUnauthorized();
        $this->postJson('/api/users/phone/new/send-otp')->assertUnauthorized();
        $this->postJson('/api/users/phone/new/verify')->assertUnauthorized();
    }

    public function test_api_user_renvoie_l_utilisateur_connecte_sans_mot_de_passe(): void
    {
        $user = $this->makeUser('Alice');
        $this->actingAs($user)->getJson('/api/user')
            ->assertOk()
            ->assertJson(['id' => $user->id, 'firstname' => 'Alice'])
            ->assertJsonMissingPath('password');
    }

    // --- Profil ---------------------------------------------------------------------------

    public function test_profil_renvoie_les_donnees_et_les_statistiques(): void
    {
        $user = $this->makeUser('Alice', ['sports_preferences' => ['tennis']]);
        $other = $this->makeUser('Bob');
        $this->makeSession($user);                                    // créée + participation acceptée
        $this->makeSession($user, [], ['status' => 'cancelled']);   // annulée : non comptée comme créée
        $this->makeSession($other, [[$user, 'accepted']]);
        $this->makeSession($other, [[$user, 'pending']]);

        foreach (['/api/profile', '/api/users/profile'] as $url) {
            $this->actingAs($user)->getJson($url)
                ->assertOk()
                ->assertJson(['success' => true, 'data' => [
                    'id' => $user->id,
                    'firstname' => 'Alice',
                    'phone' => $user->phone,
                    'avatar' => null,
                    'sports_preferences' => ['tennis'],
                    'stats' => ['sessionsCreated' => 1, 'sessionsParticipated' => 3],
                ]]);
        }
    }

    public function test_mise_a_jour_du_profil(): void
    {
        $user = $this->makeUser('Alice');

        $this->actingAs($user)->putJson('/api/users/profile', ['firstname' => '  Alicia ', 'lastname' => 'Martin'])
            ->assertOk()
            ->assertJson(['success' => true, 'data' => ['firstname' => 'Alicia', 'lastname' => 'Martin']]);

        $this->assertSame('Alicia', $user->fresh()->firstname);
    }

    public function test_mise_a_jour_du_profil_ignore_les_valeurs_invalides(): void
    {
        $user = $this->makeUser('Alice');

        // Prénom trop court, nom trop long : ignorés (pas d'erreur, rien ne change)
        $this->actingAs($user)->putJson('/api/users/profile', ['firstname' => 'A', 'lastname' => str_repeat('x', 101)])
            ->assertOk()->assertJsonPath('data.firstname', 'Alice')->assertJsonPath('data.lastname', 'Test');
    }

    // --- E-mail / mot de passe -------------------------------------------------------------

    public function test_changer_d_email(): void
    {
        $user = $this->makeUser('Alice', ['email' => 'alice@example.test']);
        $this->makeUser('Bob', ['email' => 'bob@example.test']);

        $this->actingAs($user)->postJson('/api/users/update-email', [])
            ->assertStatus(400)->assertJsonPath('error.code', 'VALIDATION_ERROR');
        $this->actingAs($user)->postJson('/api/users/update-email', ['currentEmail' => 'wrong@example.test', 'newEmail' => 'new@example.test'])
            ->assertStatus(400)->assertJsonPath('error.message', 'Email actuel incorrect');
        $this->actingAs($user)->postJson('/api/users/update-email', ['currentEmail' => 'alice@example.test', 'newEmail' => 'pas-un-email'])
            ->assertStatus(400)->assertJsonPath('error.message', "Format d'email invalide");
        $this->actingAs($user)->postJson('/api/users/update-email', ['currentEmail' => 'alice@example.test', 'newEmail' => 'bob@example.test'])
            ->assertStatus(409)->assertJsonPath('error.code', 'EMAIL_ALREADY_EXISTS');

        $this->actingAs($user)->postJson('/api/users/update-email', ['currentEmail' => 'alice@example.test', 'newEmail' => 'alice2@example.test'])
            ->assertOk()->assertJson(['success' => true]);
        $this->assertSame('alice2@example.test', $user->fresh()->email);
    }

    public function test_changer_de_mot_de_passe(): void
    {
        $user = $this->makeUser('Alice', ['password' => 'ancien-mdp']);

        $this->actingAs($user)->postJson('/api/users/update-password', ['currentPassword' => 'ancien-mdp'])
            ->assertStatus(400)->assertJsonPath('error.code', 'VALIDATION_ERROR');
        $this->actingAs($user)->postJson('/api/users/update-password', ['currentPassword' => 'faux', 'newPassword' => 'nouveau-mdp'])
            ->assertStatus(400)->assertJsonPath('error.code', 'INVALID_CURRENT_PASSWORD');
        $this->actingAs($user)->postJson('/api/users/update-password', ['currentPassword' => 'ancien-mdp', 'newPassword' => '123'])
            ->assertStatus(400)->assertJsonPath('error.code', 'VALIDATION_ERROR');

        $this->actingAs($user)->postJson('/api/users/update-password', ['currentPassword' => 'ancien-mdp', 'newPassword' => 'nouveau-mdp'])
            ->assertOk()->assertJson(['success' => true]);
        $this->assertTrue(Hash::check('nouveau-mdp', $user->fresh()->password));
    }

    // --- Avatar ---------------------------------------------------------------------------

    public function test_upload_d_avatar_cree_le_fichier_et_remplace_l_ancien(): void
    {
        Storage::fake('s3');
        $user = $this->makeUser('Alice');

        $first = $this->actingAs($user)->post('/api/profile/avatar', ['avatar' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonStructure(['avatar_url'])->json('avatar_url');
        $firstFile = FileModel::first();
        Storage::disk('s3')->assertExists($firstFile->path);
        $this->assertSame($firstFile->id, UserProfileModel::where('user_id', $user->id)->value('avatar_file_id'));

        // PUT fonctionne aussi ; l'ancien fichier est supprimé (base + stockage)
        $this->actingAs($user)->put('/api/profile/avatar', ['avatar' => UploadedFile::fake()->image('b.png')], ['Accept' => 'application/json'])
            ->assertOk();
        $this->assertSame(1, FileModel::count());
        $this->assertNull(FileModel::find($firstFile->id));
        Storage::disk('s3')->assertMissing($firstFile->path);

        $this->actingAs($user)->getJson('/api/profile')->assertJsonPath('data.avatar', FileModel::first()->url);
        $this->assertNotSame($first, FileModel::first()->url);
    }

    public function test_upload_d_avatar_valide_le_fichier(): void
    {
        Storage::fake('s3');
        $user = $this->makeUser('Alice');

        $this->actingAs($user)->postJson('/api/profile/avatar', [])->assertStatus(422)->assertJsonValidationErrors('avatar');
        $this->actingAs($user)->post('/api/profile/avatar', ['avatar' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertStatus(422);
        $this->assertSame(0, FileModel::count());
    }

    // --- Suppression ---------------------------------------------------------------------

    public function test_supprimer_son_compte_exige_le_code_sms(): void
    {
        $user = $this->makeUser('Alice');

        $this->actingAs($user)->deleteJson('/api/users')->assertStatus(422);
        $this->actingAs($user)->deleteJson('/api/users', ['code' => '000000'])->assertStatus(400); // aucun code envoyé

        $this->storeOtp($user->phone, '123456');
        $this->actingAs($user)->deleteJson('/api/users', ['code' => '654321'])->assertStatus(400);
        $this->assertNotNull(UserModel::find($user->id));
    }

    public function test_supprimer_son_compte_avec_le_bon_code_supprime_tout(): void
    {
        $user = $this->makeUser('Alice');
        $friend = $this->makeUser('Bob');
        $this->befriend($user, $friend);
        $session = $this->makeSession($user, [[$friend, 'accepted']]);
        $this->storeOtp($user->phone, '123456');

        $this->actingAs($user)->deleteJson('/api/users', ['code' => '123456'])
            ->assertOk()->assertJson(['success' => true]);

        $this->assertNull(UserModel::find($user->id));
        $this->assertNull(SportSessionModel::find($session->id)); // cascade : sessions organisées
        $this->assertDatabaseCount('friends', 0);
        $this->assertSame(0, PhoneVerificationCodeModel::where('phone', $user->phone)->count()); // code consommé
        $this->assertNotNull(UserModel::find($friend->id));
    }

    public function test_supprimer_ses_donnees(): void
    {
        $user = $this->makeUser('Alice');
        SupportRequestModel::create(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'message' => 'Aide']);
        FileModel::create(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'path' => 'p', 'url' => 'https://x.test/p', 'mime_type' => 'image/png']);

        $this->actingAs($user)->deleteJson('/api/users/data')->assertOk()->assertJsonStructure(['message']);

        $this->assertNull(UserModel::find($user->id));
        $this->assertSame(0, SupportRequestModel::count());
        $this->assertSame(0, FileModel::count());
    }

    // --- Contacts --------------------------------------------------------------------------

    public function test_verifier_ses_contacts(): void
    {
        $me = $this->makeUser('Me', ['phone' => '+33600000001']);
        $friend = $this->makeUser('Ami', ['phone' => '+33600000002']);
        $pending = $this->makeUser('Attente', ['phone' => '+33600000003']);
        $other = $this->makeUser('Autre', ['phone' => '+33600000004']);
        $this->befriend($me, $friend);
        $this->friendRequest($pending, $me);

        $data = $this->actingAs($me)->postJson('/api/users/check-contacts', [
            'phoneNumbers' => ['06 00 00 00 02', '+33600000003', '33600000004', '0699999999'],
        ])->assertOk()->assertJsonPath('success', true)->json('data');

        $this->assertCount(4, $data);
        $this->assertSame('06 00 00 00 02', $data[0]['phoneNumber']);
        $this->assertTrue($data[0]['isRegistered']);
        $this->assertSame($friend->id, $data[0]['user']['id']);
        $this->assertTrue($data[0]['user']['relationship']['isFriend']);
        $this->assertTrue($data[1]['user']['relationship']['hasPendingRequest']);
        $this->assertFalse($data[1]['user']['relationship']['isFriend']);
        $this->assertSame($other->id, $data[2]['user']['id']);
        $this->assertFalse($data[2]['user']['relationship']['hasPendingRequest']);
        $this->assertSame(['phoneNumber' => '0699999999', 'isRegistered' => false], $data[3]);
    }

    public function test_verifier_ses_contacts_validation(): void
    {
        $me = $this->makeUser('Me');
        $this->actingAs($me)->postJson('/api/users/check-contacts', [])->assertStatus(422)->assertJsonPath('success', false);
        $this->actingAs($me)->postJson('/api/users/check-contacts', ['phoneNumbers' => []])->assertStatus(422);
        $this->actingAs($me)->postJson('/api/users/check-contacts', ['phoneNumbers' => 'nope'])->assertStatus(422);
        $this->actingAs($me)->postJson('/api/users/check-contacts', ['phoneNumbers' => [str_repeat('1', 21)]])->assertStatus(422);
    }

    // --- Changement de numéro -------------------------------------------------------------

    public function test_envoi_du_code_sur_le_numero_actuel(): void
    {
        $user = $this->makeUser('Alice', ['phone' => '+33611111111']);

        $this->actingAs($user)->postJson('/api/users/phone/send-otp')->assertOk()->assertJson(['success' => true]);
        $this->assertSame(1, PhoneVerificationCodeModel::where('phone', '+33611111111')->count());

        // Renvoi immédiat : délai de 45 s
        $this->actingAs($user)->postJson('/api/users/phone/send-otp')->assertStatus(429)->assertJsonStructure(['retry_after']);
    }

    public function test_envoi_du_code_sur_un_numero_etranger_est_refuse(): void
    {
        $this->actingAs($this->makeUser('Foreign', ['phone' => '+14155550100']))
            ->postJson('/api/users/phone/send-otp')->assertStatus(422)->assertJsonStructure(['errors' => ['phone']]);
    }

    public function test_verification_du_numero_actuel(): void
    {
        $user = $this->makeUser('Alice', ['phone' => '+33611111111']);
        $this->storeOtp('+33611111111', '123456');

        $this->actingAs($user)->postJson('/api/users/phone/verify-current', ['code' => 'abc'])->assertStatus(422);
        $this->actingAs($user)->postJson('/api/users/phone/verify-current', ['code' => '000000'])->assertStatus(400);
        $this->actingAs($user)->postJson('/api/users/phone/verify-current', ['code' => '123456'])->assertOk();
        $this->assertNotNull(PhoneVerificationCodeModel::where('phone', '+33611111111')->value('verified_at'));
    }

    public function test_changement_de_numero_complet(): void
    {
        // Le parcours enchaîne plus de requêtes que le throttle (5/min) n'en autorise
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $user = $this->makeUser('Alice', ['phone' => '+33611111111']);
        $this->makeUser('Bob', ['phone' => '+33622222222']);

        // Numéro actuel pas encore confirmé
        $this->actingAs($user)->postJson('/api/users/phone/new/send-otp', ['phone' => '0633333333'])->assertStatus(401);

        $this->storeOtp('+33611111111', '111111', now());

        $this->actingAs($user)->postJson('/api/users/phone/new/send-otp', [])->assertStatus(422);
        $this->actingAs($user)->postJson('/api/users/phone/new/send-otp', ['phone' => '12'])->assertStatus(422);
        $this->actingAs($user)->postJson('/api/users/phone/new/send-otp', ['phone' => '0611111111'])->assertStatus(422); // déjà le sien
        $this->actingAs($user)->postJson('/api/users/phone/new/send-otp', ['phone' => '0622222222'])->assertStatus(409); // pris

        $this->actingAs($user)->postJson('/api/users/phone/new/send-otp', ['phone' => '06 33 33 33 33'])->assertOk();
        $this->assertSame(1, PhoneVerificationCodeModel::where('phone', '+33633333333')->count());

        $this->storeOtp('+33633333333', '333333');
        $this->actingAs($user)->postJson('/api/users/phone/new/verify', ['phone' => '0633333333', 'code' => '000000'])->assertStatus(400);
        $this->actingAs($user)->postJson('/api/users/phone/new/verify', ['phone' => '0633333333', 'code' => '333333'])
            ->assertOk()->assertJson(['success' => true, 'phone' => '+33633333333']);

        $this->assertSame('+33633333333', $user->fresh()->phone);
        $this->assertSame(0, PhoneVerificationCodeModel::count()); // ancien et nouveau codes consommés
    }

    public function test_confirmation_du_nouveau_numero_exige_la_verification_de_l_actuel(): void
    {
        $user = $this->makeUser('Alice', ['phone' => '+33611111111']);
        $this->storeOtp('+33633333333', '333333');
        // Vérification de l'actuel trop ancienne (> 10 min)
        $this->storeOtp('+33611111111', '111111', now()->subMinutes(11));

        $this->actingAs($user)->postJson('/api/users/phone/new/verify', ['phone' => '0633333333', 'code' => '333333'])->assertStatus(401);
        $this->actingAs($user)->postJson('/api/users/phone/new/verify', ['phone' => '0633333333'])->assertStatus(422);
        $this->assertSame('+33611111111', $user->fresh()->phone);
    }
}
