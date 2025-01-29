<?php

namespace App\Models;

use App\Entities\UserExercise;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserExerciseModel extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_exercises';
    
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'exercise_id',
        'completed_at',
        'watch_time',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(ExerciseModel::class, 'exercise_id');
    }

    public function toEntity(): UserExercise
    {
        return new UserExercise(
            id: $this->id,
            userId: $this->user_id,
            exerciseId: $this->exercise_id,
            completedAt: $this->completed_at,
            watchTime: $this->watch_time,
        );
    }

    public static function fromEntity(UserExercise $userExercise): self
    {
        return new self([
            'user_id' => $userExercise->getUserId(),
            'exercise_id' => $userExercise->getExerciseId(),
            'completed_at' => $userExercise->getCompletedAt(),
            'watch_time' => $userExercise->getWatchTime(),
        ]);
    }
} 