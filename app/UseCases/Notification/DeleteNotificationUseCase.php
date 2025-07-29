<?php

namespace App\UseCases\Notification;

use App\Repositories\Notification\NotificationRepositoryInterface;
use Exception;

class DeleteNotificationUseCase
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository
    ) {}

    public function execute(string $notificationId, string $userId): bool
    {
        $notification = $this->notificationRepository->findById($notificationId);

        if (!$notification) {
            throw new Exception('Notification non trouvée');
        }

        // Vérifier que la notification appartient à l'utilisateur
        if ($notification->getUserId() !== $userId) {
            throw new Exception('Vous n\'êtes pas autorisé à supprimer cette notification');
        }

        return $this->notificationRepository->delete($notificationId);
    }
}
