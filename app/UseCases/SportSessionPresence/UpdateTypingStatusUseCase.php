<?php

namespace App\UseCases\SportSessionPresence;

use App\Events\UserTyping;
use App\Repositories\SportSessionPresence\SportSessionPresenceRepositoryInterface;
use Illuminate\Support\Facades\Validator;

class UpdateTypingStatusUseCase
{
    public function __construct(
        private SportSessionPresenceRepositoryInterface $presenceRepository
    ) {}

    public function execute(string $sessionId, string $userId, bool $isTyping): array
    {
        // Validation des données
        $validator = Validator::make([
            'sessionId' => $sessionId,
            'userId' => $userId,
            'isTyping' => $isTyping,
        ], [
            'sessionId' => 'required|uuid|exists:sport_sessions,id',
            'userId' => 'required|uuid|exists:users,id',
            'isTyping' => 'required|boolean',
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

        // Mettre à jour le statut de frappe
        $presence = $this->presenceRepository->updateTypingStatus($sessionId, $userId, $isTyping);

        if (!$presence) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'UPDATE_FAILED',
                    'message' => 'Échec de la mise à jour du statut de frappe',
                ],
            ];
        }

        // Diffuser l'événement
        event(new UserTyping($presence));

        return [
            'success' => true,
            'data' => [
                'sessionId' => $sessionId,
                'userId' => $userId,
                'isTyping' => $isTyping,
                'timestamp' => $presence->typingStartedAt?->format('c') ?? now()->toISOString(),
            ],
        ];
    }
}
