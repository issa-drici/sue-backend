<?php

namespace App\Repositories\Favorite;

interface FavoriteRepositoryInterface
{
    public function findByUserId(string $userId): array;
} 