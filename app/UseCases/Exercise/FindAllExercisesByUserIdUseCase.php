<?php

namespace App\UseCases\Exercise;

use App\Repositories\Exercise\ExerciseRepositoryInterface;
use App\Repositories\Level\LevelRepositoryInterface;
use App\Repositories\UserExercise\UserExerciseRepositoryInterface;
use Illuminate\Validation\ValidationException;

class FindAllExercisesByUserIdUseCase
{
    public function __construct(
        private ExerciseRepositoryInterface $exerciseRepository,
        private UserExerciseRepositoryInterface $userExerciseRepository,
        private LevelRepositoryInterface $levelRepository
    ) {}

    public function execute(string $userId): array
    {
        $levels = $this->levelRepository->findAll();
        $exercises = $this->exerciseRepository->findAll();
        $completedExercises = $this->userExerciseRepository->findCompletedByUserId($userId);
        $completedExerciseIds = array_column($completedExercises, 'exercise_id');
        return $this->formatResponse($levels, $exercises, $completedExerciseIds);
    }

    private function formatResponse(array $levels, array $exercises, array $completedExerciseIds): array
    {
        $categories = [
            'beginner' => [],
            'intermediate' => [],
            'advanced' => []
        ];

        // Organiser les niveaux par catégorie
        foreach ($levels as $level) {
            // Convertir les objets en tableaux si nécessaire
            if (is_object($level)) {
                $level = (array) $level;
            }

            // Si $level est un entier ou n'est pas un tableau, le sauter
            if (!is_array($level)) {
                continue;
            }

            // Vérifier que les clés nécessaires existent
            if (!isset($level['category'])) {
                continue;
            }

            $category = $level['category'];
            if (!isset($categories[$category])) {
                continue;
            }

            // Créer une entrée de niveau avec un tableau d'exercices vide
            $levelData = [
                'id' => $level['id'] ?? null,
                'name' => $level['name'] ?? '',
                'category' => $level['category'],
                'level_number' => $level['level_number'] ?? 0,
                'description' => $level['description'] ?? '',
                'banner_url' => $level['banner_url'] ?? '',
                'exercises' => []
            ];

            $categories[$category][] = $levelData;
        }

        // Trier les niveaux par level_number dans chaque catégorie
        foreach ($categories as $category => $levels) {
            usort($categories[$category], function($a, $b) {
                return $a['level_number'] <=> $b['level_number'];
            });
        }

        // Ajouter les exercices à leurs niveaux respectifs
        foreach ($exercises as $exercise) {
            // Convertir les objets en tableaux si nécessaire
            if (is_object($exercise)) {
                $exercise = (array) $exercise;
            }

            // Si $exercise n'est pas un tableau ou n'a pas de level_id, le sauter
            if (!is_array($exercise) || empty($exercise['level_id'])) {
                continue;
            }

            $levelId = $exercise['level_id'];
            $exerciseData = [
                'id' => $exercise['id'] ?? '',
                'title' => $exercise['title'] ?? '',
                'duration_seconds' => $exercise['duration'] ?? 0,
                'xp' => $exercise['xp_value'] ?? 0,
                'banner_url' => $exercise['banner_url'] ?? '',
                'video_url' => $exercise['video_url'] ?? '',
                'isCompleted' => in_array($exercise['id'] ?? '', $completedExerciseIds)
            ];

            // Trouver le niveau correspondant et ajouter l'exercice
            foreach ($categories as $category => &$levels) {
                foreach ($levels as &$level) {
                    if (isset($level['id']) && $level['id'] === $levelId) {
                        $level['exercises'][] = $exerciseData;
                        break 2;
                    }
                }
            }
        }

        return ['categories' => $categories];
    }
}
