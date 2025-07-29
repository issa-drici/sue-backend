<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class FriendRequestModel extends Model
{
    use HasFactory;

    protected $table = 'friend_requests';

    protected $fillable = [
        'id',
        'sender_id',
        'receiver_id',
        'status',
        'cancelled_at',
    ];

    protected $casts = [
        'id' => 'string',
        'sender_id' => 'string',
        'receiver_id' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid();
            }
        });
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'receiver_id');
    }

    public function scopeBySender($query, string $senderId)
    {
        return $query->where('sender_id', $senderId);
    }

    public function scopeByReceiver($query, string $receiverId)
    {
        return $query->where('receiver_id', $receiverId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeDeclined($query)
    {
        return $query->where('status', 'declined');
    }

    public function scopeCancelled($query)
    {
        return $query->whereNotNull('cancelled_at');
    }

    public function scopeNotCancelled($query)
    {
        return $query->whereNull('cancelled_at');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('cancelled_at')->where('status', 'pending');
    }
}
