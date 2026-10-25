<?php

namespace App\UseCases\SportSession;

use App\Entities\SportSession;
use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Repositories\Notification\NotificationRepositoryInterface;
use App\Repositories\User\UserRepositoryInterface;
use App\Repositories\PushToken\PushTokenRepositoryInterface;
use App\Services\ExpoPushNotificationService;
use App\Services\DateFormatterService;
use App\UseCases\User\UpdateSportsPreferencesUseCase;
use Exception;

class CreateSportSessionUseCase
{
    public function __construct(
        private SportSessionRepositoryInterface $sportSessionRepository,
        private NotificationRepositoryInterface $notificationRepository,
        private UserRepositoryInterface $userRepository,
        private PushTokenRepositoryInterface $pushTokenRepository,
        private ExpoPushNotificationService $expoPushService,
        private UpdateSportsPreferencesUseCase $updateSportsPreferencesUseCase
    ) {}

    public function execute(array $data): SportSession
    {
        // Validation des données
        $this->validateData($data);

        // Créer la session
        $session = $this->sportSessionRepository->create($data);

        // Ajouter automatiquement le sport aux préférences de l'organisateur
        $this->addSportToOrganizerPreferences($data['organizer_id'], $data['sport']);

        // Ajouter les participants si spécifiés
        if (isset($data['participantIds']) && !empty($data['participantIds'])) {
            $this->addParticipantsToSession($session->getId(), $data['participantIds']);
        }

        // Créer une notification pour l'organisateur
        // $this->createSessionCreatedNotification($session);

        // Note: Les notifications d'invitation sont envoyées uniquement via l'endpoint d'invitation classique
        // pour éviter les doublons

        return $session;
    }

    private function validateData(array $data): void
    {
        if (!isset($data['sport']) || !\App\Services\SportService::isValidSport($data['sport'])) {
            throw new Exception('Sport invalide');
        }

        if (($data['scheduling'] ?? 'scheduled') === 'finding_slot') {
            $this->validateSlotProposals($data['slotProposals'] ?? []);
        } else {
            $this->validateTimeRange($data);

            // Vérifier que la date n'est pas dans le passé
            if (strtotime($data['date']) < strtotime(date('Y-m-d'))) {
                throw new Exception('La date ne peut pas être dans le passé');
            }
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

        // Validation de pricePerPerson
        if (isset($data['pricePerPerson']) && $data['pricePerPerson'] !== null && $data['pricePerPerson'] < 0) {
            throw new Exception('Le prix par personne ne peut pas être négatif');
        }

        // Vérifier que l'organisateur existe
        $organizer = $this->userRepository->findById($data['organizer_id']);
        if (!$organizer) {
            throw new Exception('Organisateur non trouvé');
        }
    }

    private function validateTimeRange(array $range): void
    {
        if (!isset($range['date']) || !$this->isValidDate($range['date'])) {
            throw new Exception('Date invalide');
        }

        if (!isset($range['startTime']) || !$this->isValidTime($range['startTime'])) {
            throw new Exception('Heure de début invalide');
        }

        if (!isset($range['endTime']) || !$this->isValidTime($range['endTime'])) {
            throw new Exception('Heure de fin invalide');
        }

        // Une fin avant le début tombe le lendemain : seule une durée nulle est refusée
        if ($range['endTime'] === $range['startTime']) {
            throw new Exception('L\'heure de fin doit être différente de l\'heure de début');
        }
    }

    private function validateSlotProposals(array $slots): void
    {
        if (empty($slots)) {
            throw new Exception('Au moins un créneau doit être proposé');
        }

        $seen = [];
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        foreach ($slots as $slot) {
            $this->validateTimeRange($slot);

            $start = new \DateTime($slot['date'] . ' ' . $slot['startTime'], new \DateTimeZone('Europe/Paris'));
            if ($start < $now) {
                throw new Exception('Un créneau proposé est déjà passé');
            }

            $key = $slot['date'] . ' ' . $slot['startTime'] . ' ' . $slot['endTime'];
            if (isset($seen[$key])) {
                throw new Exception('Le même créneau est proposé deux fois');
            }
            $seen[$key] = true;
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
     * Ajoute automatiquement le sport aux préférences de l'organisateur
     */
    private function addSportToOrganizerPreferences(string $organizerId, string $sport): void
    {
        try {
            $this->updateSportsPreferencesUseCase->addSportToPreferences($organizerId, $sport);
        } catch (\Exception $e) {
            // Ignorer silencieusement l'erreur pour ne pas faire échouer la création de session
        }
    }
}
