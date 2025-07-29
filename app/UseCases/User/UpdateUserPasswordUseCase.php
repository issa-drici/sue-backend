<?php

namespace App\UseCases\User;

use App\Repositories\User\UserRepositoryInterface;

class UpdateUserPasswordUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function execute(string $userId, string $currentPassword, string $newPassword): array
    {
        // Vérifier le mot de passe actuel
        if (!$this->userRepository->verifyPassword($userId, $currentPassword)) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'INVALID_CURRENT_PASSWORD',
                    'message' => 'Mot de passe actuel incorrect'
                ]
            ];
        }

        // Valider le nouveau mot de passe
        if (strlen($newPassword) < 6) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Le nouveau mot de passe doit contenir au moins 6 caractères'
                ]
            ];
        }

        // Mettre à jour le mot de passe
        $updated = $this->userRepository->updatePassword($userId, $newPassword);

        if (!$updated) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Impossible de mettre à jour le mot de passe'
                ]
            ];
        }

        return [
            'success' => true,
            'message' => 'Mot de passe mis à jour avec succès'
        ];
    }
}
