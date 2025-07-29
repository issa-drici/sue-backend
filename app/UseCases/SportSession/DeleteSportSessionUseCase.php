<?php

namespace App\UseCases\SportSession;

use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Repositories\Notification\NotificationRepositoryInterface;
use Exception;

class DeleteSportSessionUseCase
{
    public function __construct(
        private SportSessionRepositoryInterface $sportSessionRepository,
        private NotificationRepositoryInterface $notificationRepository
    ) {}

    public function execute(string $sessionId, string $userId): bool
    {
        // Récupérer la session
        $session = $this->sportSessionRepository->findById($sessionId);

        if (!$session) {
            throw new Exception('Session non trouvée');
        }

        // Vérifier que l'utilisateur est l'organisateur
        if (!$session->isOrganizer($userId)) {
            throw new Exception('Vous n\'êtes pas autorisé à supprimer cette session');
        }

        // Vérifier que la session n'est pas dans le passé
        if (strtotime($session->getDate()) < strtotime(date('Y-m-d'))) {
            throw new Exception('Impossible de supprimer une session passée');
        }

        // Créer des notifications pour les participants
        $this->createSessionDeletedNotifications($session);

        // Supprimer la session
        return $this->sportSessionRepository->delete($sessionId);
    }

    private function createSessionDeletedNotifications($session): void
    {
        foreach ($session->getParticipants() as $participant) {
            if ($participant['status'] === 'accepted') {
                $this->notificationRepository->create([
                    'user_id' => $participant['id'],
                    'type' => 'update',
                    'title' => 'Session annulée',
                    'message' => "La session de {$session->getSport()} a été annulée",
                    'session_id' => null, // Session supprimée
                ]);
            }
        }
    }
}
