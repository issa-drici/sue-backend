<?php

namespace App\Repositories\UserExercise;

use App\Entities\UserExercise;
use App\Models\UserExerciseModel;
use Illuminate\Support\Facades\DB;

class UserExerciseRepository implements UserExerciseRepositoryInterface
{
    public function findByUserAndExercise(string $userId, string $exerciseId): ?UserExercise
    {
        $model = UserExerciseModel::where('user_id', $userId)
            ->where('exercise_id', $exerciseId)
            ->first();

        return $model ? $model->toEntity() : null;
    }

    public function save(UserExercise $userExercise): UserExercise
    {
        $model = UserExerciseModel::fromEntity($userExercise);
        $model->save();
        return $model->toEntity();
    }

    public function updateWatchTime(string $userId, string $exerciseId, int $watchTime): UserExercise
    {
        $model = UserExerciseModel::updateOrCreate(
            ['user_id' => $userId, 'exercise_id' => $exerciseId],
            ['watch_time' => $watchTime]
        );
        return $model->toEntity();
    }

    public function markAsCompleted(string $userId, string $exerciseId): void
    {
        UserExerciseModel::where('user_id', $userId)
            ->where('exercise_id', $exerciseId)
            ->update(['completed_at' => now()]);
    }

    public function findRecent(string $userId, int $limit): array
    {
        return UserExerciseModel::where('user_id', $userId)
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get(['exercise_id', 'completed_at', 'updated_at'])
            ->map(function ($userExercise) {
                return [
                    'exercise_id' => $userExercise->exercise_id,
                    'completed_at' => $userExercise->completed_at?->toIso8601String(),
                    'updated_at' => $userExercise->updated_at->toIso8601String()
                ];
            })
            ->toArray();
    }
} 