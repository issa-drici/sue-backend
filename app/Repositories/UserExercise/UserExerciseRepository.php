<?php

namespace App\Repositories\UserExercise;

use App\Entities\UserExercise;
use App\Models\UserExerciseModel;
use Illuminate\Support\Facades\DB;
use DateTime;
use Carbon\Carbon;
use Illuminate\Support\Str;

class UserExerciseRepository implements UserExerciseRepositoryInterface
{
    public function findByUserAndExercise(string $userId, string $exerciseId): array
    {
        return UserExerciseModel::where('user_id', $userId)
            ->where('exercise_id', $exerciseId)
            ->select(['id', 'watch_time', 'completed_at'])
            ->get()
            ->toArray();
    }

    public function save(UserExercise $userExercise): UserExercise
    {
        $model = UserExerciseModel::find($userExercise->getId());
        
        if (!$model) {
            $model = new UserExerciseModel();
            $model->id = (string) Str::uuid();
            $model->user_id = $userExercise->getUserId();
            $model->exercise_id = $userExercise->getExerciseId();
            $model->created_at = $userExercise->getCreatedAt();
        }
        
        $model->watch_time = $userExercise->getWatchTime();
        $model->completed_at = $userExercise->getCompletedAt();
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

    public function findByUserAndExerciseForDate(string $userId, string $exerciseId, DateTime $date): ?UserExercise
    {
        $model = UserExerciseModel::where('user_id', $userId)
            ->where('exercise_id', $exerciseId)
            ->whereDate('created_at', $date)
            ->first();

        return $model ? $model->toEntity() : null;
    }

    public function findCompletedByPeriod(string $userId, DateTime $start, DateTime $end): array
    {
        return UserExerciseModel::where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('completed_at')
            ->select(['id', 'exercise_id', 'completed_at', 'watch_time'])
            ->get()
            ->toArray();
    }

    public function findByPeriod(string $userId, DateTime $start, DateTime $end): array
    {
        return UserExerciseModel::where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->select(['id', 'exercise_id', 'watch_time', 'created_at'])
            ->get()
            ->toArray();
    }

    public function findCompletedByUserId(string $userId): array
    {
        return UserExerciseModel::where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->select(['id', 'exercise_id', 'completed_at', 'watch_time'])
            ->get()
            ->toArray();
    }
} 