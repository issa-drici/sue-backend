<?php

namespace App\Repositories\Friend;

use App\Entities\Friend;
use Illuminate\Pagination\LengthAwarePaginator;

interface FriendRepositoryInterface
{
    public function findById(string $id): ?Friend;

    public function getUserFriends(string $userId, int $page = 1, int $limit = 20): LengthAwarePaginator;

    public function areFriends(string $userId1, string $userId2): bool;

    public function addFriend(string $userId1, string $userId2): bool;

    public function removeFriend(string $userId1, string $userId2): bool;

    public function getMutualFriendsCount(string $userId1, string $userId2): int;

    public function getFriendsIds(string $userId): array;
}
