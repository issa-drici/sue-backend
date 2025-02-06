<?php

namespace App\Repositories\Favorite;

interface FavoriteRepositoryInterface
{
    public function findByUserId(string $userId): array;
    public function findByUserAndExercise(string $userId, string $exerciseId): ?array;
    public function create(array $data): array;
    public function delete(string $id): void;
} 