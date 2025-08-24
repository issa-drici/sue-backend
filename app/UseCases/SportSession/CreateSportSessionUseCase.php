<?php

namespace App\UseCases\SportSession;

use App\Entities\SportSession;
use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Repositories\Notification\NotificationRepositoryInterface;
use App\Repositories\User\UserRepositoryInterface;
use App\Repositories\PushToken\PushTokenRepositoryInterface;
use App\Services\ExpoPushNotificationService;
use App\Services\DateFormatterService;
use Exception;

class CreateSportSessionUseCase
{
    public function __construct(
        private SportSessionRepositoryInterface $sportSessionRepository,
        private NotificationRepositoryInterface $notificationRepository,
        private UserRepositoryInterface $userRepository,
        private PushTokenRepositoryInterface $pushTokenRepository,
        private ExpoPushNotificationService $expoPushService
    ) {}

    public function execute(array $data): SportSession
    {
        // Validation des données
        $this->validateData($data);

        // Créer la session
        $session = $this->sportSessionRepository->create($data);

        // Ajouter les participants si spécifiés
        if (isset($data['participantIds']) && !empty($data['participantIds'])) {
            $this->addParticipantsToSession($session->getId(), $data['participantIds']);
        }

        // Créer une notification pour l'organisateur
        $this->createSessionCreatedNotification($session);

        // Envoyer des notifications push aux participants invités
        if (isset($data['participantIds']) && !empty($data['participantIds'])) {
            $this->sendInvitationNotifications($session, $data['participantIds']);
        }

        return $session;
    }

    private function validateData(array $data): void
    {
        if (!isset($data['sport']) || !in_array($data['sport'], ['tennis', 'golf', 'musculation', 'football', 'basketball'])) {
            throw new Exception('Sport invalide');
        }

        if (!isset($data['date']) || !$this->isValidDate($data['date'])) {
            throw new Exception('Date invalide');
        }

        if (!isset($data['time']) || !$this->isValidTime($data['time'])) {
            throw new Exception('Heure invalide');
        }

        if (!isset($data['location']) || empty(trim($data['location']))) {
            throw new Exception('Lieu requis');
        }

        if (!isset($data['organizer_id'])) {
            throw new Exception('Organisateur requis');
        }

        // Validation de maxParticipants
        if (isset($data['maxParticipants']) && $data['maxParticipants'] !== null && ($data['maxParticipants'] < 1 || $data['maxParticipants'] > 100)) {
            throw new Exception('Le nombre maximum de participants doit être entre 1 et 100');
        }

        // Vérifier que l'organisateur existe
        $organizer = $this->userRepository->findById($data['organizer_id']);
        if (!$organizer) {
            throw new Exception('Organisateur non trouvé');
        }

        // Vérifier que la date n'est pas dans le passé
        if (strtotime($data['date']) < strtotime(date('Y-m-d'))) {
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

    private function addParticipantsToSession(string $sessionId, array $participantIds): void
    {
        foreach ($participantIds as $participantId) {
            // Vérifier que l'utilisateur existe
            $user = $this->userRepository->findById($participantId);
            if (!$user) {
                continue; // Ignorer les utilisateurs inexistants
            }

            // Vérifier que l'utilisateur n'est pas déjà participant
            if (!$this->sportSessionRepository->isUserParticipant($sessionId, $participantId)) {
                $this->sportSessionRepository->addParticipant($sessionId, $participantId, 'pending');
            }
        }
    }

    private function createSessionCreatedNotification(SportSession $session): void
    {
        $sportName = DateFormatterService::getSportName($session->getSport());
        $this->notificationRepository->create([
            'user_id' => $session->getOrganizer()->getId(),
            'type' => 'update',
            'title' => 'Session créée',
            'message' => "Votre session de {$sportName} a été créée avec succès",
            'session_id' => $session->getId(),
        ]);
    }

    /**
     * Envoie des notifications push aux participants invités
     */
    private function sendInvitationNotifications(SportSession $session, array $participantIds): void
    {
        foreach ($participantIds as $participantId) {
            // Ignorer l'organisateur
            if ($participantId === $session->getOrganizer()->getId()) {
                continue;
            }

            try {
                // Créer une notification pour l'utilisateur invité
                $notification = $this->notificationRepository->create([
                    'user_id' => $participantId,
                    'type' => 'invitation',
                    'title' => DateFormatterService::generateInvitationTitle($session->getSport()),
                    'message' => DateFormatterService::generateInvitationMessage($session->getSport(), $session->getDate(), $session->getTime()),
                    'session_id' => $session->getId()
                ]);

                // Envoyer une notification push
                $this->sendPushNotification($participantId, $session, $notification);

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Erreur lors de l'envoi de notification d'invitation", [
                    'userId' => $participantId,
                    'sessionId' => $session->getId(),
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Envoie une notification push à l'utilisateur invité
     */
    private function sendPushNotification(string $userId, SportSession $session, $notification): void
    {
        try {
            // Récupérer les tokens push de l'utilisateur
            $pushTokens = $this->pushTokenRepository->getTokensForUser($userId);

            if (empty($pushTokens)) {
                \Illuminate\Support\Facades\Log::info("Aucun token push trouvé pour l'utilisateur", [
                    'userId' => $userId
                ]);
                return;
            }

            // Préparer le message de notification
            $title = DateFormatterService::generatePushInvitationTitle($session->getSport());
            $body = DateFormatterService::generateInvitationMessage($session->getSport(), $session->getDate(), $session->getTime());

            // Données supplémentaires pour l'app mobile
            $data = [
                'type' => 'session_invitation',
                'session_id' => $session->getId(),
                'notification_id' => $notification->getId(),
                'sport' => $session->getSport(),
                'date' => $session->getDate(),
                'time' => $session->getTime(),
                'location' => $session->getLocation()
            ];

            // Envoyer la notification push
            $result = $this->expoPushService->sendNotification(
                $pushTokens,
                $title,
                $body,
                $data
            );

            // Marquer la notification comme envoyée par push
            $this->notificationRepository->markAsPushSent($notification->getId(), $result);

            \Illuminate\Support\Facades\Log::info("Notification push envoyée pour invitation", [
                'userId' => $userId,
                'sessionId' => $session->getId(),
                'tokensCount' => count($pushTokens),
                'result' => $result
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erreur lors de l'envoi de notification push", [
                'userId' => $userId,
                'sessionId' => $session->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }
}
