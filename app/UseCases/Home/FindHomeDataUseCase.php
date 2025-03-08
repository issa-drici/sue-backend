<?php

namespace App\UseCases\Home;

use App\Repositories\UserProfile\UserProfileRepositoryInterface;
use App\Repositories\UserExercise\UserExerciseRepositoryInterface;
use App\Repositories\Exercise\ExerciseRepositoryInterface;
use App\Repositories\File\FileRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class FindHomeDataUseCase
{
    public function __construct(
        private UserProfileRepositoryInterface $userProfileRepository,
        private UserExerciseRepositoryInterface $userExerciseRepository,
        private ExerciseRepositoryInterface $exerciseRepository,
        private FileRepositoryInterface $fileRepository
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

        // Regrouper les exercices par exercise_id et garder le plus récent
        $uniqueExercises = [];
        foreach ($recentExercises as $exercise) {
            $exerciseId = $exercise['exercise_id'];
            if (!isset($uniqueExercises[$exerciseId]) ||
                strtotime($exercise['updated_at']) > strtotime($uniqueExercises[$exerciseId]['updated_at'])) {
                $uniqueExercises[$exerciseId] = $exercise;
            }
        }
        $recentExercises = array_values($uniqueExercises);

        // Récupérer les informations des exercices uniques
        $exerciseIds = array_column($recentExercises, 'exercise_id');
        $exercises = $this->exerciseRepository->findByIds($exerciseIds);

        // Formatage des exercices récents
        $recentExercises = array_map(function ($recent) use ($exercises) {
            $exercise = $exercises[array_search($recent['exercise_id'], array_column($exercises, 'id'))];
            return [
                'id' => $exercise['id'],
                'title' => $exercise['title'],
                'description' => $exercise['description'],
                'completed_at' => $recent['completed_at'],
                'updated_at' => $recent['updated_at'],
                'banner_url' => $exercise['banner_url'],
                'duration' => $exercise['duration_seconds']
            ];
        }, $recentExercises);

         // Récupérer l'avatar si présent
         $avatarUrl = null;
         if ($stats && $stats->getAvatarFileId()) {
             $avatarFile = $this->fileRepository->findById($stats->getAvatarFileId());
             if ($avatarFile) {
                 $avatarUrl = $avatarFile->getUrl();
             }
         }

        return [
            'user' => [
                'avatar_url' => $avatarUrl
            ],
            'stats' => [
                'total_training_time' => $stats->getTotalTrainingTime(),
                'total_xp' => $stats->getTotalXp(),
                'completed_videos' => $stats->getCompletedVideos()
            ],
            'recent_exercises' => $recentExercises
        ];
    }
}
