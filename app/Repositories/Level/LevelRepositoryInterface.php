<?php

namespace App\Repositories\Level;

use App\Entities\Level;

interface LevelRepositoryInterface
{
    public function findAll(): array;
    public function findById(string $id): ?Level;
    public function findByCategory(string $category): array;
    public function create(array $data): Level;
    public function update(string $id, array $data): ?Level;
    public function delete(string $id): bool;
}
