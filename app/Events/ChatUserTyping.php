<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * « Karim est en train d'écrire » : l'app l'envoie au plus toutes les 3 s pendant la saisie et
 * l'affiche ~5 s chez les autres. Passe par le backend (et pas par un événement client Pusher)
 * pour ne dépendre d'aucun réglage du serveur temps réel.
 */
class ChatUserTyping implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public string $sessionId,
        public string $userId,
        public string $firstname
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('session-chat.' . $this->sessionId)];
    }

    public function broadcastAs(): string
    {
        return 'user.typing';
    }

    public function broadcastWith(): array
    {
        return ['userId' => $this->userId, 'firstname' => $this->firstname];
    }
}
