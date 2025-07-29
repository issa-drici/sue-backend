<?php

namespace App\UseCases\Notification;

use App\Repositories\Notification\NotificationRepositoryInterface;

class GetUnreadCountUseCase
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository
    ) {}

    public function execute(string $userId): int
    {
        return $this->notificationRepository->getUnreadCount($userId);
    }
}
