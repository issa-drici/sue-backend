<?php

namespace App\UseCases\User;

use App\Repositories\Friend\FriendRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class GetUserFriendsUseCase
{
    public function __construct(
        private FriendRepositoryInterface $friendRepository
    ) {}

    public function execute(string $userId, int $page = 1, int $limit = 20): LengthAwarePaginator
    {
        return $this->friendRepository->getUserFriends($userId, $page, $limit);
    }
}
