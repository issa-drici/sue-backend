<?php

namespace App\UseCases\PushToken;

use App\Repositories\PushToken\PushTokenRepositoryInterface;
use App\Services\ExpoPushNotificationService;

class SavePushTokenUseCase
{
    public function __construct(
        private PushTokenRepositoryInterface $pushTokenRepository,
        private ExpoPushNotificationService $expoService
    ) {}

    public function execute(string $userId, string $token, string $platform = 'expo'): array
    {
        try {
            // Valider le token
            if (!$this->expoService->isValidToken($token)) {
                return [
                    'success' => false,
                    'error' => 'Token invalide'
                ];
            }

            // Sauvegarder le token
            $saved = $this->pushTokenRepository->saveToken($userId, $token, $platform);

            if (!$saved) {
                return [
                    'success' => false,
                    'error' => 'Erreur lors de la sauvegarde du token'
                ];
            }

            return [
                'success' => true,
                'message' => 'Token enregistré avec succès'
            ];

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error saving push token', [
                'user_id' => $userId,
                'token' => $token,
                'platform' => $platform,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Erreur interne lors de la sauvegarde du token'
            ];
        }
    }
}
