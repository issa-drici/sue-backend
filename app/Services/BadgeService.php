<?php

namespace App\Services;

use App\Models\FriendModel;
use App\Models\SportSessionModel;
use App\Models\SportSessionParticipantModel;
use App\Models\UserBadgeModel;
use App\Models\UserReferralModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Badges (gamification) : calculés à partir de l'activité existante.
 * "Session jouée" = session acceptée (ou organisée), terminée, non annulée, avec une date fixée :
 * l'app ne sait pas qui est vraiment venu le jour J.
 */
class BadgeService
{
    /** Ordre d'affichage, libellés et seuils */
    private const BADGES = [
        'first-session' => ['name' => 'Première session', 'description' => 'Jouer sa première session', 'metric' => 'played', 'target' => 1],
        'regular' => ['name' => 'Habitué', 'description' => 'Jouer 10 sessions', 'metric' => 'played', 'target' => 10],
        'pillar' => ['name' => 'Pilier', 'description' => 'Jouer 50 sessions', 'metric' => 'played', 'target' => 50],
        'organizer' => ['name' => 'Organisateur', 'description' => 'Créer sa première session', 'metric' => 'created', 'target' => 1],
        'captain' => ['name' => 'Capitaine', 'description' => 'Organiser 10 sessions qui ont eu lieu', 'metric' => 'organizedPlayed', 'target' => 10],
        'full-session' => ['name' => 'Session complète', 'description' => 'Organiser une session complète', 'metric' => 'fullSessions', 'target' => 1],
        'recruiter' => ['name' => 'Recruteur', 'description' => 'Faire venir un nouveau joueur avec ton lien', 'metric' => 'referrals', 'target' => 1],
        'ambassador' => ['name' => 'Ambassadeur', 'description' => 'Faire venir 5 nouveaux joueurs avec ton lien', 'metric' => 'referrals', 'target' => 5],
        'multisport' => ['name' => 'Multisport', 'description' => 'Jouer à 3 sports différents', 'metric' => 'sports', 'target' => 3],
        'all-rounder' => ['name' => 'Touche-à-tout', 'description' => 'Jouer à 5 sports différents', 'metric' => 'sports', 'target' => 5],
        'friends' => ['name' => 'Bande de potes', 'description' => 'Avoir 10 amis sur Sue', 'metric' => 'friends', 'target' => 10],
        'streak' => ['name' => 'Fidèle', 'description' => 'Jouer chaque semaine pendant 4 semaines', 'metric' => 'weekStreak', 'target' => 4],
    ];

    /**
     * Badges d'un joueur avec leur progression. Enregistre ceux qui viennent d'être débloqués.
     *
     * @return array<int, array{id: string, name: string, description: string, unlocked: bool, unlockedAt: ?string, progress: int, target: int, seen: bool}>
     */
    public function getBadges(string $userId): array
    {
        $metrics = $this->computeMetrics($userId);
        $stored = UserBadgeModel::where('user_id', $userId)->get()->keyBy('badge');

        $badges = [];
        foreach (self::BADGES as $id => $badge) {
            $progress = $metrics[$badge['metric']];
            $record = $stored->get($id);

            // Un badge débloqué le reste, même si la progression baisse (session annulée, ami retiré)
            if (!$record && $progress >= $badge['target']) {
                $record = UserBadgeModel::create([
                    'id' => (string) Str::uuid(),
                    'user_id' => $userId,
                    'badge' => $id,
                    'unlocked_at' => now(),
                ]);
            }

            $badges[] = [
                'id' => $id,
                'name' => $badge['name'],
                'description' => $badge['description'],
                'unlocked' => (bool) $record,
                'unlockedAt' => $record?->unlocked_at?->toIso8601String(),
                'progress' => min($progress, $badge['target']),
                'target' => $badge['target'],
                'seen' => (bool) $record?->seen_at,
            ];
        }

        return $badges;
    }

    /** Marque la célébration des badges comme vue (elle ne s'affiche qu'une fois) */
    public function markSeen(string $userId, array $badgeIds): void
    {
        UserBadgeModel::where('user_id', $userId)
            ->whereIn('badge', $badgeIds)
            ->whereNull('seen_at')
            ->update(['seen_at' => now()]);
    }

    /**
     * Parrainage : un compte tout neuf qui rejoint une session par un lien compte pour la personne
     * qui a partagé le lien (ou l'organisateur à défaut). Une personne ne compte qu'une fois.
     */
    public function recordReferral(string $referrerId, string $referredId, ?string $sessionId): void
    {
        if ($referrerId === $referredId || UserReferralModel::where('referred_id', $referredId)->exists()) {
            return;
        }

        UserReferralModel::create([
            'id' => (string) Str::uuid(),
            'referrer_id' => $referrerId,
            'referred_id' => $referredId,
            'session_id' => $sessionId,
        ]);
    }

    private function computeMetrics(string $userId): array
    {
        $now = Carbon::now();

        // Sessions jouées : participant accepté (l'organisateur l'est automatiquement), terminées
        $played = SportSessionModel::query()
            ->where('status', 'active')
            ->where('scheduling', 'scheduled')
            ->where('end_date', '<', $now)
            ->whereIn('id', SportSessionParticipantModel::where('user_id', $userId)->where('status', 'accepted')->select('session_id'))
            ->get(['id', 'sport', 'start_date', 'organizer_id']);

        $organized = SportSessionModel::where('organizer_id', $userId)->where('status', 'active');

        $fullSessions = (clone $organized)
            ->whereNotNull('max_participants')
            ->whereRaw('(select count(*) from sport_session_participants p where p.session_id = sport_sessions.id and p.status = ?) >= sport_sessions.max_participants', ['accepted'])
            ->count();

        return [
            'played' => $played->count(),
            'created' => (clone $organized)->count(),
            'organizedPlayed' => $played->where('organizer_id', $userId)->count(),
            'fullSessions' => $fullSessions,
            'referrals' => UserReferralModel::where('referrer_id', $userId)->count(),
            'sports' => $played->pluck('sport')->unique()->count(),
            'friends' => FriendModel::where('user_id', $userId)->count(),
            'weekStreak' => $this->longestWeekStreak($played->pluck('start_date')),
        ];
    }

    /** Plus longue suite de semaines consécutives avec au moins une session jouée */
    private function longestWeekStreak($startDates): int
    {
        $weeks = $startDates
            ->map(fn ($date) => Carbon::parse($date)->setTimezone('Europe/Paris')->startOfWeek()->toDateString())
            ->unique()
            ->sort()
            ->values();

        $longest = 0;
        $current = 0;
        $previous = null;
        foreach ($weeks as $week) {
            $current = ($previous && Carbon::parse($previous)->addWeek()->toDateString() === $week) ? $current + 1 : 1;
            $longest = max($longest, $current);
            $previous = $week;
        }

        return $longest;
    }
}
