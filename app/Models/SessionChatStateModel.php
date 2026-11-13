<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionChatStateModel extends Model
{
    protected $table = 'session_chat_states';

    protected $fillable = [
        'session_id',
        'user_id',
        'last_read_message_id',
        'muted',
    ];

    protected $casts = [
        'last_read_message_id' => 'integer',
        'muted' => 'boolean',
    ];
}
