<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Nouveau message dans le chat d'une session, diffusé à ceux qui ont le chat ouvert.
 * Canal privé : seuls les membres de la session y ont accès (routes/channels.php).
 * Envoyé aussi à l'auteur : l'app remplace son message « en cours d'envoi » grâce au clientId.
 */
class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public string $sessionId,
        public array $message
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('session-chat.' . $this->sessionId)];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return ['message' => $this->message];
    }
}
