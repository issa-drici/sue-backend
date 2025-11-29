<?php

namespace App\Repositories\Notification;

use App\Entities\Notification;
use Illuminate\Pagination\LengthAwarePaginator;

interface NotificationRepositoryInterface
{
    public function findById(string $id): ?Notification;

    public function findByUser(string $userId, int $page = 1, int $limit = 20): LengthAwarePaginator;

    public function create(array $data): Notification;

    public function markAsRead(string $id): bool;

    public function markAllAsRead(string $userId): bool;

    public function delete(string $id): bool;

    public function getUnreadCount(string $userId): int;

    public function findByType(string $userId, string $type): array;

    public function findBySession(string $sessionId): array;

    /**
     * Vérifie si une notification d'invitation existe déjà pour un utilisateur et une session
     */
    public function hasInvitationNotification(string $userId, string $sessionId): bool;

    /**
     * Marquer une notification comme envoyée par push
     */
    public function markAsPushSent(string $id, array $pushData = []): bool;

    /**
     * Vérifie si une notification de rappel d'un type spécifique existe déjà pour un utilisateur et une session
     */
    public function hasReminderNotification(string $userId, string $sessionId, string $reminderType): bool;
}
