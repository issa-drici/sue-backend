<?php

namespace App\UseCases\Notification;

use App\Entities\Notification;
use App\Repositories\Notification\NotificationRepositoryInterface;
use Exception;

class MarkNotificationAsReadUseCase
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository
    ) {}

    public function execute(string $notificationId, string $userId): Notification
    {
        $notification = $this->notificationRepository->findById($notificationId);

        if (!$notification) {
            throw new Exception('Notification non trouvée');
        }

        // Vérifier que la notification appartient à l'utilisateur
        if ($notification->getUserId() !== $userId) {
            throw new Exception('Vous n\'êtes pas autorisé à modifier cette notification');
        }

        $success = $this->notificationRepository->markAsRead($notificationId);

        if (!$success) {
            throw new Exception('Erreur lors de la mise à jour de la notification');
        }

        // Récupérer la notification mise à jour
        $updatedNotification = $this->notificationRepository->findById($notificationId);

        if (!$updatedNotification) {
            throw new Exception('Erreur lors de la récupération de la notification mise à jour');
        }

        return $updatedNotification;
    }
}
