<?php

namespace App\UseCases\SportSession;

use App\Entities\SportSession;
use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Repositories\Notification\NotificationRepositoryInterface;
use App\Repositories\PushToken\PushTokenRepositoryInterface;
use App\Services\ExpoPushNotificationService;
use App\Services\DateFormatterService;
use App\UseCases\User\UpdateSportsPreferencesUseCase;
use Exception;

class RespondToSessionInvitationUseCase
{
    public function __construct(
        private SportSessionRepositoryInterface $sportSessionRepository,
        private NotificationRepositoryInterface $notificationRepository,
        private PushTokenRepositoryInterface $pushTokenRepository,
        private ExpoPushNotificationService $expoService,
        private UpdateSportsPreferencesUseCase $updateSportsPreferencesUseCase
    ) {}

    public function execute(string $sessionId, string $userId, string $response): SportSession
    {
        // Récupérer la session
        $session = $this->sportSessionRepository->findById($sessionId);

        if (!$session) {
            throw new Exception('Session non trouvée');
        }

        // Vérifier que l'utilisateur est un participant
        if (!$session->isParticipant($userId)) {
            throw new Exception('Vous n\'êtes pas invité à cette session');
        }

        // Plus de réponse possible sur une session annulée ou terminée
        if ($session->getStatus() === 'cancelled') {
            throw new Exception('Cette session est annulée.');
        }
        if ($session->getEndDate() < now()) {
            throw new Exception('Cette session est terminée.');
        }

        // Validation de la réponse
        if (!in_array($response, ['accept', 'decline'])) {
            throw new Exception('Réponse invalide. Utilisez \'accept\' ou \'decline\'');
        }

        // Créneau pas encore choisi : on donne ses disponibilités au lieu d'accepter
        if ($response === 'accept' && !$session->isScheduled()) {
            throw new Exception('Réponse invalide : indiquez plutôt vos disponibilités');
        }

        // Si l'utilisateur veut accepter, vérifier la limite de participants
        if ($response === 'accept') {
            $this->validateParticipantLimit($session);
        }

        // Déterminer le statut
        $status = $response === 'accept' ? 'accepted' : 'declined';

        // Mettre à jour le statut du participant
        $success = $this->sportSessionRepository->updateParticipantStatus($sessionId, $userId, $status);

        if (!$success) {
            throw new Exception('Erreur lors de la mise à jour du statut');
        }

        // Si l'utilisateur accepte, ajouter automatiquement le sport à ses préférences
        if ($response === 'accept') {
            $this->addSportToUserPreferences($userId, $session->getSport());
        }

        // Créer une notification pour l'organisateur
        $this->createResponseNotification($session, $userId, $response);

        // Récupérer la session mise à jour
        $updatedSession = $this->sportSessionRepository->findById($sessionId);

        if (!$updatedSession) {
            throw new Exception('Erreur lors de la récupération de la session mise à jour');
        }

        return $updatedSession;
    }

    private function createResponseNotification(SportSession $session, string $userId, string $response): void
    {
        $organizerId = $session->getOrganizer()->getId();

        $sportName = DateFormatterService::getSportName($session->getSport());
        $message = $response === 'accept'
            ? "Un participant a accepté votre invitation à la session de {$sportName}"
            : "Un participant a décliné votre invitation à la session de {$sportName}";

        $title = $response === 'accept' ? 'Invitation acceptée' : 'Invitation déclinée';

        $notification = $this->notificationRepository->create([
            'user_id' => $organizerId,
            'type' => 'update',
            'title' => $title,
            'message' => $message,
            'session_id' => $session->getId(),
        ]);

        // Push à l'organisateur
        try {
            $tokens = $this->pushTokenRepository->getTokensForUser($organizerId);
            if (!empty($tokens)) {
                $data = [
                    'type' => 'session_update',
                    'session_id' => $session->getId(),
                    'notification_id' => $notification->getId(),
                ];
                $this->expoService->sendNotification(
                    $tokens,
                    $title,
                    $message,
                    $data
                );
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error sending push for invitation response', [
                'sessionId' => $session->getId(),
                'organizerId' => $organizerId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function validateParticipantLimit(SportSession $session): void
    {
        $maxParticipants = $session->getMaxParticipants();

        // Si pas de limite, on peut accepter
        if ($maxParticipants === null) {
            return;
        }

        // Compter les participants acceptés
        $acceptedParticipants = 0;
        foreach ($session->getParticipants() as $participant) {
            if ($participant['status'] === 'accepted') {
                $acceptedParticipants++;
            }
        }

        // Vérifier si on peut encore accepter des participants
        if ($acceptedParticipants >= $maxParticipants) {
            throw new Exception("Impossible d'accepter l'invitation : la session a atteint sa limite de {$maxParticipants} participants");
        }
    }

    /**
     * Ajoute automatiquement le sport aux préférences de l'utilisateur
     */
    private function addSportToUserPreferences(string $userId, string $sport): void
    {
        try {
            $this->updateSportsPreferencesUseCase->addSportToPreferences($userId, $sport);
        } catch (\Exception $e) {
            // Ignorer silencieusement l'erreur pour ne pas faire échouer l'acceptation de l'invitation
        }
    }
}
