<?php

namespace App\Repositories\UserProfile;

use App\Entities\UserProfile;
use Carbon\Carbon;

interface UserProfileRepositoryInterface
{
    public function findByUserId(string $userId): ?UserProfile;
    public function findAllUsers(): array;
    public function save(UserProfile $userProfile): UserProfile;
} 