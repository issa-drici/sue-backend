<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessionSlotProposalModel extends Model
{
    protected $table = 'session_slot_proposals';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'session_id',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(SportSessionModel::class, 'session_id');
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(SessionSlotAvailabilityModel::class, 'slot_id');
    }
}
