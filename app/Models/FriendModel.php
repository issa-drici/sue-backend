<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FriendModel extends Model
{
    use HasFactory;

    protected $table = 'friends';

    protected $fillable = [
        'id',
        'user_id',
        'friend_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function friend(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'friend_id');
    }

    public function scopeByUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByFriend($query, string $friendId)
    {
        return $query->where('friend_id', $friendId);
    }
}
