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
use App\Services\DateFormatterService;
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
                \Illuminate\Support\Facades\Log::warning('Session not found for push notification', [
                    'sessionId' => $sessionId
                ]);
                return;
            }

            $participants = $session->getParticipants();

            // Récupérer le nom de l'auteur
            $author = $this->userRepository->findById($authorId);
            $authorName = $author ? ($author->getFirstname() . ' ' . $author->getLastname()) : 'Un participant';
            $authorFirstName = $author ? $author->getFirstname() : 'Un participant';

            // Filtrer les participants acceptés (hors auteur)
            $targetParticipants = array_filter($participants, function($participant) use ($authorId) {
                return ($participant['id'] ?? null) !== $authorId &&
                       ($participant['status'] ?? null) === 'accepted';
            });

            // CORRECTION: S'assurer que l'organisateur est inclus dans les notifications
            // même s'il n'est pas dans la liste des participants
            $organizerId = $session->getOrganizer()->getId();
            $organizerAlreadyIncluded = collect($targetParticipants)->contains('id', $organizerId);

            if (!$organizerAlreadyIncluded && $organizerId !== $authorId) {
                // Ajouter l'organisateur à la liste des destinataires
                $organizer = $this->userRepository->findById($organizerId);
                if ($organizer) {
                    $targetParticipants[] = [
                        'id' => $organizerId,
                        'fullName' => $organizer->getFirstname() . ' ' . $organizer->getLastname(),
                        'status' => 'accepted'
                    ];
                }
            }

            if (empty($targetParticipants)) {
                \Illuminate\Support\Facades\Log::info('No target participants found for push notification', [
                    'sessionId' => $sessionId,
                    'authorId' => $authorId,
                    'totalParticipants' => count($participants)
                ]);
                return;
            }

            \Illuminate\Support\Facades\Log::info('Sending push notifications for comment', [
                'sessionId' => $sessionId,
                'authorId' => $authorId,
                'authorName' => $authorName,
                'targetParticipantsCount' => count($targetParticipants),
                'targetParticipantIds' => array_column($targetParticipants, 'id')
            ]);

            // Traiter chaque participant individuellement (comme pour les invitations)

            foreach ($targetParticipants as $participant) {
                $notification = $this->notificationRepository->create([
                    'user_id' => $participant['id'],
                    'type' => 'comment',
                    'title' => DateFormatterService::generateCommentTitle($session->getSport()),
                    'message' => DateFormatterService::generateCommentMessageShort($authorName, $session->getSport()),
                    'session_id' => $sessionId,
                ]);

                // Récupérer les tokens pour cet utilisateur spécifique
                $tokens = $this->pushTokenRepository->getTokensForUser($participant['id']);

                \Illuminate\Support\Facades\Log::info('Tokens found for participant', [
                    'sessionId' => $sessionId,
                    'participantId' => $participant['id'],
                    'participantName' => $participant['fullName'] ?? 'Unknown',
                    'tokensCount' => count($tokens)
                ]);

                if (empty($tokens)) {
                    \Illuminate\Support\Facades\Log::warning('No push tokens found for participant', [
                        'sessionId' => $sessionId,
                        'participantId' => $participant['id'],
                        'participantName' => $participant['fullName'] ?? 'Unknown'
                    ]);
                    continue;
                }

                $data = [
                    'type' => 'comment',
                    'session_id' => $sessionId,
                    'notification_id' => $notification->getId(),
                ];

                $result = $this->expoService->sendNotification(
                    $tokens,
                    DateFormatterService::generatePushCommentTitleWithDate($session->getSport(), $session->getDate(), $authorFirstName),
                    DateFormatterService::generatePushCommentMessageShort($comment->content),
                    $data
                );

                // Gérer les tokens invalides
                if (isset($result['results']) && is_array($result['results'])) {
                    foreach ($result['results'] as $expoResult) {
                        if (isset($expoResult['invalid_tokens']) && is_array($expoResult['invalid_tokens'])) {
                            foreach ($expoResult['invalid_tokens'] as $invalidToken) {
                                \Illuminate\Support\Facades\Log::warning('Invalid token detected, removing from database', [
                                    'token' => $invalidToken,
                                    'participantId' => $participant['id']
                                ]);
                                $this->pushTokenRepository->deleteToken($invalidToken);
                            }
                        }
                    }
                }

                \Illuminate\Support\Facades\Log::info('Push notification sent for participant', [
                    'sessionId' => $sessionId,
                    'participantId' => $participant['id'],
                    'participantName' => $participant['fullName'] ?? 'Unknown',
                    'tokensCount' => count($tokens),
                    'result' => $result
                ]);

                // Marquer la notification comme envoyée par push
                $this->notificationRepository->markAsPushSent($notification->getId(), $result);
            }

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error sending push for comment', [
                'sessionId' => $sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
