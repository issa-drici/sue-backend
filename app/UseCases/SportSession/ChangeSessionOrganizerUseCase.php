<?php

namespace App\UseCases\SportSession;

use App\Entities\SportSession;
use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Repositories\User\UserRepositoryInterface;
use App\Repositories\Notification\NotificationRepositoryInterface;
use App\Services\ExpoPushNotificationService;
use Exception;

class ChangeSessionOrganizerUseCase
{
    public function __construct(
        private SportSessionRepositoryInterface $sportSessionRepository,
        private UserRepositoryInterface $userRepository,
        private NotificationRepositoryInterface $notificationRepository,
        private ExpoPushNotificationService $pushNotificationService
    ) {}

    public function execute(string $sessionId, string $newOrganizerId, string $currentUserId): SportSession
    {
        // Récupérer la session
        $session = $this->sportSessionRepository->findById($sessionId);

        if (!$session) {
            throw new Exception('Session non trouvée');
        }

        // Vérifier que l'utilisateur actuel est l'organisateur
        if (!$session->isOrganizer($currentUserId)) {
            throw new Exception('Vous n\'êtes pas autorisé à changer l\'organisateur de cette session');
        }

        // Vérifier que le nouvel organisateur n'est pas déjà l'organisateur actuel
        if ($session->getOrganizer()->getId() === $newOrganizerId) {
            throw new Exception('Cet utilisateur est déjà l\'organisateur de cette session');
        }

        // Vérifier que le nouvel organisateur existe
        $newOrganizer = $this->userRepository->findById($newOrganizerId);
        if (!$newOrganizer) {
            throw new Exception('Utilisateur non trouvé');
        }

        // Vérifier que le nouvel organisateur est un participant de la session
        // Si ce n'est pas le cas, l'ajouter comme participant avec le statut 'accepted'
        if (!$session->isParticipant($newOrganizerId)) {
            $this->sportSessionRepository->addParticipant($sessionId, $newOrganizerId, 'accepted');
        } else {
            // S'assurer que le participant a le statut 'accepted'
            $this->sportSessionRepository->updateParticipantStatus($sessionId, $newOrganizerId, 'accepted');
        }

        // Changer l'organizer_id dans la base de données
        $updatedSession = $this->sportSessionRepository->update($sessionId, [
            'organizer_id' => $newOrganizerId
        ]);

        if (!$updatedSession) {
            throw new Exception('Erreur lors du changement d\'organisateur');
        }

        // Récupérer la session mise à jour pour avoir les bonnes informations
        $updatedSession = $this->sportSessionRepository->findById($sessionId);

        // Créer des notifications pour informer les participants du changement
        $this->createOrganizerChangeNotifications($session, $updatedSession, $newOrganizer);

        // Envoyer des notifications push
        $this->sendPushNotifications($session, $updatedSession, $newOrganizer);

        return $updatedSession;
    }

    private function createOrganizerChangeNotifications(
        SportSession $oldSession,
        SportSession $newSession,
        \App\Entities\User $newOrganizer
    ): void {
        $oldOrganizerName = $oldSession->getOrganizer()->getFirstname() . ' ' . $oldSession->getOrganizer()->getLastname();
        $newOrganizerName = $newOrganizer->getFirstname() . ' ' . $newOrganizer->getLastname();

        // Notifier tous les participants (y compris l'ancien organizer)
        $allParticipants = array_merge(
            [$oldSession->getOrganizer()->getId()],
            array_column($oldSession->getParticipants(), 'id')
        );
        $allParticipants = array_unique($allParticipants);

        foreach ($allParticipants as $participantId) {
            // Ne pas notifier le nouvel organisateur lui-même
            if ($participantId === $newOrganizer->getId()) {
                continue;
            }

            $this->notificationRepository->create([
                'user_id' => $participantId,
                'type' => 'session_update',
                'title' => 'Organisateur changé',
                'message' => "{$newOrganizerName} est maintenant l'organisateur de la session de {$newSession->getSport()}",
                'session_id' => $newSession->getId(),
                'push_data' => [
                    'type' => 'session_organizer_changed',
                    'session_id' => $newSession->getId(),
                    'old_organizer_id' => $oldSession->getOrganizer()->getId(),
                    'new_organizer_id' => $newOrganizer->getId(),
                    'sport' => $newSession->getSport(),
                    'date' => $newSession->getDate(),
                    'startTime' => $newSession->getStartTime(),
                    'endTime' => $newSession->getEndTime(),
                ],
            ]);
        }
    }

    private function sendPushNotifications(
        SportSession $oldSession,
        SportSession $newSession,
        \App\Entities\User $newOrganizer
    ): void {
        $newOrganizerName = $newOrganizer->getFirstname() . ' ' . $newOrganizer->getLastname();

        // Notifier tous les participants (y compris l'ancien organizer)
        $allParticipants = array_merge(
            [$oldSession->getOrganizer()->getId()],
            array_column($oldSession->getParticipants(), 'id')
        );
        $allParticipants = array_unique($allParticipants);

        foreach ($allParticipants as $participantId) {
            // Ne pas notifier le nouvel organisateur lui-même
            if ($participantId === $newOrganizer->getId()) {
                continue;
            }

            // Envoyer seulement aux participants qui ont accepté (ou à l'ancien organizer)
            $isAccepted = false;
            if ($participantId === $oldSession->getOrganizer()->getId()) {
                $isAccepted = true;
            } else {
                foreach ($oldSession->getParticipants() as $participant) {
                    if ($participant['id'] === $participantId && $participant['status'] === 'accepted') {
                        $isAccepted = true;
                        break;
                    }
                }
            }

            if ($isAccepted) {
                $this->pushNotificationService->sendToUser(
                    $participantId,
                    'Organisateur changé',
                    "{$newOrganizerName} est maintenant l'organisateur de la session de {$newSession->getSport()}",
                    [
                        'type' => 'session_organizer_changed',
                        'session_id' => $newSession->getId(),
                        'old_organizer_id' => $oldSession->getOrganizer()->getId(),
                        'new_organizer_id' => $newOrganizer->getId(),
                        'sport' => $newSession->getSport(),
                        'date' => $newSession->getDate(),
                        'startTime' => $newSession->getStartTime(),
                        'endTime' => $newSession->getEndTime(),
                    ]
                );
            }
        }
    }
}

