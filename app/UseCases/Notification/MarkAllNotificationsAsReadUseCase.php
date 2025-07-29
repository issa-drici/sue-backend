<?php

namespace App\UseCases\Notification;

use App\Repositories\Notification\NotificationRepositoryInterface;

class MarkAllNotificationsAsReadUseCase
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository
    ) {}

    public function execute(string $userId): array
    {
        $success = $this->notificationRepository->markAllAsRead($userId);

        if (!$success) {
            throw new \Exception('Erreur lors de la mise à jour des notifications');
        }

        $unreadCount = $this->notificationRepository->getUnreadCount($userId);

        return [
            'updatedCount' => $unreadCount,
            'success' => $success
        ];
    }
}
