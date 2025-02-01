<?php

namespace App\UseCases\Home;

use App\Repositories\UserProfile\UserProfileRepositoryInterface;
use App\Repositories\UserExercise\UserExerciseRepositoryInterface;
use App\Repositories\Exercise\ExerciseRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class FindHomeDataUseCase
{
    public function __construct(
        private UserProfileRepositoryInterface $userProfileRepository,
        private UserExerciseRepositoryInterface $userExerciseRepository,
        private ExerciseRepositoryInterface $exerciseRepository
    ) {}

    public function execute(): array
    {
        $user = Auth::user();
        if (!$user) {
            throw ValidationException::withMessages([
                'auth' => ['Utilisateur non authentifié']
            ]);
        }

        // Récupération des stats depuis user_profiles
        $stats = $this->userProfileRepository->findByUserId($user->id);
        if (!$stats) {
            $stats = [
                'total_training_time' => 0,
                'total_xp' => 0,
                'completed_videos' => 0
            ];
        }

        // Récupération des derniers exercices
        $recentExercises = $this->userExerciseRepository->findRecent($user->id, 3);
        $exerciseIds = array_column($recentExercises, 'exercise_id');
        $exercises = $this->exerciseRepository->findByIds($exerciseIds);

        // Formatage des exercices récents
        $recentExercises = array_map(function ($recent) use ($exercises) {
            $exercise = $exercises[array_search($recent['exercise_id'], array_column($exercises, 'id'))];
            return [
                'id' => $exercise['id'],
                'title' => $exercise['title'],
                'completed_at' => $recent['completed_at'],
                'updated_at' => $recent['updated_at'],
                'banner_url' => $exercise['banner_url']
            ];
        }, $recentExercises);

        return [
            'stats' => [
                'total_training_time' => $stats->getTotalTrainingTime(),
                'total_xp' => $stats->getTotalXp(),
                'completed_videos' => $stats->getCompletedVideos()
            ],
            'recent_exercises' => $recentExercises
        ];
    }
} 