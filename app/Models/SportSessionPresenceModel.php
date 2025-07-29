<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SportSessionPresenceModel extends Model
{
    use HasFactory;

    protected $table = 'sport_session_presence';

    protected $fillable = [
        'id',
        'sport_session_id',
        'user_id',
        'is_online',
        'is_typing',
        'last_seen_at',
        'typing_started_at',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'is_typing' => 'boolean',
        'last_seen_at' => 'datetime',
        'typing_started_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(SportSessionModel::class, 'sport_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function scopeBySession($query, string $sessionId)
    {
        return $query->where('sport_session_id', $sessionId);
    }

    public function scopeByUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOnline($query)
    {
        return $query->where('is_online', true);
    }

    public function scopeTyping($query)
    {
        return $query->where('is_typing', true);
    }

    public function scopeActive($query)
    {
        return $query->where('last_seen_at', '>=', now()->subMinutes(5));
    }
}
