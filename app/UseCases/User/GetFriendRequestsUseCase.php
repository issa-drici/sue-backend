<?php

namespace App\UseCases\User;

use App\Repositories\FriendRequest\FriendRequestRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class GetFriendRequestsUseCase
{
    public function __construct(
        private FriendRequestRepositoryInterface $friendRequestRepository
    ) {}

    public function execute(string $userId, int $page = 1, int $limit = 20): LengthAwarePaginator
    {
        return $this->friendRequestRepository->getUserFriendRequests($userId, $page, $limit);
    }
}
