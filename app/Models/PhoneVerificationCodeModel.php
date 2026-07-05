<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PhoneVerificationCodeModel extends Model
{
    use HasUuids;

    protected $table = 'phone_verification_codes';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'phone',
        'code_hash',
        'expires_at',
        'attempts',
        'verified_at',
        'last_sent_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'attempts' => 'integer',
    ];
}
