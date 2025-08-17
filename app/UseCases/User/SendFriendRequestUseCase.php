<?php

namespace App\UseCases\User;

use App\Repositories\FriendRequest\FriendRequestRepositoryInterface;
use App\Repositories\Friend\FriendRepositoryInterface;
use App\Repositories\User\UserRepositoryInterface;
use App\Repositories\Notification\NotificationRepositoryInterface;
use App\Repositories\PushToken\PushTokenRepositoryInterface;
use App\Services\ExpoPushNotificationService;
use App\Entities\FriendRequest;

class SendFriendRequestUseCase
{
    public function __construct(
        private FriendRequestRepositoryInterface $friendRequestRepository,
        private FriendRepositoryInterface $friendRepository,
        private UserRepositoryInterface $userRepository,
        private NotificationRepositoryInterface $notificationRepository,
        private PushTokenRepositoryInterface $pushTokenRepository,
        private ExpoPushNotificationService $expoService
    ) {}

    public function execute(string $senderId, string $receiverId): array
    {
        // Vérifier que l'utilisateur existe
        $receiver = $this->userRepository->findById($receiverId);
        if (!$receiver) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'USER_NOT_FOUND',
                    'message' => 'Utilisateur non trouvé'
                ]
            ];
        }

        // Vérifier qu'on ne s'envoie pas une demande à soi-même
        if ($senderId === $receiverId) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Vous ne pouvez pas vous envoyer une demande d\'ami à vous-même'
                ]
            ];
        }

        // Vérifier qu'ils ne sont pas déjà amis
        if ($this->friendRepository->areFriends($senderId, $receiverId)) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Vous êtes déjà amis avec cet utilisateur'
                ]
            ];
        }

        // Note: La vérification d'existence est maintenant gérée dans createRequest
        // qui peut réactiver une demande annulée

        // Créer la demande d'ami
        $result = $this->friendRequestRepository->createRequest($senderId, $receiverId);

        if (!$result['success']) {
            return $result; // Retourner l'erreur du repository
        }

        // Notifier le destinataire + push
        $notification = $this->notificationRepository->create([
            'user_id' => $receiverId,
            'type' => 'friend_request',
            'title' => 'Nouvelle demande d\'ami',
            'message' => 'Vous avez reçu une demande d\'ami',
        ]);

        try {
            $tokens = $this->pushTokenRepository->getTokensForUser($receiverId);
            if (!empty($tokens)) {
                $data = [
                    'type' => 'friend_request',
                    'notification_id' => $notification->getId(),
                    'user_id' => $senderId,
                ];
                $title = 'Demande d\'ami';
                $sender = $this->userRepository->findById($senderId);
                $senderName = $sender ? ($sender->getFirstname() . ' ' . $sender->getLastname()) : 'Un utilisateur';
                $body = $senderName . " vous a envoyé une demande d'ami";
                $this->expoService->sendNotification($tokens, $title, $body, $data);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error sending push for friend request', [
                'receiverId' => $receiverId,
                'error' => $e->getMessage(),
            ]);
        }

        // Récupérer les amis en commun
        $mutualFriends = $this->friendRepository->getMutualFriendsCount($senderId, $receiverId);

        return [
            'success' => true,
            'data' => [
                'id' => $receiverId,
                            'firstname' => $receiver->getFirstname(),
            'lastname' => $receiver->getLastname(),
                'avatar' => null, // avatar null pour l'instant
                'mutualFriends' => $mutualFriends
            ],
            'message' => 'Demande d\'ami envoyée'
        ];
    }
}
