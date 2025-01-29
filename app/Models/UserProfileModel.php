<?php

namespace App\Models;

use App\Entities\UserProfile;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfileModel extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_profiles';
    
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'avatar_file_id',
        'total_xp',
        'total_training_time',
        'completed_videos',
        'completed_days',
        'current_goals',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function avatarFile(): BelongsTo
    {
        return $this->belongsTo(FileModel::class, 'avatar_file_id');
    }

    public function toEntity(): UserProfile
    {
        return new UserProfile(
            id: $this->id,
            userId: $this->user_id,
            avatarFileId: $this->avatar_file_id,
            totalXp: $this->total_xp,
            totalTrainingTime: $this->total_training_time,
            completedVideos: $this->completed_videos,
            completedDays: $this->completed_days,
            currentGoals: $this->current_goals,
        );
    }

    public static function fromEntity(UserProfile $profile): self
    {
        return new self([
            'user_id' => $profile->getUserId(),
            'avatar_file_id' => $profile->getAvatarFileId(),
            'total_xp' => $profile->getTotalXp(),
            'total_training_time' => $profile->getTotalTrainingTime(),
            'completed_videos' => $profile->getCompletedVideos(),
            'completed_days' => $profile->getCompletedDays(),
            'current_goals' => $profile->getCurrentGoals(),
        ]);
    }
} 