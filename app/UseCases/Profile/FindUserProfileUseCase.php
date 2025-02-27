<?php

namespace App\UseCases\Profile;

use App\Repositories\UserProfile\UserProfileRepositoryInterface;
use App\Repositories\UserExercise\UserExerciseRepositoryInterface;
use App\Repositories\Exercise\ExerciseRepositoryInterface;
use App\Repositories\Favorite\FavoriteRepositoryInterface;
use App\Repositories\File\FileRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class FindUserProfileUseCase
{
    public function __construct(
        private UserProfileRepositoryInterface $userProfileRepository,
        private UserExerciseRepositoryInterface $userExerciseRepository,
        private ExerciseRepositoryInterface $exerciseRepository,
        private FavoriteRepositoryInterface $favoriteRepository,
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
                'completed_videos' => 0,
                'completed_days' => 0
            ];
        }

        // Récupération des favoris
        $favorites = $this->favoriteRepository->findByUserId($user->id);
        $exerciseIds = array_column($favorites, 'exercise_id');
        $exercises = $this->exerciseRepository->findByIds($exerciseIds);

        // Formatage des favoris
        $formattedFavorites = array_map(function ($favorite) use ($exercises) {
            $exercise = $exercises[array_search($favorite['exercise_id'], array_column($exercises, 'id'))];
            return [
                'id' => $exercise['id'],
                'title' => $exercise['title'],
                'level' => $exercise['level'],
                'banner_url' => $exercise['banner_url'],
                'duration' => $exercise['duration_seconds'],
            ];
        }, $favorites);

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
                'id' => $user->id,
                'full_name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $avatarUrl
            ],
            'stats' => [
                'total_xp' => $stats->getTotalXp(),
                'total_training_time' => $stats->getTotalTrainingTime(),
                'completed_videos' => $stats->getCompletedVideos(),
                'completed_days' => $stats->getCompletedDays()
            ],
            'favorites' => $formattedFavorites,
            'current_goals' => $stats->getCurrentGoals()
        ];
    }
}
