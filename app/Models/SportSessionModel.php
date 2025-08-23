<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SportSessionModel extends Model
{
    use HasFactory;

    protected $table = 'sport_sessions';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'sport',
        'date',
        'time',
        'location',
        'max_participants',
        'organizer_id',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'string',
        'max_participants' => 'integer',
    ];

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'organizer_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(SportSessionParticipantModel::class, 'session_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(SportSessionCommentModel::class, 'session_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(NotificationModel::class, 'session_id');
    }

    public function scopeBySport($query, string $sport)
    {
        return $query->where('sport', $sport);
    }

    public function scopeByDate($query, string $date)
    {
        return $query->where('date', $date);
    }

    public function scopeByOrganizer($query, string $organizerId)
    {
        return $query->where('organizer_id', $organizerId);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', now()->toDateString());
    }

    public function scopePast($query)
    {
        return $query->where('date', '<', now()->toDateString());
    }
}
