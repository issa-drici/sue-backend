<?php

namespace App\UseCases\SportSession;

use App\Entities\SportSession;
use App\Exceptions\InvalidShareLinkException;
use App\Exceptions\SessionFullException;
use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Repositories\Notification\NotificationRepositoryInterface;
use App\Repositories\PushToken\PushTokenRepositoryInterface;
use App\Repositories\SportSessionComment\SportSessionCommentRepositoryInterface;
use App\Repositories\User\UserRepositoryInterface;
use App\Services\ExpoPushNotificationService;
use App\Services\DateFormatterService;
use App\UseCases\User\UpdateSportsPreferencesUseCase;
use App\Events\CommentCreated;

/**
 * Permet à un utilisateur authentifié de rejoindre une session à partir d'un
 * token de partage (Universal Link / lien WhatsApp). L'utilisateur devient
 * directement participant "accepted" (contrairement au flux d'invitation où il
 * doit d'abord être invité). L'opération est idempotente : rejoindre deux fois
 * renvoie simplement la session sans effet de bord.
 */
class JoinSessionByShareTokenUseCase
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

    public function execute(string $shareToken, string $userId): SportSession
    {
        // Résoudre le token (mêmes règles d'expiration que l'aperçu public)
        $session = $this->sportSessionRepository->findByShareToken($shareToken);

        if (!$session) {
            throw new InvalidShareLinkException('token_not_found');
        }

        if (!$session->isShareLinkActive()) {
            throw new InvalidShareLinkException('link_expired_or_cancelled');
        }

        $sessionId = $session->getId();

        // L'organisateur fait déjà partie de la session
        if ($session->isOrganizer($userId)) {
            return $session;
        }

        // Déjà participant accepté → idempotent, on renvoie la session telle quelle
        $participant = $this->sportSessionRepository->findParticipant($sessionId, $userId);
        if ($participant && $participant['status'] === 'accepted') {
            return $session;
        }

        // Vérifier la limite de participants avant d'ajouter
        $this->validateParticipantLimit($session);

        // Ajouter (ou réactiver si l'utilisateur avait été invité / avait décliné)
        if ($participant) {
            $this->sportSessionRepository->updateParticipantStatus($sessionId, $userId, 'accepted');
        } else {
            $this->sportSessionRepository->addParticipant($sessionId, $userId, 'accepted');
        }

        // Ajouter automatiquement le sport aux préférences de l'utilisateur
        $this->addSportToUserPreferences($userId, $session->getSport());

        // Notifier l'organisateur + commentaire système (comme pour l'acceptation d'invitation)
        $this->createJoinNotification($session, $userId);
        $this->createSystemComment($session, $userId);

        $updatedSession = $this->sportSessionRepository->findById($sessionId);

        if (!$updatedSession) {
            throw new \Exception('Erreur lors de la récupération de la session mise à jour');
        }

        return $updatedSession;
    }

    private function validateParticipantLimit(SportSession $session): void
    {
        $maxParticipants = $session->getMaxParticipants();

        if ($maxParticipants === null) {
            return;
        }

        if ($session->getAcceptedParticipantsCount() >= $maxParticipants) {
            throw new SessionFullException($maxParticipants);
        }
    }

    private function createJoinNotification(SportSession $session, string $userId): void
    {
        $organizerId = $session->getOrganizer()->getId();
        $sportName = DateFormatterService::getSportName($session->getSport());

        $title = 'Nouveau participant';
        $message = "Un participant a rejoint votre session de {$sportName} via un lien de partage";

        $notification = $this->notificationRepository->create([
            'user_id' => $organizerId,
            'type' => 'update',
            'title' => $title,
            'message' => $message,
            'session_id' => $session->getId(),
        ]);

        try {
            $tokens = $this->pushTokenRepository->getTokensForUser($organizerId);
            if (!empty($tokens)) {
                $data = [
                    'type' => 'session_update',
                    'session_id' => $session->getId(),
                    'notification_id' => $notification->getId(),
                ];
                $this->expoService->sendNotification($tokens, $title, $message, $data);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error sending push for share-link join', [
                'sessionId' => $session->getId(),
                'organizerId' => $organizerId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function createSystemComment(SportSession $session, string $userId): void
    {
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            return;
        }

        $comment = $this->commentRepository->createComment(
            sessionId: $session->getId(),
            userId: $userId,
            content: 'a rejoint cette session via un lien de partage 🔗'
        );

        try {
            broadcast(new CommentCreated($comment, $session->getId()));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to broadcast event for share-link join comment', [
                'sessionId' => $session->getId(),
                'userId' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function addSportToUserPreferences(string $userId, string $sport): void
    {
        try {
            $this->updateSportsPreferencesUseCase->addSportToPreferences($userId, $sport);
        } catch (\Exception $e) {
            // Ignorer silencieusement pour ne pas faire échouer le join
        }
    }
}
