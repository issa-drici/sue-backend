<?php

namespace App\UseCases\SportSessionComment;

use App\Entities\SportSessionComment;
use App\Events\CommentCreated;
use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Repositories\Notification\NotificationRepositoryInterface;
use App\Repositories\PushToken\PushTokenRepositoryInterface;
use App\Services\ExpoPushNotificationService;
use App\Repositories\SportSessionComment\SportSessionCommentRepositoryInterface;
use App\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Facades\Validator;

class CreateCommentUseCase
{
    public function __construct(
        private SportSessionCommentRepositoryInterface $commentRepository,
        private SportSessionRepositoryInterface $sessionRepository,
        private UserRepositoryInterface $userRepository,
        private NotificationRepositoryInterface $notificationRepository,
        private PushTokenRepositoryInterface $pushTokenRepository,
        private ExpoPushNotificationService $expoService
    ) {}

    public function execute(string $sessionId, string $userId, string $content, ?array $mentions = null): array
    {
        // Validation des données
        $validator = Validator::make([
            'sessionId' => $sessionId,
            'userId' => $userId,
            'content' => $content,
            'mentions' => $mentions,
        ], [
            'sessionId' => 'required|uuid|exists:sport_sessions,id',
            'userId' => 'required|uuid|exists:users,id',
            'content' => 'required|string|min:1|max:1000',
            'mentions' => 'nullable|array',
            'mentions.*' => 'uuid|exists:users,id',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Données invalides',
                    'details' => $validator->errors()->toArray(),
                ],
            ];
        }

        // Vérifier que l'utilisateur est participant de la session
        $session = $this->sessionRepository->findById($sessionId);
        if (!$session) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'SESSION_NOT_FOUND',
                    'message' => 'Session non trouvée',
                ],
            ];
        }

        // Vérifier que l'utilisateur est participant
        // $isParticipant = $this->sessionRepository->isUserParticipant($sessionId, $userId);
        // if (!$isParticipant) {
        //     return [
        //         'success' => false,
        //         'error' => [
        //             'code' => 'NOT_PARTICIPANT',
        //             'message' => 'Vous devez être participant de cette session pour commenter',
        //         ],
        //     ];
        // }

        // Créer le commentaire
        $comment = $this->commentRepository->createComment($sessionId, $userId, $content, $mentions);

        // Émettre l'événement Laravel Broadcasting (Soketi)
        try {
            broadcast(new CommentCreated($comment, $sessionId));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to broadcast event", [
                'sessionId' => $sessionId,
                'error' => $e->getMessage()
            ]);
        }

        // Envoyer des notifications push aux autres participants (hors auteur)
        $this->sendPushToParticipants($sessionId, $userId, $comment);

        return [
            'success' => true,
            'data' => $comment->toArray(),
            'message' => 'Commentaire créé avec succès',
        ];
    }

    private function sendPushToParticipants(string $sessionId, string $authorId, SportSessionComment $comment): void
    {
        try {
            $session = $this->sessionRepository->findById($sessionId);
            if (!$session) {
                return;
            }

            $participants = $session->getParticipants();
            foreach ($participants as $participant) {
                if (($participant['id'] ?? null) === $authorId) {
                    continue;
                }
                if (($participant['status'] ?? null) !== 'accepted') {
                    continue;
                }

                $notification = $this->notificationRepository->create([
                    'user_id' => $participant['id'],
                    'type' => 'comment',
                    'title' => 'Nouveau commentaire',
                    'message' => mb_strimwidth($comment->content, 0, 60, '…'),
                    'session_id' => $sessionId,
                ]);

                $tokens = $this->pushTokenRepository->getTokensForUser($participant['id']);
                if (empty($tokens)) {
                    continue;
                }

                $data = [
                    'type' => 'comment',
                    'session_id' => $sessionId,
                    'notification_id' => $notification->getId(),
                ];

                $author = $this->userRepository->findById($authorId);
                $authorName = $author ? ($author->getFirstname() . ' ' . $author->getLastname()) : 'Un participant';

                $this->expoService->sendNotification(
                    $tokens,
                    $authorName . ' a commenté',
                    mb_strimwidth($comment->content, 0, 60, '…'),
                    $data
                );
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error sending push for comment', [
                'sessionId' => $sessionId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
