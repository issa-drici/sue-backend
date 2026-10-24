<?php

namespace Database\Seeders;

use App\Models\FriendModel;
use App\Models\SportSessionModel;
use App\Models\SportSessionParticipantModel;
use App\Models\UserModel;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Données de démo pour le dev local (simulateur) :
 *   php artisan db:seed --class=LocalDemoSeeder
 *
 * Connexion dans l'app avec le numéro 06 00 00 00 01 : sans clé InfiniReach
 * configurée, le code OTP n'est pas envoyé mais écrit dans storage/logs/laravel.log.
 * Relançable : les données de démo précédentes (numéros +3360000000x) sont recréées.
 */
class LocalDemoSeeder extends Seeder
{
    private const DEMO_PHONE_PREFIX = '+3360000000';

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('LocalDemoSeeder ne doit jamais tourner en production.');
        }

        $this->deleteExistingDemoData();

        $me = $this->user(1, 'Issa', 'Drici');
        $friends = [
            $this->user(2, 'Mohamed', 'Benali'),
            $this->user(3, 'Asmaa', 'Karim'),
            $this->user(4, 'Lucas', 'Martin'),
            $this->user(5, 'Sarah', 'Dubois'),
            $this->user(6, 'Yanis', 'Haddad'),
            $this->user(7, 'Emma', 'Leroy'),
        ];

        foreach ($friends as $friend) {
            $this->befriend($me, $friend);
        }

        // Organisée par moi, demain 18h, 3 confirmés + 1 en attente
        $this->session($me, 'football', 'Five du Havre', 1, '18:00', 90, 10, 8.0, [
            [$friends[0], 'accepted'],
            [$friends[1], 'accepted'],
            [$friends[2], 'accepted'],
            [$friends[3], 'pending'],
        ]);

        // Organisée par un ami, j'y suis invité (en attente de ma réponse)
        $this->session($friends[0], 'padel', 'Padel Club Le Havre', 3, '20:00', 60, 4, 12.5, [
            [$me, 'pending'],
            [$friends[4], 'accepted'],
        ]);

        // Organisée par un ami, j'ai accepté
        $this->session($friends[2], 'musculation', 'Basic Fit Le Havre', 2, '19:00', 60, null, null, [
            [$me, 'accepted'],
        ]);

        // Session passée
        $this->session($me, 'tennis', 'Tennis Club du Havre', -5, '10:00', 60, 2, null, [
            [$friends[5], 'accepted'],
        ]);
    }

    private function deleteExistingDemoData(): void
    {
        $demoUserIds = UserModel::where('phone', 'like', self::DEMO_PHONE_PREFIX . '%')->pluck('id');
        if ($demoUserIds->isEmpty()) {
            return;
        }

        $sessionIds = SportSessionModel::whereIn('organizer_id', $demoUserIds)->pluck('id');
        SportSessionParticipantModel::whereIn('session_id', $sessionIds)
            ->orWhereIn('user_id', $demoUserIds)
            ->delete();
        SportSessionModel::whereIn('id', $sessionIds)->delete();
        FriendModel::whereIn('user_id', $demoUserIds)->orWhereIn('friend_id', $demoUserIds)->delete();
        UserModel::whereIn('id', $demoUserIds)->delete();
    }

    private function user(int $n, string $firstname, string $lastname): UserModel
    {
        return UserModel::create([
            'firstname' => $firstname,
            'lastname' => $lastname,
            'phone' => self::DEMO_PHONE_PREFIX . $n,
            'email' => null,
            'password' => null,
            'role' => 'player',
        ]);
    }

    private function befriend(UserModel $a, UserModel $b): void
    {
        FriendModel::create(['id' => Str::uuid(), 'user_id' => $a->id, 'friend_id' => $b->id]);
        FriendModel::create(['id' => Str::uuid(), 'user_id' => $b->id, 'friend_id' => $a->id]);
    }

    /**
     * @param array<int, array{0: UserModel, 1: string}> $participants
     */
    private function session(
        UserModel $organizer,
        string $sport,
        string $location,
        int $daysFromToday,
        string $startTime,
        int $durationMinutes,
        ?int $maxParticipants,
        ?float $pricePerPerson,
        array $participants,
    ): void {
        // Heures exprimées en heure de Paris, stockées en UTC (comme SportSessionRepository::create)
        $start = Carbon::parse(today('Europe/Paris')->addDays($daysFromToday)->format('Y-m-d') . " {$startTime}", 'Europe/Paris');

        $session = SportSessionModel::create([
            'id' => Str::uuid(),
            'sport' => $sport,
            'start_date' => $start->copy()->utc(),
            'end_date' => $start->copy()->addMinutes($durationMinutes)->utc(),
            'location' => $location,
            'max_participants' => $maxParticipants,
            'price_per_person' => $pricePerPerson,
            'organizer_id' => $organizer->id,
            'status' => 'active',
            'share_token' => Str::random(40),
        ]);

        // Comme SportSessionRepository::create : l'organisateur est participant accepté
        foreach ([[$organizer, 'accepted'], ...$participants] as [$user, $status]) {
            SportSessionParticipantModel::create([
                'id' => Str::uuid(),
                'session_id' => $session->id,
                'user_id' => $user->id,
                'status' => $status,
            ]);
        }
    }
}
