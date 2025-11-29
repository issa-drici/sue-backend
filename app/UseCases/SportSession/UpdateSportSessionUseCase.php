<?php

namespace App\UseCases\SportSession;

use App\Entities\SportSession;
use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Repositories\Notification\NotificationRepositoryInterface;
use App\Repositories\PushToken\PushTokenRepositoryInterface;
use App\Services\ExpoPushNotificationService;
use App\Services\SportService;
use Exception;

class UpdateSportSessionUseCase
{
    public function __construct(
        private SportSessionRepositoryInterface $sportSessionRepository,
        private NotificationRepositoryInterface $notificationRepository,
        private PushTokenRepositoryInterface $pushTokenRepository,
        private ExpoPushNotificationService $pushNotificationService
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

        // Sauvegarder l'ancienne session pour comparer les changements
        $oldSession = $session;

        // Mettre à jour la session
        $updatedSession = $this->sportSessionRepository->update($sessionId, $data);

        if (!$updatedSession) {
            throw new Exception('Erreur lors de la mise à jour de la session');
        }

        // Récupérer la session mise à jour complète
        $updatedSession = $this->sportSessionRepository->findById($sessionId);

        // Détecter les changements
        $changes = $this->detectChanges($oldSession, $updatedSession, $data);

        // Créer une notification pour les participants
        $this->createSessionUpdatedNotification($oldSession, $updatedSession, $changes);

        // Envoyer des notifications push
        $this->sendPushNotifications($oldSession, $updatedSession, $changes);

        return $updatedSession;
    }

