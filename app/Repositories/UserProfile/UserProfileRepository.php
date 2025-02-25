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

    public function findAllUsers(): array
    {
        return UserProfileModel::join('users', 'user_profiles.user_id', '=', 'users.id')
            ->select('users.id as user_id', 'users.full_name')
            ->get()
            ->toArray();
    }


    public function save(UserProfile $userProfile): UserProfile
    {
        $model = UserProfileModel::updateOrCreate(
            ['user_id' => $userProfile->getUserId()],
            [
                'avatar_file_id' => $userProfile->getAvatarFileId(),
                'total_xp' => $userProfile->getTotalXp(),
                'completed_videos' => $userProfile->getCompletedVideos(),
                'total_training_time' => $userProfile->getTotalTrainingTime(),
                'current_goals' => $userProfile->getCurrentGoals(),
            ]
        );

        return $model ? $model->toEntity() : null;
    }
}
