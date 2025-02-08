<?php

namespace App\Repositories\UserProfile;

use App\Entities\UserProfile;
use App\Models\UserProfileModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

    public function findAllUsers(): array
    {
        return UserProfileModel::join('users', 'user_profiles.user_id', '=', 'users.id')
            ->select('users.id as user_id', 'users.full_name')
            ->get()
            ->toArray();
    }
} 