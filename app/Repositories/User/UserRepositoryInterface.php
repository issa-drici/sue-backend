<?php

namespace App\Repositories\User;

use App\Entities\User;
use App\Entities\UserProfile;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function findById(string $id): ?User;

    public function findByEmail(string $email): ?User;

    public function create(array $data): User;

    public function update(string $id, array $data): ?User;

    public function delete(string $id): bool;

    public function getUserProfile(string $userId): ?UserProfile;

    public function updateUserProfile(string $userId, array $data): ?UserProfile;

    public function searchUsers(string $query, string $currentUserId, int $page = 1, int $limit = 20): LengthAwarePaginator;

    public function updateEmail(string $userId, string $newEmail): bool;

    public function updatePassword(string $userId, string $newPassword): bool;

    public function verifyPassword(string $userId, string $password): bool;

    public function emailExists(string $email, ?string $excludeUserId = null): bool;
}
