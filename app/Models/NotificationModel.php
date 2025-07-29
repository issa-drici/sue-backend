<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationModel extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'type',
        'title',
        'message',
        'session_id',
        'read',
        'push_sent',
        'push_sent_at',
        'push_data',
    ];

    protected $casts = [
        'read' => 'boolean',
        'push_sent' => 'boolean',
        'push_sent_at' => 'datetime',
        'push_data' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(SportSessionModel::class, 'session_id');
    }

    public function scopeUnread($query)
    {
        return $query->where('read', false);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }
}
