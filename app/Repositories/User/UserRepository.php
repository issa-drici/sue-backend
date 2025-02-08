<?php

namespace App\Repositories\User;

use App\Models\UserModel;

class UserRepository implements UserRepositoryInterface
{
    public function findById(string $id): ?array
    {
        return UserModel::where('id', $id)
            ->select(['id', 'full_name', 'email'])
            ->first()?->toArray();
    }

    public function findAll(): array
    {
        return UserModel::select(['id', 'full_name', 'email'])
            ->get()
            ->toArray();
    }
} 