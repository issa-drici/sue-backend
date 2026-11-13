<?php

namespace App\Console\Commands;

use App\Models\FriendModel;
use App\Models\NotificationModel;
use App\Models\SessionSlotAvailabilityModel;
use App\Models\SessionSlotProposalModel;
use App\Models\SportSessionModel;
use App\Models\SportSessionParticipantModel;
use App\Models\UserModel;
use App\Services\DateFormatterService;
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

    /** Lien d'invitation fixe de la session de volley (ouvert par les tests Maestro) */
    public const E2E_JOIN_TOKEN = 'e2eDemoJoinLinkVolleyHugo000000000000000';

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
            // Nom remis à chaque connexion : un changement fait pendant une démo ne reste pas
            $me = UserModel::firstOrCreate(
                ['phone' => $phone],
                ['firstname' => 'Alex', 'lastname' => 'Martin', 'email' => null, 'password' => null, 'role' => 'player']
            );
            $me->update(['firstname' => 'Alex', 'lastname' => 'Martin']);

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

            // Invitation à confirmer : padel organisé par Lucas
            $padel = $this->session($lucas, 'padel', 'Padel Club Le Havre', 3, '20:30', 90, 4, 12.5, [
                [$sarah, 'accepted'], [$me, 'pending'],
            ]);

            // Session à voter : basket, dont un créneau proposé par Camille.
            // Timeline : créneaux créés d'abord (antédatés), puis réponses des 4 invités
            // → 4/4 ont répondu, cohérent avec les dispos affichées sous chaque créneau.
            // (Antédater slot_responded_at APRÈS les créneaux faisait hasNewSlots=true pour tous
            //  → "0/4 a répondu" alors que Thomas/Yanis/Hugo apparaissaient déjà en dispos.)
            $basket = $this->session($me, 'basketball', 'Gymnase Langevin', 4, '19:00', 90, 10, null, [
                [$thomas, 'pending'], [$camille, 'pending'], [$yanis, 'pending'], [$hugo, 'pending'],
            ], 'finding_slot');
            $this->slot($basket, 4, '19:00', 90, null, [$thomas, $yanis, $hugo]);
            $this->slot($basket, 5, '20:00', 90, null, [$thomas]);
            $this->slot($basket, 6, '18:30', 90, $camille, [$camille, $hugo]);
            $this->syncFindingSlotRange($basket);
            SessionSlotProposalModel::where('session_id', $basket->id)->update([
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ]);
            SportSessionParticipantModel::where('session_id', $basket->id)
                ->whereIn('user_id', [$thomas->id, $camille->id, $yanis->id, $hugo->id])
                ->update(['slot_responded_at' => now()->subHour()]);

            // À voter côté invité : tennis organisé par Yanis, le compte de démo n'a pas encore
            // donné ses disponibilités
            $tennis = $this->session($yanis, 'tennis', 'Tennis Club du Havre', 8, '18:00', 60, 4, null, [
                [$me, 'pending'], [$ines, 'pending'],
            ], 'finding_slot');
            $this->slot($tennis, 8, '18:00', 60, null, [$ines]);
            $this->slot($tennis, 9, '12:30', 60, null, []);
            $this->syncFindingSlotRange($tennis);
            SessionSlotProposalModel::where('session_id', $tennis->id)->update([
                'created_at' => now()->subHours(3),
                'updated_at' => now()->subHours(3),
            ]);
            SportSessionParticipantModel::where('session_id', $tennis->id)
                ->where('user_id', $ines->id)
                ->update(['slot_responded_at' => now()->subHours(2)]);

            // Lien d'invitation (tests de bout en bout) : volley organisé par Hugo, sans le compte de
            // démo, avec un lien fixe que le test ouvre pour rejoindre la session
            $volley = $this->session($hugo, 'volleyball', 'Plage du Havre', 7, '17:00', 90, 6, null, [
                [$lea, 'accepted'],
            ]);
            $volley->update(['share_token' => self::E2E_JOIN_TOKEN]);

            // Running organisé par Camille, sans limite de joueurs
            $this->session($camille, 'course', 'Plage du Havre', 6, '09:30', 60, null, null, [
                [$me, 'accepted'], [$lea, 'accepted'], [$ines, 'accepted'],
            ]);

            // Historique
            $this->session($me, 'tennis', 'Tennis Club du Havre', -5, '10:00', 60, 2, null, [[$sarah, 'accepted']]);
            $this->session($thomas, 'football', 'Five du Havre', -8, '19:00', 90, 10, 8.0, [
                [$me, 'accepted'], [$lucas, 'accepted'], [$yanis, 'accepted'], [$hugo, 'accepted'],
            ]);

            $camilleSlotLabel = DateFormatterService::formatSlotLabel([
                'date' => today('Europe/Paris')->addDays(6)->format('Y-m-d'),
                'startTime' => '18:30',
            ]);

            NotificationModel::where('user_id', $me->id)->delete();
            $this->notify($me, $five, 'update', 'Léa a rejoint ta session', 'Five du Havre demain à 19h : 8/10 joueurs', 25);
            $this->notify($me, $basket, 'update', 'Camille propose un autre créneau', ucfirst($camilleSlotLabel) . " : garde-le ou retire-le, c'est toi qui confirmes.", 120);
            $this->notify($me, $padel, 'invitation', "Lucas t'invite à une session de padel", 'Padel Club Le Havre', 240);
        });

        $this->info('Compte de démo prêt.');
        return self::SUCCESS;
    }

    /** Supprime les sessions organisées par les comptes de démo (et ce qui en dépend) */
    private function clearDemoSessions(array $users): void
    {
        $sessionIds = SportSessionModel::whereIn('organizer_id', array_map(fn ($u) => $u->id, $users))->pluck('id');
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

    /** Comme create()/updateSlotProposals() : plage = premier début / dernière fin des créneaux. */
    private function syncFindingSlotRange(SportSessionModel $session): void
    {
        $slots = SessionSlotProposalModel::where('session_id', $session->id);
        $session->update([
            'start_date' => (clone $slots)->min('start_date'),
            'end_date' => (clone $slots)->max('end_date'),
        ]);
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
