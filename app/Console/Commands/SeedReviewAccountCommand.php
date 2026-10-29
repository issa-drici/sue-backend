<?php

namespace App\Console\Commands;

use App\Models\FriendModel;
use App\Models\NotificationModel;
use App\Models\SessionSlotAvailabilityModel;
use App\Models\SessionSlotProposalModel;
use App\Models\SportSessionCommentModel;
use App\Models\SportSessionModel;
use App\Models\SportSessionParticipantModel;
use App\Models\UserModel;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Remplit le compte de démo des équipes de review (App Store, Google Play) : quelques amis et
 * sessions à venir, une session à voter, une invitation à confirmer, une session passée.
 *
 * Lancé à chaque demande de code pour le numéro de démo (PhoneOtpService::sendCode) : les dates
 * restent à venir quel que soit le jour de la review. Le compte de démo lui-même n'est jamais
 * supprimé, seules les sessions de démo sont recréées. Les amis de démo ont des numéros de la plage réservée
 * à la fiction (06 39 98 xx xx) et aucun jeton push : personne de réel n'est notifié.
 */
class SeedReviewAccountCommand extends Command
{
    protected $signature = 'demo:review-account';

    protected $description = "Remplit (ou rafraîchit) le compte de démo utilisé par les équipes de review";

    private const FRIENDS = [
        ['Thomas', 'Bernard'], ['Lucas', 'Moreau'], ['Camille', 'Petit'], ['Yanis', 'Haddad'],
        ['Inès', 'Lambert'], ['Hugo', 'Girard'], ['Sarah', 'Dubois'], ['Léa', 'Rousseau'],
    ];

    public function handle(): int
    {
        $phone = config('services.review_account.phone');
        if (!$phone) {
            $this->info('Aucun numéro de démo configuré (REVIEW_PHONE) : rien à faire.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($phone) {
            $me = UserModel::firstOrCreate(
                ['phone' => $phone],
                ['firstname' => 'Alex', 'lastname' => 'Martin', 'email' => null, 'password' => null, 'role' => 'player']
            );

            $friends = [];
            foreach (self::FRIENDS as $i => [$firstname, $lastname]) {
                $friends[] = UserModel::firstOrCreate(
                    ['phone' => '+336399800' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)],
                    ['firstname' => $firstname, 'lastname' => $lastname, 'email' => null, 'password' => null, 'role' => 'player']
                );
            }
            [$thomas, $lucas, $camille, $yanis, $ines, $hugo, $sarah, $lea] = $friends;

            $this->clearDemoSessions(array_merge([$me], $friends));

            FriendModel::where('user_id', $me->id)->orWhere('friend_id', $me->id)->delete();
            foreach ($friends as $friend) {
                FriendModel::create(['id' => Str::uuid(), 'user_id' => $me->id, 'friend_id' => $friend->id]);
                FriendModel::create(['id' => Str::uuid(), 'user_id' => $friend->id, 'friend_id' => $me->id]);
            }

            // Five de demain organisé par le compte de démo : il manque 2 joueurs
            $five = $this->session($me, 'football', 'Five du Havre', 1, '19:00', 90, 10, 8.0, [
                [$thomas, 'accepted'], [$lucas, 'accepted'], [$camille, 'accepted'], [$yanis, 'accepted'],
                [$ines, 'accepted'], [$hugo, 'accepted'], [$lea, 'accepted'], [$sarah, 'pending'],
            ]);
            $this->addComment($five, $thomas, 'Je ramène le ballon ⚽', 180);
            $this->addComment($five, $hugo, 'Qui a des chasubles ?', 95);
            $this->addComment($five, $me, "Moi ! Rendez-vous 18h50 devant l'entrée", 60);

            // Invitation à confirmer : padel organisé par Lucas
            $this->session($lucas, 'padel', 'Padel Club Le Havre', 3, '20:30', 90, 4, 12.5, [
                [$sarah, 'accepted'], [$me, 'pending'],
            ]);

            // Session à voter : basket, dont un créneau proposé par Camille
            $basket = $this->session($me, 'basketball', 'Gymnase Langevin', 4, '19:00', 90, 10, null, [
                [$thomas, 'pending'], [$camille, 'pending'], [$yanis, 'pending'], [$hugo, 'pending'],
            ], 'finding_slot');
            $this->slot($basket, 4, '19:00', 90, null, [$thomas, $yanis, $hugo]);
            $this->slot($basket, 5, '20:00', 90, null, [$thomas]);
            $this->slot($basket, 6, '18:30', 90, $camille, [$camille, $hugo]);
            SportSessionParticipantModel::where('session_id', $basket->id)
                ->whereIn('user_id', [$thomas->id, $camille->id, $yanis->id, $hugo->id])
                ->update(['slot_responded_at' => now()->subHours(2)]);

            // Running organisé par Camille, sans limite de joueurs
            $this->session($camille, 'course', 'Plage du Havre', 6, '09:30', 60, null, null, [
                [$me, 'accepted'], [$lea, 'accepted'], [$ines, 'accepted'],
            ]);

            // Historique
            $this->session($me, 'tennis', 'Tennis Club du Havre', -5, '10:00', 60, 2, null, [[$sarah, 'accepted']]);
            $this->session($thomas, 'football', 'Five du Havre', -8, '19:00', 90, 10, 8.0, [
                [$me, 'accepted'], [$lucas, 'accepted'], [$yanis, 'accepted'], [$hugo, 'accepted'],
            ]);

            NotificationModel::where('user_id', $me->id)->delete();
            $this->notify($me, $five, 'session_update', 'Léa a rejoint ta session', 'Five du Havre demain à 19h : 8/10 joueurs', 25);
            $this->notify($me, $basket, 'update', 'Camille propose un autre créneau', "Garde-le ou retire-le, c'est toi qui confirmes.", 120);
            $this->notify($me, null, 'invitation', "Lucas t'invite à une session de padel", 'Padel Club Le Havre', 240);
        });

