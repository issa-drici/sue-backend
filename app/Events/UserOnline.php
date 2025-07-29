<?php

namespace App\Events;

use App\Entities\SportSessionPresence;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserOnline implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly SportSessionPresence $presence
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("sport-session.{$this->presence->sportSessionId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.online';
    }

    public function broadcastWith(): array
    {
        return [
            'userId' => $this->presence->userId,
            'user' => $this->presence->user?->toArray(),
            'joinedAt' => $this->presence->createdAt->format('c'),
        ];
    }
}
