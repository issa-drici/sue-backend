<?php

namespace App\UseCases\User;

use App\Repositories\User\UserRepositoryInterface;
use App\Entities\UserProfile;

class GetUserProfileUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function execute(string $userId): ?UserProfile
    {
        return $this->userRepository->getUserProfile($userId);
    }
}
