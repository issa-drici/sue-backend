<?php

namespace App\UseCases\Exercise;

use App\Repositories\Exercise\ExerciseRepositoryInterface;
use App\Repositories\UserExercise\UserExerciseRepositoryInterface;

class FindAllExercisesByLevelIdAndUserIdUseCase
{
    public function __construct(
        private ExerciseRepositoryInterface $exerciseRepository,
        private UserExerciseRepositoryInterface $userExerciseRepository
    ) {}

    public function execute(string $levelId, string $userId): array
    {
        // Récupérer les exercices du niveau spécifié
        $exercises = $this->exerciseRepository->findByLevelId($levelId);

        // Récupérer les exercices complétés par l'utilisateur
        $completedExercises = $this->userExerciseRepository->findCompletedByUserId($userId);
        $completedExerciseIds = array_column($completedExercises, 'exercise_id');

        // Enrichir les exercices avec les informations de complétion
        $enrichedExercises = array_map(function ($exercise) use ($completedExerciseIds) {
            // Convertir les objets en tableaux si nécessaire
            if (is_object($exercise)) {
                $exercise = (array) $exercise;
            }

            return [
                'id' => $exercise['id'] ?? '',
                'title' => $exercise['title'] ?? '',
                'description' => $exercise['description'] ?? '',
                'duration_seconds' => $exercise['duration'] ?? 0,
                'level_id' => $exercise['level_id'] ?? '',
                'xp' => $exercise['xp_value'] ?? 0,
                'banner_url' => $exercise['banner_url'] ?? '',
                'video_url' => $exercise['video_url'] ?? '',
                'isCompleted' => in_array($exercise['id'] ?? '', $completedExerciseIds)
            ];
        }, $exercises);

        return [
            'level_id' => $levelId,
            'exercises' => $enrichedExercises
        ];
    }
}
