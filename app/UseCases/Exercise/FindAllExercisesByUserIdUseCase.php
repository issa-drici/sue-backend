<?php

namespace App\UseCases\Exercise;

use App\Repositories\Exercise\ExerciseRepositoryInterface;
use App\Repositories\UserExercise\UserExerciseRepositoryInterface;
use Illuminate\Validation\ValidationException;

class FindAllExercisesByUserIdUseCase
{
    private const LEVEL_MAPPING = [
        1 => 'beginner',
        2 => 'intermediate',
        3 => 'advanced'
    ];

    public function __construct(
        private ExerciseRepositoryInterface $exerciseRepository,
        private UserExerciseRepositoryInterface $userExerciseRepository
    ) {}

    public function execute(string $userId): array
    {
        $exercises = $this->exerciseRepository->findAll();
        $completedExercises = $this->userExerciseRepository->findCompletedByUserId($userId);
        $completedExerciseIds = array_column($completedExercises, 'exercise_id');
        
        return $this->formatResponse($exercises, $completedExerciseIds);
    }

    private function formatResponse(array $exercises, array $completedExerciseIds): array
    {
        $grouped = ['levels' => []];

        foreach ($exercises as $exercise) {
            $apiLevel = self::LEVEL_MAPPING[$exercise['level']] ?? null;
            if ($apiLevel === null) continue;

            if (!isset($grouped['levels'][$apiLevel])) {
                $grouped['levels'][$apiLevel] = [];
            }

            $grouped['levels'][$apiLevel][] = [
                'id' => $exercise['id'],
                'title' => $exercise['title'],
                'duration_seconds' => $exercise['duration'],
                'xp' => $exercise['xp_value'],
                'banner_url' => $exercise['banner_url'],
                'video_url' => $exercise['video_url'],
                'isCompleted' => in_array($exercise['id'], $completedExerciseIds)
            ];
        }

        return $grouped;
    }
} 