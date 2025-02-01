<?php

namespace App\Repositories\UserProfile;

use App\Entities\UserProfile;

interface UserProfileRepositoryInterface
{
    public function findByUserId(string $userId): ?UserProfile;
} 