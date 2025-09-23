<?php

namespace App\UseCases\PushToken;

use App\Repositories\PushToken\PushTokenRepositoryInterface;

class DeletePushTokenUseCase
{
    public function __construct(
        private PushTokenRepositoryInterface $pushTokenRepository
    ) {}

    public function execute(string $userId, string $token): array
    {
        try {
            // Vérifier que le token appartient à l'utilisateur connecté
            $existingToken = $this->pushTokenRepository->findByToken($token);

            if (!$existingToken || $existingToken->getUserId() !== $userId) {
                return [
                    'success' => false,
                    'error' => 'TOKEN_NOT_FOUND_OR_ALREADY_DELETED'
                ];
            }

            $deleted = $this->pushTokenRepository->deleteToken($token);

            if (!$deleted) {
                return [
                    'success' => false,
                    'error' => 'TOKEN_NOT_FOUND_OR_ALREADY_DELETED'
                ];
            }

            return [
                'success' => true,
                'message' => 'Token supprimé avec succès'
            ];

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error deleting push token', [
                'user_id' => $userId,
                'token' => $token,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'INTERNAL_ERROR'
            ];
        }
    }
}


