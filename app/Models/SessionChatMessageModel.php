<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionChatMessageModel extends Model
{
    protected $table = 'session_chat_messages';

    protected $fillable = [
        'session_id',
        'user_id',
        'client_id',
        'body',
        'mentions',
    ];

    protected $casts = [
        'mentions' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    /** Format envoyé à l'app (API et temps réel) */
    public function toPayload(): array
    {
        return [
            'id' => $this->id,
            'clientId' => $this->client_id,
            'sessionId' => $this->session_id,
            'senderId' => $this->user_id,
            'senderFirstname' => $this->user?->firstname,
            'body' => $this->body,
            'mentions' => $this->mentions ?? [],
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
