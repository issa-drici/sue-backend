<?php

namespace App\Repositories\Favorite;

use App\Models\FavoriteModel;

class FavoriteRepository implements FavoriteRepositoryInterface
{
    public function findByUserId(string $userId): array
    {
        return FavoriteModel::where('user_id', $userId)
            ->select(['id', 'exercise_id'])
            ->get()
            ->toArray();
    }

    public function findByUserAndExercise(string $userId, string $exerciseId): ?array
    {
        return FavoriteModel::where('user_id', $userId)
            ->where('exercise_id', $exerciseId)
            ->select(['id', 'exercise_id'])
            ->first()?->toArray();
    }

    public function create(array $data): array
    {
        return FavoriteModel::create($data)->toArray();
    }

    public function delete(string $id): void
    {
        FavoriteModel::where('id', $id)->delete();
    }
} 