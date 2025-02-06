<?php

namespace App\Repositories\Favorite;

interface FavoriteRepositoryInterface
{
    public function findByUserId(string $userId): array;
    public function findByUserAndExercise(string $userId, string $exerciseId): ?array;
} 