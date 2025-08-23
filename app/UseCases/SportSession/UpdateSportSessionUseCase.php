<?php

namespace App\UseCases\SportSession;

use App\Entities\SportSession;
use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Repositories\Notification\NotificationRepositoryInterface;
use Exception;

class UpdateSportSessionUseCase
{
    public function __construct(
        private SportSessionRepositoryInterface $sportSessionRepository,
        private NotificationRepositoryInterface $notificationRepository
    ) {}

    public function execute(string $sessionId, array $data, string $userId): SportSession
    {
        // Récupérer la session
        $session = $this->sportSessionRepository->findById($sessionId);

        if (!$session) {
            throw new Exception('Session non trouvée');
        }

        // Vérifier que l'utilisateur est l'organisateur
        if (!$session->isOrganizer($userId)) {
            throw new Exception('Vous n\'êtes pas autorisé à modifier cette session');
        }

        // Vérifier que la session n'est pas dans le passé
        if (strtotime($session->getDate()) < strtotime(date('Y-m-d'))) {
            throw new Exception('Impossible de modifier une session passée');
        }

        // Validation des données
        $this->validateUpdateData($data);

        // Mettre à jour la session
        $updatedSession = $this->sportSessionRepository->update($sessionId, $data);

        if (!$updatedSession) {
            throw new Exception('Erreur lors de la mise à jour de la session');
        }

        // Créer une notification pour les participants
        $this->createSessionUpdatedNotification($updatedSession);

        return $updatedSession;
    }

    private function validateUpdateData(array $data): void
    {
        if (isset($data['time']) && !$this->isValidTime($data['time'])) {
            throw new Exception('Heure invalide');
        }

        if (isset($data['location']) && empty(trim($data['location']))) {
            throw new Exception('Lieu requis');
        }

        if (isset($data['location']) && strlen($data['location']) > 200) {
            throw new Exception('Le lieu ne peut pas dépasser 200 caractères');
        }

        if (isset($data['date']) && !$this->isValidDate($data['date'])) {
            throw new Exception('Date invalide');
        }

        if (isset($data['date']) && strtotime($data['date']) < strtotime(date('Y-m-d'))) {
            throw new Exception('La date ne peut pas être dans le passé');
        }


    }

    private function isValidDate(string $date): bool
    {
        $dateTime = \DateTime::createFromFormat('Y-m-d', $date);
        return $dateTime && $dateTime->format('Y-m-d') === $date;
    }

    private function isValidTime(string $time): bool
    {
        $dateTime = \DateTime::createFromFormat('H:i', $time);
        return $dateTime && $dateTime->format('H:i') === $time;
    }

    private function createSessionUpdatedNotification(SportSession $session): void
    {
        $organizerName = $session->getOrganizer()->getFirstname() . ' ' . $session->getOrganizer()->getLastname();

        foreach ($session->getParticipants() as $participant) {
            // Ne pas notifier l'organisateur lui-même
            if ($participant['id'] === $session->getOrganizer()->getId()) {
                continue;
            }

            if ($participant['status'] === 'accepted') {
                $this->notificationRepository->create([
                    'user_id' => $participant['id'],
                    'type' => 'session_update',
                    'title' => 'Session modifiée',
                    'message' => "{$organizerName} a modifié sa session de {$session->getSport()}",
                    'session_id' => $session->getId(),
                    'data' => json_encode([
                        'type' => 'session_update',
                        'session_id' => $session->getId(),
                        'organizer_id' => $session->getOrganizer()->getId(),
                        'changes' => [
                            'date' => $session->getDate(),
                            'time' => $session->getTime(),
                            'location' => $session->getLocation(),
                        ],
                    ]),
                ]);
            }
        }
    }
}
