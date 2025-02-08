<?php

namespace App\Repositories\User;

interface UserRepositoryInterface
{
    public function findById(string $id): ?array;
    public function findAll(): array;
} 