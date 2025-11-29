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
        'start_date',
        'end_date',
        'location',
        'max_participants',
        'price_per_person',
        'organizer_id',
        'status',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'max_participants' => 'integer',
        'price_per_person' => 'decimal:2',
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
        return $query->whereDate('start_date', $date);
    }

    public function scopeByOrganizer($query, string $organizerId)
    {
        return $query->where('organizer_id', $organizerId);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', now());
    }

    public function scopePast($query)
    {
        return $query->where('start_date', '<', now());
    }
}
