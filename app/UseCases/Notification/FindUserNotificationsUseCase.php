<?php

namespace App\UseCases\Notification;

use App\Repositories\Notification\NotificationRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class FindUserNotificationsUseCase
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository
    ) {}

    public function execute(string $userId, int $page = 1, int $limit = 20): LengthAwarePaginator
    {
        return $this->notificationRepository->findByUser($userId, $page, $limit);
    }
}
