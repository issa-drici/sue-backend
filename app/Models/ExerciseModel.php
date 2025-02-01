<?php

namespace App\Models;

use App\Entities\Exercise;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExerciseModel extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'exercises';
    
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'level',
        'banner_url',
        'video_url',
        'title',
        'description',
        'duration',
        'xp_value',
    ];

    public function userExercises(): HasMany
    {
        return $this->hasMany(UserExerciseModel::class, 'exercise_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(FavoriteModel::class, 'exercise_id');
    }

    public function toEntity(): Exercise
    {
        return new Exercise(
            id: $this->id,
            level: $this->level,
            bannerUrl: $this->banner_url,
            videoUrl: $this->video_url,
            title: $this->title,
            description: $this->description,
            duration: $this->duration,
            xpValue: $this->xp_value,
        );
    }

    public static function fromEntity(Exercise $exercise): self
    {
        return new self([
            'level' => $exercise->getLevel(),
            'banner_url' => $exercise->getBannerUrl(),
            'video_url' => $exercise->getVideoUrl(),
            'title' => $exercise->getTitle(),
            'description' => $exercise->getDescription(),
            'duration' => $exercise->getDuration(),
            'xp_value' => $exercise->getXpValue(),
        ]);
    }
} 