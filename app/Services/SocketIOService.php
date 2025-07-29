<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocketIOService
{
    private string $socketUrl;
    private string $apiUrl;

    public function __construct()
    {
        $this->socketUrl = env('SOCKET_IO_URL', 'http://localhost:6001');
        $this->apiUrl = env('APP_URL', 'http://localhost:8000');
    }

    /**
     * Émettre un événement vers le serveur Socket.IO
     */
    public function emit(string $event, array $data): bool
    {
        try {
            Log::info("Attempting to emit WebSocket event", [
                'event' => $event,
                'data' => $data,
                'url' => $this->socketUrl . '/emit'
            ]);

            $response = Http::timeout(5)->post($this->socketUrl . '/emit', [
                'event' => $event,
                'data' => $data
            ]);

            if ($response->successful()) {
                Log::info("Socket.IO event emitted successfully: {$event}", [
                    'data' => $data,
                    'response' => $response->json()
                ]);
                return true;
            } else {
                Log::error("Socket.IO emit failed: {$event}", [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Socket.IO connection error: {$event}", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Émettre un commentaire créé
     */
    public function emitCommentCreated(string $sessionId, array $comment): bool
    {
        return $this->emit('comment-created', [
            'sessionId' => $sessionId,
            'comment' => $comment
        ]);
    }

    /**
     * Émettre un événement Laravel Broadcasting
     */
    public function emitLaravelEvent(string $event, string $channel, array $data): bool
    {
        return $this->emit('laravel-broadcast', [
            'event' => $event,
            'channel' => $channel,
            'data' => $data
        ]);
    }

    /**
     * Émettre un commentaire modifié
     */
    public function emitCommentUpdated(string $sessionId, array $comment): bool
    {
        return $this->emit('comment-updated', [
            'sessionId' => $sessionId,
            'comment' => $comment
        ]);
    }

    /**
     * Émettre un commentaire supprimé
     */
    public function emitCommentDeleted(string $sessionId, string $commentId): bool
    {
        return $this->emit('comment-deleted', [
            'sessionId' => $sessionId,
            'commentId' => $commentId
        ]);
    }

    /**
     * Émettre un utilisateur en ligne
     */
    public function emitUserOnline(string $sessionId, string $userId, array $user): bool
    {
        return $this->emit('user-online', [
            'sessionId' => $sessionId,
            'userId' => $userId,
            'user' => $user
        ]);
    }

    /**
     * Émettre un utilisateur hors ligne
     */
    public function emitUserOffline(string $sessionId, string $userId, array $user): bool
    {
        return $this->emit('user-offline', [
            'sessionId' => $sessionId,
            'userId' => $userId,
            'user' => $user
        ]);
    }

    /**
     * Émettre un indicateur de frappe
     */
    public function emitUserTyping(string $sessionId, string $userId, bool $isTyping, array $user): bool
    {
        return $this->emit('typing', [
            'sessionId' => $sessionId,
            'userId' => $userId,
            'isTyping' => $isTyping,
            'user' => $user
        ]);
    }

    /**
     * Vérifier si le serveur Socket.IO est accessible
     */
    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(3)->get($this->socketUrl . '/health');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
