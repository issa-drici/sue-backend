<?php

namespace App\UseCases\SportSession;

use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Repositories\Notification\NotificationRepositoryInterface;
use App\Services\SessionNotificationService;
use Exception;

class DeleteSportSessionUseCase
{
    public function __construct(
        private SportSessionRepositoryInterface $sportSessionRepository,
        private NotificationRepositoryInterface $notificationRepository,
        private SessionNotificationService $sessionNotificationService
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

    /**
     * Notification in-app + push pour les participants confirmés (comme une annulation),
     * jamais pour l'organisateur. Sans session_id : la session n'existera plus.
     */
    private function createSessionDeletedNotifications($session): void
    {
        $organizerId = $session->getOrganizer()->getId();
        $title = 'Session annulée';
        $message = "La session de {$session->getSport()} a été annulée";

        foreach ($session->getParticipants() as $participant) {
            if ($participant['id'] === $organizerId || $participant['status'] !== 'accepted') {
                continue;
            }

            $notification = $this->notificationRepository->create([
                'user_id' => $participant['id'],
                'type' => 'update',
                'title' => $title,
                'message' => $message,
                'session_id' => null, // Session supprimée
            ]);

            $this->sessionNotificationService->push($participant['id'], $notification->getId(), $title, $message, [
                'type' => 'session_cancelled',
                'sport' => $session->getSport(),
                'date' => $session->getDate(),
                'startTime' => $session->getStartTime(),
            ]);
        }
    }
}
