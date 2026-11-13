<?php

namespace App\UseCases\SportSession;

use App\Entities\SportSession;
use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Repositories\Notification\NotificationRepositoryInterface;
use App\Services\SessionNotificationService;
use Exception;

class CancelSportSessionUseCase
{
    public function __construct(
        private SportSessionRepositoryInterface $sportSessionRepository,
        private NotificationRepositoryInterface $notificationRepository,
        private SessionNotificationService $sessionNotificationService
    ) {}

    public function execute(string $sessionId, string $userId): array
    {
        // Récupérer la session
        $session = $this->sportSessionRepository->findById($sessionId);

        if (!$session) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'SESSION_NOT_FOUND',
                    'message' => 'Session non trouvée',
                ],
            ];
        }

        // Vérifier que l'utilisateur est l'organisateur
        if (!$session->isOrganizer($userId)) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Vous n\'êtes pas autorisé à annuler cette session',
                ],
            ];
        }

        // Vérifier que la session n'est pas déjà annulée
        if ($session->getStatus() === 'cancelled') {
            return [
                'success' => false,
                'error' => [
                    'code' => 'SESSION_ALREADY_CANCELLED',
                    'message' => 'Cette session est déjà annulée',
                ],
            ];
        }

        // Vérifier que la session n'est pas terminée
        if (strtotime($session->getDate()) < strtotime(date('Y-m-d'))) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'SESSION_ENDED',
                    'message' => 'Impossible d\'annuler une session terminée',
                ],
            ];
        }

        // Mettre à jour le statut de la session
        $updatedSession = $this->sportSessionRepository->update($sessionId, ['status' => 'cancelled']);

        if (!$updatedSession) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'UPDATE_FAILED',
                    'message' => 'Erreur lors de l\'annulation de la session',
                ],
            ];
        }

        // Notification in-app + push pour les participants confirmés
        $this->notifyParticipants($updatedSession);

        return [
            'success' => true,
            'message' => 'Session annulée avec succès',
            'data' => [
                'session' => $updatedSession->toArray(),
            ],
        ];
    }

    private function notifyParticipants(SportSession $session): void
    {
        $organizerId = $session->getOrganizer()->getId();
        $organizerName = $session->getOrganizer()->getFirstname() . ' ' . $session->getOrganizer()->getLastname();
        $title = 'Session annulée';
        $message = "{$organizerName} a annulé sa session de {$session->getSport()}";
        $data = [
            'type' => 'session_cancelled',
            'session_id' => $session->getId(),
            'organizer_id' => $organizerId,
            'sport' => $session->getSport(),
            'date' => $session->getDate(),
            'startTime' => $session->getStartTime(),
            'endTime' => $session->getEndTime(),
        ];

        foreach ($session->getParticipants() as $participant) {
            // Seulement les participants confirmés, jamais l'organisateur lui-même
            if ($participant['id'] === $organizerId || $participant['status'] !== 'accepted') {
                continue;
            }

            $notification = $this->notificationRepository->create([
                'user_id' => $participant['id'],
                'type' => 'session_cancelled',
                'title' => $title,
                'message' => $message,
                'session_id' => $session->getId(),
                'push_data' => $data,
            ]);

            $this->sessionNotificationService->push($participant['id'], $notification->getId(), $title, $message, $data);
        }
    }
}
