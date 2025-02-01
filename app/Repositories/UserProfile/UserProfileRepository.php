<?php

namespace App\Repositories\UserProfile;

use App\Entities\UserProfile;
use App\Models\UserProfileModel;

class UserProfileRepository implements UserProfileRepositoryInterface
{
    public function findByUserId(string $userId): ?UserProfile
    {
        $profile = UserProfileModel::where('user_id', $userId)->first();
        
        if (!$profile) {
            return null;
        }

        return $profile ? $profile->toEntity() : null;
    }
} 