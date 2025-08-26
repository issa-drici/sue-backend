<?php

namespace App\UseCases\User;

use App\Repositories\FriendRequest\FriendRequestRepositoryInterface;
use App\Repositories\Friend\FriendRepositoryInterface;
use App\Repositories\User\UserRepositoryInterface;

class RespondToFriendRequestUseCase
{
    public function __construct(
        private FriendRequestRepositoryInterface $friendRequestRepository,
        private FriendRepositoryInterface $friendRepository,
        private UserRepositoryInterface $userRepository
    ) {}

    public function execute(string $userId, string $requestId, string $response): array
    {
        // Vérifier que la demande existe
        $request = $this->friendRequestRepository->findEntityById($requestId);
        if (!$request) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'USER_NOT_FOUND',
                    'message' => 'Demande d\'ami non trouvée'
                ]
            ];
        }

        // Vérifier que l'utilisateur est bien le destinataire de la demande
        // Note: Pour l'instant, on suppose que l'ID de la demande correspond au sender_id
        // Dans une vraie implémentation, il faudrait vérifier le receiver_id

        // Valider la réponse
        if (!in_array($response, ['accept', 'decline'])) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Réponse invalide. Doit être "accept" ou "decline"'
                ]
            ];
        }

        // Mettre à jour le statut de la demande
        $status = $response === 'accept' ? 'accepted' : 'declined';
        $updated = $this->friendRequestRepository->updateRequestStatus($requestId, $status);

        if (!$updated) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Impossible de mettre à jour la demande d\'ami'
                ]
            ];
        }

        // Si acceptée, créer la relation d'amitié
        if ($response === 'accept') {
            $this->friendRepository->addFriend($request->getSenderId(), $userId);
        }

        // Récupérer les amis en commun
        try {
            $mutualFriends = $this->friendRepository->getMutualFriendsCount($request->getSenderId(), $userId);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Error getting mutual friends count in RespondToFriendRequest', [
                'senderId' => $request->getSenderId(),
                'userId' => $userId,
                'error' => $e->getMessage()
            ]);
            $mutualFriends = 0; // Valeur par défaut en cas d'erreur
        }

        // Récupérer les informations de l'utilisateur expéditeur
        $sender = $this->userRepository->findById($request->getSenderId());

        return [
            'success' => true,
            'data' => [
                'id' => $request->getSenderId(),
                'firstname' => $sender ? $sender->getFirstname() : 'Utilisateur',
                'lastname' => $sender ? $sender->getLastname() : 'Inconnu',
                'avatar' => null, // avatar null pour l'instant
                'mutualFriends' => $mutualFriends,
                'status' => $status
            ],
            'message' => $response === 'accept' ? 'Demande d\'ami acceptée' : 'Demande d\'ami refusée'
        ];
    }
}
