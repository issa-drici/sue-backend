<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserReferralModel extends Model
{
    protected $table = 'user_referrals';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'referrer_id', 'referred_id', 'session_id'];
}
