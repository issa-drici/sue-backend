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
        // Rien à marquer n'est pas une erreur : updatedCount vaut alors 0
        $updatedCount = $this->notificationRepository->markAllAsRead($userId);

        return [
            'updatedCount' => $updatedCount,
            'success' => true
        ];
    }
}
