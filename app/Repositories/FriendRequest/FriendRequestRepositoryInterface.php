<?php

namespace App\Repositories\FriendRequest;

use App\Entities\FriendRequest;
use App\Entities\FriendRequestEntity;
use Illuminate\Pagination\LengthAwarePaginator;

interface FriendRequestRepositoryInterface
{
    public function findById(string $id): ?FriendRequest;

    public function findEntityById(string $id): ?FriendRequestEntity;

    public function findRequestByUsers(string $senderId, string $receiverId): ?FriendRequestEntity;

    public function getRelationshipStatus(string $currentUserId, string $otherUserId): string;

    public function getUserFriendRequests(string $userId, int $page = 1, int $limit = 20): LengthAwarePaginator;

    public function createRequest(string $senderId, string $receiverId): array;

    public function updateRequestStatus(string $requestId, string $status): bool;

    public function requestExists(string $senderId, string $receiverId): bool;

    public function getPendingRequest(string $senderId, string $receiverId): ?FriendRequest;

    public function deleteRequest(string $requestId): bool;

    public function cancelRequest(string $requestId): bool;
}