        $this->info('Compte de démo prêt.');
        return self::SUCCESS;
    }

    /** Supprime les sessions organisées par les comptes de démo (et ce qui en dépend) */
    private function clearDemoSessions(array $users): void
    {
        $sessionIds = SportSessionModel::whereIn('organizer_id', array_map(fn ($u) => $u->id, $users))->pluck('id');
        SportSessionCommentModel::whereIn('session_id', $sessionIds)->delete();
        NotificationModel::whereIn('session_id', $sessionIds)->delete();
        SportSessionParticipantModel::whereIn('session_id', $sessionIds)->delete();
        SessionSlotProposalModel::whereIn('session_id', $sessionIds)->delete();
        SportSessionModel::whereIn('id', $sessionIds)->delete();
    }

    private function parisStart(int $days, string $time): Carbon
    {
        return Carbon::parse(today('Europe/Paris')->addDays($days)->format('Y-m-d') . " {$time}", 'Europe/Paris');
    }

    /**
     * @param array<int, array{0: UserModel, 1: string}> $participants
     */
    private function session(
        UserModel $organizer,
        string $sport,
        string $location,
        int $days,
        string $time,
        int $duration,
        ?int $max,
        ?float $price,
        array $participants,
        string $scheduling = 'scheduled',
    ): SportSessionModel {
        $start = $this->parisStart($days, $time);
        $session = SportSessionModel::create([
            'id' => Str::uuid(),
            'sport' => $sport,
            'start_date' => $start->copy()->utc(),
            'end_date' => $start->copy()->addMinutes($duration)->utc(),
            'location' => $location,
            'max_participants' => $max,
            'price_per_person' => $price,
            'organizer_id' => $organizer->id,
            'status' => 'active',
            'scheduling' => $scheduling,
            'share_token' => Str::random(40),
        ]);
        foreach ([[$organizer, 'accepted'], ...$participants] as [$user, $status]) {
            SportSessionParticipantModel::create([
                'id' => Str::uuid(),
                'session_id' => $session->id,
                'user_id' => $user->id,
                'status' => $status,
            ]);
        }
        return $session;
    }

    /**
     * @param array<int, UserModel> $available
     */
    private function slot(SportSessionModel $session, int $days, string $time, int $duration, ?UserModel $proposer, array $available): void
    {
        $start = $this->parisStart($days, $time);
        $slot = SessionSlotProposalModel::create([
            'id' => Str::uuid(),
            'session_id' => $session->id,
            'proposed_by' => $proposer?->id,
            'start_date' => $start->copy()->utc(),
            'end_date' => $start->copy()->addMinutes($duration)->utc(),
        ]);
        foreach ($available as $user) {
            SessionSlotAvailabilityModel::create(['id' => Str::uuid(), 'slot_id' => $slot->id, 'user_id' => $user->id]);
        }
    }

    private function addComment(SportSessionModel $session, UserModel $user, string $content, int $minutesAgo): void
    {
        $comment = SportSessionCommentModel::create([
            'session_id' => $session->id,
            'user_id' => $user->id,
            'content' => $content,
            'mentions' => [],
        ]);
        $comment->created_at = now()->subMinutes($minutesAgo);
        $comment->save();
    }

    private function notify(UserModel $user, ?SportSessionModel $session, string $type, string $title, string $message, int $minutesAgo): void
    {
        $notification = NotificationModel::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'session_id' => $session?->id,
            'read' => false,
            'push_sent' => false,
        ]);
        $notification->created_at = now()->subMinutes($minutesAgo);
        $notification->save();
    }
}