    private function validateUpdateData(array $data): void
    {
        // Validation du sport
        if (isset($data['sport']) && !SportService::isValidSport($data['sport'])) {
            throw new Exception('Sport invalide');
        }

        if (isset($data['startTime']) && !$this->isValidTime($data['startTime'])) {
            throw new Exception('Heure de début invalide');
        }

        if (isset($data['endTime']) && !$this->isValidTime($data['endTime'])) {
            throw new Exception('Heure de fin invalide');
        }

        // Vérifier que l'heure de fin est après l'heure de début si les deux sont fournis
        if (isset($data['startTime']) && isset($data['endTime'])) {
            if (strtotime($data['endTime']) <= strtotime($data['startTime'])) {
                throw new Exception('L\'heure de fin doit être après l\'heure de début');
            }
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

        // Validation de maxParticipants
        if (isset($data['maxParticipants']) && $data['maxParticipants'] !== null && ($data['maxParticipants'] < 1 || $data['maxParticipants'] > 100)) {
            throw new Exception('Le nombre maximum de participants doit être entre 1 et 100');
        }

        // Validation de pricePerPerson
        if (isset($data['pricePerPerson']) && $data['pricePerPerson'] !== null && $data['pricePerPerson'] < 0) {
            throw new Exception('Le prix par personne ne peut pas être négatif');
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

    private function detectChanges(SportSession $oldSession, SportSession $newSession, array $data): array
    {
        $changes = [];

        // Comparer chaque champ modifié
        if (isset($data['sport']) && $oldSession->getSport() !== $newSession->getSport()) {
            $changes['sport'] = [
                'old' => $oldSession->getSport(),
                'new' => $newSession->getSport()
            ];
        }

        if (isset($data['date']) && $oldSession->getDate() !== $newSession->getDate()) {
            $changes['date'] = [
                'old' => $oldSession->getDate(),
                'new' => $newSession->getDate()
            ];
        }

        if (isset($data['startTime']) && $oldSession->getStartTime() !== $newSession->getStartTime()) {
            $changes['startTime'] = [
                'old' => $oldSession->getStartTime(),
                'new' => $newSession->getStartTime()
            ];
        }

        if (isset($data['endTime']) && $oldSession->getEndTime() !== $newSession->getEndTime()) {
            $changes['endTime'] = [
                'old' => $oldSession->getEndTime(),
                'new' => $newSession->getEndTime()
            ];
        }

        if (isset($data['location']) && $oldSession->getLocation() !== $newSession->getLocation()) {
            $changes['location'] = [
                'old' => $oldSession->getLocation(),
                'new' => $newSession->getLocation()
            ];
        }

        if (isset($data['maxParticipants'])) {
            $oldMax = $oldSession->getMaxParticipants();
            $newMax = $newSession->getMaxParticipants();
            if ($oldMax !== $newMax) {
                $changes['maxParticipants'] = [
                    'old' => $oldMax,
                    'new' => $newMax
                ];
            }
        }

        if (isset($data['pricePerPerson'])) {
            $oldPrice = $oldSession->getPricePerPerson();
            $newPrice = $newSession->getPricePerPerson();
            if ($oldPrice != $newPrice) {
                $changes['pricePerPerson'] = [
                    'old' => $oldPrice,
                    'new' => $newPrice
                ];
            }
        }

        return $changes;
    }

    private function formatChangesMessage(array $changes): string
    {
        if (empty($changes)) {
            return '';
        }

        $messages = [];

        foreach ($changes as $field => $values) {
            $oldValue = $values['old'];
            $newValue = $values['new'];

            // Formatage spécial pour certains champs
            if ($field === 'pricePerPerson') {
                $oldValue = $oldValue !== null ? number_format($oldValue, 2, ',', ' ') . ' €' : 'Gratuit';
                $newValue = $newValue !== null ? number_format($newValue, 2, ',', ' ') . ' €' : 'Gratuit';
                $messages[] = "Prix changé : {$newValue}";
            } elseif ($field === 'maxParticipants') {
                $oldValue = $oldValue !== null ? (string)$oldValue : 'Illimité';
                $newValue = $newValue !== null ? (string)$newValue : 'Illimité';
                $messages[] = "Participants max : {$newValue}";
            } elseif ($field === 'sport') {
                $oldValue = \App\Services\SportService::getFormattedSportName($oldValue);
                $newValue = \App\Services\SportService::getFormattedSportName($newValue);
                $messages[] = "Sport changé : {$newValue}";
            } elseif ($field === 'date') {
                $newValue = \App\Services\DateFormatterService::formatDate($newValue);
                $messages[] = "Date : {$newValue}";
            } elseif ($field === 'startTime') {
                $newValue = \App\Services\DateFormatterService::formatTime($newValue);
                $messages[] = "Début à {$newValue}";
            } elseif ($field === 'endTime') {
                $newValue = \App\Services\DateFormatterService::formatTime($newValue);
                $messages[] = "Fin à {$newValue}";
            } elseif ($field === 'location') {
                $messages[] = "Lieu : {$newValue}";
            }
        }

        return implode(', ', $messages);
    }

    private function createSessionUpdatedNotification(SportSession $oldSession, SportSession $newSession, array $changes): void
    {
        $changesMessage = $this->formatChangesMessage($changes);
        $sportName = \App\Services\SportService::getFormattedSportName($oldSession->getSport());

        foreach ($oldSession->getParticipants() as $participant) {
            // Ne pas notifier l'organisateur lui-même
            if ($participant['id'] === $oldSession->getOrganizer()->getId()) {
                continue;
            }

            // Notifier les participants qui ont accepté ou sont en attente
            if ($participant['status'] === 'accepted' || $participant['status'] === 'pending') {
                $message = "Session de {$sportName} modifiée";
                if (!empty($changesMessage)) {
                    $message .= ". " . $changesMessage;
                }

                $this->notificationRepository->create([
                    'user_id' => $participant['id'],
                    'type' => 'session_update',
                    'title' => 'Session modifiée',
                    'message' => $message,
                    'session_id' => $newSession->getId(),
                    'push_data' => [
                        'type' => 'session_update',
                        'session_id' => $newSession->getId(),
                        'organizer_id' => $newSession->getOrganizer()->getId(),
                        'changes' => $changes,
                    ],
                ]);
            }
        }
    }

    private function sendPushNotifications(SportSession $oldSession, SportSession $newSession, array $changes): void
    {
        $changesMessage = $this->formatChangesMessage($changes);
        $sportName = \App\Services\SportService::getFormattedSportName($oldSession->getSport());

        foreach ($oldSession->getParticipants() as $participant) {
            // Ne pas notifier l'organisateur lui-même
            if ($participant['id'] === $oldSession->getOrganizer()->getId()) {
                continue;
            }

            // Envoyer seulement aux participants qui ont accepté ou sont en attente
            if ($participant['status'] === 'accepted' || $participant['status'] === 'pending') {
                // Récupérer les tokens push de l'utilisateur
                $pushTokens = $this->pushTokenRepository->getTokensForUser($participant['id']);
                
                if (!empty($pushTokens)) {
                    $message = "Session de {$sportName} modifiée";
                    if (!empty($changesMessage)) {
                        $message .= ". " . $changesMessage;
                    }

                    $this->pushNotificationService->sendNotification(
                        $pushTokens,
                        'Session modifiée',
                        $message,
                        [
                            'type' => 'session_update',
                            'session_id' => $newSession->getId(),
                            'organizer_id' => $newSession->getOrganizer()->getId(),
                            'changes' => $changes,
                        ]
                    );
                }
            }
        }
    }
}
