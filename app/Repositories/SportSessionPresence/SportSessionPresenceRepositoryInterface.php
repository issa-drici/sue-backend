<?php

namespace App\Repositories\SportSessionPresence;

use App\Entities\SportSessionPresence;
use Illuminate\Pagination\LengthAwarePaginator;

interface SportSessionPresenceRepositoryInterface
{
    public function joinSession(string $sessionId, string $userId): SportSessionPresence;

    public function leaveSession(string $sessionId, string $userId): bool;

    public function updateTypingStatus(string $sessionId, string $userId, bool $isTyping): ?SportSessionPresence;

    public function updateLastSeen(string $sessionId, string $userId): bool;

    public function findPresenceBySessionAndUser(string $sessionId, string $userId): ?SportSessionPresence;

    public function findOnlineUsersBySession(string $sessionId, int $page = 1, int $limit = 50): LengthAwarePaginator;

    public function findTypingUsersBySession(string $sessionId): array;

    public function cleanupInactiveUsers(): int;
}
