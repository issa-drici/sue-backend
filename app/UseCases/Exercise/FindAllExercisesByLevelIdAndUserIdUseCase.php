<?php

namespace App\UseCases\Exercise;

use App\Repositories\Exercise\ExerciseRepositoryInterface;
use App\Repositories\UserExercise\UserExerciseRepositoryInterface;
use App\Repositories\Level\LevelRepositoryInterface;

class FindAllExercisesByLevelIdAndUserIdUseCase
{
    public function __construct(
        private ExerciseRepositoryInterface $exerciseRepository,
        private UserExerciseRepositoryInterface $userExerciseRepository,
        private LevelRepositoryInterface $levelRepository
    ) {}

    public function execute(string $levelId, string $userId): array
    {
        // Récupérer les informations du niveau
        $level = $this->levelRepository->findById($levelId);

        if (!$level) {
            return [
                'status' => 'error',
                'message' => 'Niveau non trouvé'
            ];
        }

        // Convertir l'entité Level en tableau
        $levelData = $level->toArray();

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

        // Ajouter les exercices aux données du niveau
        $levelData['exercises'] = $enrichedExercises;

        return $levelData;
    }
}
