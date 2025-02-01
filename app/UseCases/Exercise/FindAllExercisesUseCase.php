<?php

namespace App\UseCases\Exercise;

use App\Repositories\Exercise\ExerciseRepositoryInterface;

class FindAllExercisesUseCase
{
    private const LEVEL_MAPPING = [
        1 => 'beginner',
        2 => 'intermediate',
        3 => 'advanced'
    ];

    public function __construct(
        private ExerciseRepositoryInterface $exerciseRepository
    ) {}

    public function execute(): array
    {
        $exercises = $this->exerciseRepository->findAll();
        return $this->formatResponse($exercises);
    }

    private function formatResponse(array $exercises): array
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
                'duration_seconds' => $exercise['duration_seconds'],
                'xp' => $exercise['xp'],
                'banner_url' => $exercise['banner_url'],
                'video_url' => $exercise['video_url']
            ];
        }

        return $grouped;
    }
}
