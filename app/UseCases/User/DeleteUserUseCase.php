<?php

namespace App\UseCases\User;

use App\Repositories\User\UserRepositoryInterface;
use App\Repositories\Friend\FriendRepositoryInterface;
use App\Repositories\FriendRequest\FriendRequestRepositoryInterface;

class DeleteUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private FriendRepositoryInterface $friendRepository,
        private FriendRequestRepositoryInterface $friendRequestRepository
    ) {}

    public function execute(string $userId): array
    {
        // Vérifier que l'utilisateur existe
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'USER_NOT_FOUND',
                    'message' => 'Utilisateur non trouvé'
                ]
            ];
        }

        // Supprimer l'utilisateur (les relations seront supprimées automatiquement via les foreign keys)
        $deleted = $this->userRepository->delete($userId);

        if (!$deleted) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Impossible de supprimer le compte'
                ]
            ];
        }

        return [
            'success' => true,
            'message' => 'Compte supprimé avec succès'
        ];
    }
}
