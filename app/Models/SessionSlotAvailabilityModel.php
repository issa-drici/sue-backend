<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionSlotAvailabilityModel extends Model
{
    protected $table = 'session_slot_availabilities';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'slot_id',
        'user_id',
    ];

    public function slot(): BelongsTo
    {
        return $this->belongsTo(SessionSlotProposalModel::class, 'slot_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
