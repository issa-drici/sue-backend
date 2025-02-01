<?php

namespace App\Repositories\Exercise;

use App\Models\ExerciseModel;
use App\Models\UserExerciseModel;

class ExerciseRepository implements ExerciseRepositoryInterface
{
    public function findAll(): array
    {
        return ExerciseModel::all()->map(function ($exercise) {
            return [
                'id' => $exercise->id,
                'name' => $exercise->name,
                'level' => $exercise->level,
                'duration_seconds' => $exercise->duration,
                'xp' => $exercise->xp_value,
                'thumbnail' => $exercise->thumbnail_url,
                'url' => $exercise->video_url
            ];
        })->toArray();
    }

    public function findCompletedExerciseIds(string $userId): array
    {
        return UserExerciseModel::where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->pluck('exercise_id')
            ->toArray();
    }
} 