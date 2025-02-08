<?php

namespace App\UseCases\Ranking;

use App\Repositories\UserProfile\UserProfileRepositoryInterface;
use App\Repositories\UserExercise\UserExerciseRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class FindRankingsUseCase
{
    public function __construct(
        private UserProfileRepositoryInterface $userProfileRepository,
        private UserExerciseRepositoryInterface $userExerciseRepository
    ) {}

    public function execute(string $type = 'week'): array
    {
        // Validation du type
        if (!in_array($type, ['day', 'week', 'month'])) {
            throw ValidationException::withMessages([
                'type' => ['Le type doit être day, week ou month']
            ]);
        }

        // Calcul de la période
        $endDate = Carbon::now();
        $startDate = match($type) {
            'day' => $endDate->copy()->startOfDay(),
            'week' => $endDate->copy()->subDays(6)->startOfDay(),
            'month' => $endDate->copy()->subDays(29)->startOfDay(),
        };

        // Récupération de tous les utilisateurs
        $allUsers = $this->userProfileRepository->findAllUsers();
        $userIds = array_column($allUsers, 'user_id');
        
        // Récupération des stats en une seule requête
        $stats = $this->userExerciseRepository->findStatsForUsers($userIds, $startDate, $endDate);
        
        // Fusion des données
        $rankings = array_map(function($user) use ($stats) {
            $userStats = $stats[$user['user_id']] ?? ['total_xp' => 0, 'streak' => 0];
            return [
                'user_id' => $user['user_id'],
                'full_name' => $user['full_name'],
                'total_xp' => $userStats['total_xp'],
                'streak' => $userStats['streak']
            ];
        }, $allUsers);

        // Tri par XP décroissant
        usort($rankings, fn($a, $b) => $b['total_xp'] - $a['total_xp']);

        // Ajout du rang et identification de l'utilisateur courant
        $currentUser = Auth::user();
        $currentUserRank = 0;
        $formattedRankings = [];

        foreach ($rankings as $index => $ranking) {
            $rank = $index + 1;
            $isCurrentUser = $currentUser && $ranking['user_id'] === $currentUser->id;
            
            if ($isCurrentUser) {
                $currentUserRank = $rank;
            }

            $formattedRankings[] = [
                'rank' => $rank,
                'user_id' => $ranking['user_id'],
                'full_name' => $ranking['full_name'],
                'total_xp' => $ranking['total_xp'],
                'streak' => $ranking['streak'],
                'is_current_user' => $isCurrentUser
            ];
        }

        return [
            'current_user_rank' => $currentUserRank,
            'rankings' => $formattedRankings,
            'period' => [
                'type' => $type,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString()
            ]
        ];
    }
} 