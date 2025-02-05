<?php

namespace App\Models;

use App\Entities\UserExercise;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use DateTime;

class UserExerciseModel extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_exercises';
    
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'exercise_id',
        'completed_at',
        'watch_time',
        'created_at'
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
            $this->id,
            $this->user_id,
            $this->exercise_id,
            $this->completed_at ? new DateTime($this->completed_at) : null,
            $this->watch_time,
            new DateTime($this->created_at)
        );
    }

    public static function fromEntity(UserExercise $userExercise): self
    {
        return new self([
            'user_id' => $userExercise->getUserId(),
            'exercise_id' => $userExercise->getExerciseId(),
            'completed_at' => $userExercise->getCompletedAt() ? $userExercise->getCompletedAt()->format('Y-m-d H:i:s') : null,
            'watch_time' => $userExercise->getWatchTime(),
            'created_at' => $userExercise->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);
    }
} 