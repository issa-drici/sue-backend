<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Un membre a lu le chat jusqu'à un message : les coches « vu » se mettent à jour en direct
 * chez les autres.
 */
class ChatReadUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public string $sessionId,
        public string $userId,
        public int $lastReadMessageId
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('session-chat.' . $this->sessionId)];
    }

    public function broadcastAs(): string
    {
        return 'read.updated';
    }

    public function broadcastWith(): array
    {
        return ['userId' => $this->userId, 'lastReadMessageId' => $this->lastReadMessageId];
    }
}
