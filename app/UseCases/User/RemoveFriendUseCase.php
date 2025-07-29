<?php

namespace App\UseCases\User;

use App\Repositories\Friend\FriendRepositoryInterface;
use App\Repositories\User\UserRepositoryInterface;
use App\Repositories\FriendRequest\FriendRequestRepositoryInterface;

class RemoveFriendUseCase
{
    public function __construct(
        private FriendRepositoryInterface $friendRepository,
        private UserRepositoryInterface $userRepository,
        private FriendRequestRepositoryInterface $friendRequestRepository
    ) {}

    public function execute(string $userId, string $friendId): array
    {
        // Vérifier que l'ID d'ami est valide
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $friendId)) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'INVALID_FRIEND_ID',
                    'message' => 'ID d\'ami invalide'
                ]
            ];
        }

        // Vérifier que l'utilisateur cible existe
        $friend = $this->userRepository->findById($friendId);
        if (!$friend) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'FRIEND_NOT_FOUND',
                    'message' => 'Cette personne n\'est pas dans votre liste d\'amis'
                ]
            ];
        }

        // Vérifier qu'on ne s'efface pas soi-même
        if ($userId === $friendId) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'INVALID_FRIEND_ID',
                    'message' => 'Vous ne pouvez pas vous supprimer de vos amis'
                ]
            ];
        }

        // Vérifier qu'ils sont amis
        if (!$this->friendRepository->areFriends($userId, $friendId)) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'FRIEND_NOT_FOUND',
                    'message' => 'Cette personne n\'est pas dans votre liste d\'amis'
                ]
            ];
        }

        // Supprimer l'amitié (bidirectionnelle)
        $removed = $this->friendRepository->removeFriend($userId, $friendId);

        if (!$removed) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'REMOVAL_FAILED',
                    'message' => 'Impossible de supprimer cet ami'
                ]
            ];
        }

        // Mettre à jour le statut de la demande d'ami pour permettre un nouveau cycle
        $this->updateFriendRequestStatus($userId, $friendId);

        return [
            'success' => true,
            'data' => [
                'removedFriendId' => $friendId,
                'removedAt' => now()->toISOString()
            ],
            'message' => 'Ami supprimé avec succès'
        ];
    }

    private function updateFriendRequestStatus(string $userId1, string $userId2): void
    {
        // Chercher la demande d'ami entre ces deux utilisateurs
        $request = \App\Models\FriendRequestModel::where(function ($query) use ($userId1, $userId2) {
            $query->where('sender_id', $userId1)
                  ->where('receiver_id', $userId2);
        })->orWhere(function ($query) use ($userId1, $userId2) {
            $query->where('sender_id', $userId2)
                  ->where('receiver_id', $userId1);
        })->first();

        if ($request) {
            // Mettre le statut à "cancelled" pour indiquer que l'amitié a été supprimée
            // Cela permettra d'envoyer une nouvelle demande d'ami
            $request->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
