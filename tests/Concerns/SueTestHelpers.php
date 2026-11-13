<?php

namespace Tests\Concerns;

use App\Models\FriendModel;
use App\Models\FriendRequestModel;
use App\Models\PhoneVerificationCodeModel;
use App\Models\PushTokenModel;
use App\Models\SportSessionModel;
use App\Models\SportSessionParticipantModel;
use App\Models\UserModel;
use App\Services\ExpoPushNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Aides communes aux tests : aucun appel externe (push Expo, HTTP), création rapide
 * d'utilisateurs, de sessions et d'amitiés.
 *
 * Laravel appelle automatiquement setUpSueTestHelpers() au démarrage de chaque test.
 */
trait SueTestHelpers
{
    /** Pushs « envoyés », par jeton : [token => [['title' =>, 'body' =>, 'data' =>]]] */
    protected array $pushes = [];

    protected function setUpSueTestHelpers(): void
    {
        // Aucun appel HTTP réel (Socket.IO, Telegram, SMS…) : toute requête non simulée explicitement
        // par le test lève une exception. Http::fake([]) n'enregistre aucune réponse par défaut, pour
        // que les Http::fake([...]) des tests restent prioritaires.
        Http::fake([]);
        Http::preventStrayRequests();

        // Jamais d'identifiants réels issus du .env local : SMS journalisés, alertes désactivées,
        // pas de compte de démo
        config([
            'services.smsgate.username' => null,
            'services.smsgate.password' => null,
            'services.telegram.bot_token' => null,
            'services.telegram.chat_id' => null,
            'services.review_account.phone' => null,
            'services.review_account.code' => null,
        ]);

        $pushes = &$this->pushes;
        $this->app->instance(ExpoPushNotificationService::class, new class($pushes) extends ExpoPushNotificationService {
            public function __construct(private array &$pushes) {}

            public function sendNotification(array $tokens, string $title, string $body, array $data = []): array
            {
                foreach ($tokens as $token) {
                    $this->pushes[$token][] = ['title' => $title, 'body' => $body, 'data' => $data];
                }
                return ['success' => true, 'data' => []];
            }
        });
    }

    /**
     * Marque un test révélant un bug connu (le test reste dans la suite, sans la casser).
     * SUE_VERIFY_BUGS=1 exécute quand même ces tests pour vérifier qu'ils échouent bien.
     */
    protected function knownBug(string $explanation): void
    {
        if (!getenv('SUE_VERIFY_BUGS')) {
            $this->markTestIncomplete('BUG: ' . $explanation);
        }
    }

    /** Code OTP valide enregistré pour un numéro (comme après un envoi de SMS) */
    protected function storeOtp(string $phone, string $code = '123456', ?\DateTimeInterface $verifiedAt = null, array $overrides = []): PhoneVerificationCodeModel
    {
        return PhoneVerificationCodeModel::updateOrCreate(['phone' => $phone], array_merge([
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
            'verified_at' => $verifiedAt,
            'last_sent_at' => now()->subMinutes(2),
        ], $overrides));
    }

    protected function makeUser(string $firstname = 'User', array $attributes = [], bool $withPushToken = true): UserModel
    {
        $user = UserModel::forceCreate(array_merge([
            'phone' => '+3361' . random_int(1000000, 9999999),
            'firstname' => $firstname,
            'lastname' => 'Test',
            'email' => strtolower($firstname) . '.' . Str::random(6) . '@example.test',
            'password' => 'password',
            'role' => 'player',
        ], $attributes));

        if ($withPushToken) {
            PushTokenModel::create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'token' => $this->tokenOf($user),
                'platform' => 'expo',
                'is_active' => true,
            ]);
        }

        return $user;
    }

    protected function tokenOf(UserModel $user): string
    {
        return 'ExponentPushToken[' . $user->id . ']';
    }

    /** @return array<int, array{title: string, body: string, data: array}> */
    protected function pushesFor(UserModel $user): array
    {
        return $this->pushes[$this->tokenOf($user)] ?? [];
    }

    /**
     * @param array<int, array{0: UserModel, 1: string}> $participants [[user, status], …]
     */
    protected function makeSession(UserModel $organizer, array $participants = [], array $attributes = []): SportSessionModel
    {
        $start = Carbon::now()->addDays(3)->setTime(18, 0);
        $session = SportSessionModel::create(array_merge([
            'id' => (string) Str::uuid(),
            'sport' => 'football',
            'start_date' => $start,
            'end_date' => $start->copy()->addHours(2),
            'location' => 'Five Paris',
            'organizer_id' => $organizer->id,
            'status' => 'active',
            'scheduling' => 'scheduled',
        ], $attributes));

        foreach ([[$organizer, 'accepted'], ...$participants] as [$user, $status]) {
            $this->addParticipant($session, $user, $status);
        }

        return $session;
    }

    protected function addParticipant(SportSessionModel $session, UserModel $user, string $status = 'accepted'): SportSessionParticipantModel
    {
        return SportSessionParticipantModel::create([
            'id' => (string) Str::uuid(),
            'session_id' => $session->id,
            'user_id' => $user->id,
            'status' => $status,
        ]);
    }

    protected function participantStatus(SportSessionModel $session, UserModel $user): ?string
    {
        return SportSessionParticipantModel::where('session_id', $session->id)
            ->where('user_id', $user->id)
            ->value('status');
    }

    protected function befriend(UserModel $a, UserModel $b): void
    {
        FriendModel::create(['id' => (string) Str::uuid(), 'user_id' => $a->id, 'friend_id' => $b->id]);
        FriendModel::create(['id' => (string) Str::uuid(), 'user_id' => $b->id, 'friend_id' => $a->id]);
    }

    protected function friendRequest(UserModel $sender, UserModel $receiver, string $status = 'pending'): FriendRequestModel
    {
        return FriendRequestModel::create([
            'id' => (string) Str::uuid(),
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => $status,
        ]);
    }

    protected function areFriends(UserModel $a, UserModel $b): bool
    {
        return FriendModel::where('user_id', $a->id)->where('friend_id', $b->id)->exists()
            && FriendModel::where('user_id', $b->id)->where('friend_id', $a->id)->exists();
    }
}
