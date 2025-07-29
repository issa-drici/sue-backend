<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStatsModel extends Model
{
    use HasFactory;

    protected $table = 'user_stats';

    protected $fillable = [
        'id',
        'user_id',
        'sessions_created',
        'sessions_participated',
        'favorite_sport',
    ];

    protected $casts = [
        'sessions_created' => 'integer',
        'sessions_participated' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function scopeByUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByFavoriteSport($query, string $sport)
    {
        return $query->where('favorite_sport', $sport);
    }
}
