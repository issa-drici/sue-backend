<?php

namespace App\UseCases\SportSessionPresence;

use App\Events\UserOffline;
use App\Repositories\SportSessionPresence\SportSessionPresenceRepositoryInterface;
use Illuminate\Support\Facades\Validator;

class LeaveSessionUseCase
{
    public function __construct(
        private SportSessionPresenceRepositoryInterface $presenceRepository
    ) {}

    public function execute(string $sessionId, string $userId): array
    {
        // Validation des données
        $validator = Validator::make([
            'sessionId' => $sessionId,
            'userId' => $userId,
        ], [
            'sessionId' => 'required|uuid|exists:sport_sessions,id',
            'userId' => 'required|uuid|exists:users,id',
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

        // Récupérer la présence actuelle pour l'événement
        $currentPresence = $this->presenceRepository->findPresenceBySessionAndUser($sessionId, $userId);

        // Quitter la session
        $left = $this->presenceRepository->leaveSession($sessionId, $userId);

        if (!$left) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'LEAVE_FAILED',
                    'message' => 'Échec de la déconnexion de la session',
                ],
            ];
        }

        // Diffuser l'événement si la présence existait
        if ($currentPresence) {
            event(new UserOffline($currentPresence));
        }

        return [
            'success' => true,
            'data' => [
                'sessionId' => $sessionId,
                'userId' => $userId,
                'leftAt' => now()->toISOString(),
            ],
            'message' => 'Utilisateur déconnecté de la session',
        ];
    }
}
