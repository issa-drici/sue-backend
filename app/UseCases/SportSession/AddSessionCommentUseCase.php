<?php

namespace App\UseCases\SportSession;

use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Repositories\Notification\NotificationRepositoryInterface;
use App\Services\SocketIOService;
use Exception;

class AddSessionCommentUseCase
{
    public function __construct(
        private SportSessionRepositoryInterface $sportSessionRepository,
        private NotificationRepositoryInterface $notificationRepository,
        private SocketIOService $socketIOService
    ) {}

    public function execute(string $sessionId, string $userId, string $content): array
    {
        // Récupérer la session
        $session = $this->sportSessionRepository->findById($sessionId);

        if (!$session) {
            throw new Exception('Session non trouvée');
        }

        // Vérifier que l'utilisateur peut commenter (participant ou organisateur)
        if (!$session->canUserAccess($userId)) {
            throw new Exception('Vous n\'êtes pas autorisé à commenter cette session');
        }

        // Validation du contenu
        $this->validateComment($content);

        // Ajouter le commentaire
        $success = $this->sportSessionRepository->addComment($sessionId, $userId, $content);

        if (!$success) {
            throw new Exception('Erreur lors de l\'ajout du commentaire');
        }

        // Récupérer le commentaire ajouté
        $comments = $this->sportSessionRepository->getComments($sessionId);
        $newComment = end($comments); // Le dernier commentaire ajouté

        // Créer des notifications pour les autres participants
        $this->createCommentNotifications($session, $userId, $content);

        // Émettre l'événement WebSocket
        $this->emitWebSocketEvent($sessionId, $newComment);

        return $newComment;
    }

    private function validateComment(string $content): void
    {
        if (empty(trim($content))) {
            throw new Exception('Le contenu du commentaire est requis');
        }

        if (strlen($content) > 500) {
            throw new Exception('Le commentaire ne peut pas dépasser 500 caractères');
        }

        if (strlen($content) < 1) {
            throw new Exception('Le commentaire doit contenir au moins 1 caractère');
        }
    }

    private function createCommentNotifications($session, string $userId, string $content): void
    {
        $participants = $session->getParticipants();
        $organizerId = $session->getOrganizer()->getId();

        // Notifier tous les participants sauf l'auteur du commentaire
        foreach ($participants as $participant) {
            if ($participant['id'] !== $userId && $participant['status'] === 'accepted') {
                $this->notificationRepository->create([
                    'user_id' => $participant['id'],
                    'type' => 'update',
                    'title' => 'Nouveau commentaire',
                    'message' => "Nouveau commentaire sur la session de {$session->getSport()}",
                    'session_id' => $session->getId(),
                ]);
            }
        }

        // Notifier l'organisateur s'il n'est pas l'auteur
        if ($organizerId !== $userId) {
            $this->notificationRepository->create([
                'user_id' => $organizerId,
                'type' => 'update',
                'title' => 'Nouveau commentaire',
                'message' => "Nouveau commentaire sur votre session de {$session->getSport()}",
                'session_id' => $session->getId(),
            ]);
        }
    }

    private function emitWebSocketEvent(string $sessionId, array $comment): void
    {
        try {
            \Illuminate\Support\Facades\Log::info("Emitting WebSocket event for comment", [
                'sessionId' => $sessionId,
                'commentId' => $comment['id'] ?? 'unknown'
            ]);

            $this->socketIOService->emitLaravelEvent(
                'comment.created',
                'sport-session.' . $sessionId,
                ['comment' => $comment]
            );

            \Illuminate\Support\Facades\Log::info("WebSocket event emitted successfully", [
                'sessionId' => $sessionId,
                'event' => 'comment.created'
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to emit WebSocket event", [
                'sessionId' => $sessionId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
