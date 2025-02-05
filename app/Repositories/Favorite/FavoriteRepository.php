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
} 