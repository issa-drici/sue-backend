<?php

namespace App\UseCases\User;

use App\Repositories\User\UserRepositoryInterface;

class UpdateUserEmailUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function execute(string $userId, string $newEmail, string $currentEmail): array
    {
        // Vérifier que l'email actuel correspond
        $user = $this->userRepository->findById($userId);
        if (!$user || $user->getEmail() !== $currentEmail) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Email actuel incorrect'
                ]
            ];
        }

        // Valider le format de l'email
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Format d\'email invalide'
                ]
            ];
        }

        // Vérifier que le nouvel email n'existe pas déjà
        if ($this->userRepository->emailExists($newEmail, $userId)) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'EMAIL_ALREADY_EXISTS',
                    'message' => 'Cet email est déjà utilisé'
                ]
            ];
        }

        // Mettre à jour l'email
        $updated = $this->userRepository->updateEmail($userId, $newEmail);

        if (!$updated) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Impossible de mettre à jour l\'email'
                ]
            ];
        }

        return [
            'success' => true,
            'message' => 'Email mis à jour avec succès'
        ];
    }
}
