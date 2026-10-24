<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBadgeModel extends Model
{
    protected $table = 'user_badges';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'user_id', 'badge', 'unlocked_at', 'seen_at'];

    protected $casts = [
        'unlocked_at' => 'datetime',
        'seen_at' => 'datetime',
    ];
}
