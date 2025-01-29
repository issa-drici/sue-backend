<?php

namespace App\Models;

use App\Entities\Favorite;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FavoriteModel extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'favorites';
    
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'exercise_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(ExerciseModel::class, 'exercise_id');
    }

    public function toEntity(): Favorite
    {
        return new Favorite(
            id: $this->id,
            userId: $this->user_id,
            exerciseId: $this->exercise_id,
        );
    }

    public static function fromEntity(Favorite $favorite): self
    {
        return new self([
            'user_id' => $favorite->getUserId(),
            'exercise_id' => $favorite->getExerciseId(),
        ]);
    }
} 