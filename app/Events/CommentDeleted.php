<?php

namespace App\Events;

use App\Services\SocketIOService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $commentId;
    public string $sessionId;

    /**
     * Create a new event instance.
     */
    public function __construct(string $commentId, string $sessionId)
    {
        $this->commentId = $commentId;
        $this->sessionId = $sessionId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('sport-session.' . $this->sessionId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'comment.deleted';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'commentId' => $this->commentId,
            'deletedAt' => now()->toISOString(),
        ];
    }

    /**
     * Handle the event after it is broadcast.
     */
    public function broadcasted(): void
    {
        // L'événement est déjà diffusé via Laravel Broadcasting
        // Pas besoin d'appel supplémentaire au SocketIOService
    }
}
