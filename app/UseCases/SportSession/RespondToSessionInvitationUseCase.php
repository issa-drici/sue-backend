<?php

namespace App\UseCases\SportSession;

use App\Entities\SportSession;
use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Repositories\Notification\NotificationRepositoryInterface;
use App\Repositories\PushToken\PushTokenRepositoryInterface;
use App\Services\ExpoPushNotificationService;
use App\Services\DateFormatterService;
use App\Repositories\SportSessionComment\SportSessionCommentRepositoryInterface;
use App\Repositories\User\UserRepositoryInterface;
use App\UseCases\User\UpdateSportsPreferencesUseCase;
use App\Events\CommentCreated;
use Exception;

class RespondToSessionInvitationUseCase
{
    public function __construct(
        private SportSessionRepositoryInterface $sportSessionRepository,
        private NotificationRepositoryInterface $notificationRepository,
        private SportSessionCommentRepositoryInterface $commentRepository,
        private UserRepositoryInterface $userRepository,
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

        // Validation de la réponse
        if (!in_array($response, ['accept', 'decline'])) {
            throw new Exception('Réponse invalide. Utilisez \'accept\' ou \'decline\'');
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

        // Ajouter un commentaire système
        $this->createSystemComment($session, $userId, $response);

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

            private function createSystemComment(SportSession $session, string $userId, string $response): void
    {
        // Récupérer les informations de l'utilisateur
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            return; // Ne pas créer de commentaire si l'utilisateur n'existe pas
        }

        $userName = $user->getFirstname() . ' ' . $user->getLastname();

        // Créer le message du commentaire système
        $message = $response === 'accept'
            ? "a accepté l'invitation à cette session ✅"
            : "a décliné l'invitation à cette session ❌";

        // Créer le commentaire système
        $comment = $this->commentRepository->createComment(
            sessionId: $session->getId(),
            userId: $userId,
            content: $message
        );

        // Émettre l'événement Laravel Broadcasting pour le temps réel
        try {
            broadcast(new CommentCreated($comment, $session->getId()));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to broadcast event for system comment", [
                'sessionId' => $session->getId(),
                'userId' => $userId,
                'response' => $response,
                'error' => $e->getMessage()
            ]);
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
