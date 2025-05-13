<?php

namespace App\UseCases\User;

use App\Repositories\User\UserRepositoryInterface;

class DeleteUserDataUseCase
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function execute(string $userId): array
    {
        // Vérifier si l'utilisateur existe
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ];
        }

        // Supprimer toutes les données
        $success = $this->userRepository->deleteUserData($userId);

        if (!$success) {
            return [
                'success' => false,
                'message' => 'Une erreur est survenue lors de la suppression des données'
            ];
        }

        return [
            'success' => true,
            'message' => 'Toutes les données utilisateur ont été supprimées avec succès'
        ];
    }
}