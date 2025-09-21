<?php

namespace App\UseCases\SportSession;

use App\Entities\SportSession;
use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Repositories\Notification\NotificationRepositoryInterface;
use App\Services\ExpoPushNotificationService;
use Exception;

class CancelSportSessionUseCase
{
    public function __construct(
        private SportSessionRepositoryInterface $sportSessionRepository,
        private NotificationRepositoryInterface $notificationRepository,
        private ExpoPushNotificationService $pushNotificationService
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

        // Créer des notifications pour tous les participants
        $this->createCancellationNotifications($updatedSession);

        // Envoyer des notifications push
        $this->sendPushNotifications($updatedSession);

        return [
            'success' => true,
            'message' => 'Session annulée avec succès',
            'data' => [
                'session' => $updatedSession->toArray(),
            ],
        ];
    }

    private function createCancellationNotifications(SportSession $session): void
    {
        $organizerName = $session->getOrganizer()->getFirstname() . ' ' . $session->getOrganizer()->getLastname();

        foreach ($session->getParticipants() as $participant) {
            // Ne pas notifier l'organisateur lui-même
            if ($participant['id'] === $session->getOrganizer()->getId()) {
                continue;
            }

            // Notifier seulement les participants qui ont accepté
            if ($participant['status'] === 'accepted') {
                $this->notificationRepository->create([
                    'user_id' => $participant['id'],
                    'type' => 'session_cancelled',
                    'title' => 'Session annulée',
                    'message' => "{$organizerName} a annulé sa session de {$session->getSport()}",
                    'session_id' => $session->getId(),
                    'data' => json_encode([
                        'type' => 'session_cancelled',
                        'session_id' => $session->getId(),
                        'organizer_id' => $session->getOrganizer()->getId(),
                        'sport' => $session->getSport(),
                        'date' => $session->getDate(),
                        'startTime' => $session->getStartTime(),
                        'endTime' => $session->getEndTime(),
                    ]),
                ]);
            }
        }
    }

    private function sendPushNotifications(SportSession $session): void
    {
        $organizerName = $session->getOrganizer()->getFirstname() . ' ' . $session->getOrganizer()->getLastname();

        foreach ($session->getParticipants() as $participant) {
            // Ne pas notifier l'organisateur lui-même
            if ($participant['id'] === $session->getOrganizer()->getId()) {
                continue;
            }

            // Envoyer seulement aux participants qui ont accepté
            if ($participant['status'] === 'accepted') {
                $this->pushNotificationService->sendToUser(
                    $participant['id'],
                    'Session annulée',
                    "{$organizerName} a annulé sa session de {$session->getSport()}",
                    [
                        'type' => 'session_cancelled',
                        'session_id' => $session->getId(),
                        'organizer_id' => $session->getOrganizer()->getId(),
                        'sport' => $session->getSport(),
                        'date' => $session->getDate(),
                        'startTime' => $session->getStartTime(),
                        'endTime' => $session->getEndTime(),
                    ]
                );
            }
        }
    }
}
