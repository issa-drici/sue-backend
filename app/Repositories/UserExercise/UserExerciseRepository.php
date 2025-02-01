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
} 