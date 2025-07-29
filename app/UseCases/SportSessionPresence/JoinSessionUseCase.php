<?php

namespace App\UseCases\SportSessionPresence;

use App\Entities\SportSessionPresence;
use App\Events\UserOnline;
use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Repositories\SportSessionPresence\SportSessionPresenceRepositoryInterface;
use Illuminate\Support\Facades\Validator;

class JoinSessionUseCase
{
    public function __construct(
        private SportSessionPresenceRepositoryInterface $presenceRepository,
        private SportSessionRepositoryInterface $sessionRepository
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

        // Vérifier que l'utilisateur est participant de la session
        // $isParticipant = $this->sessionRepository->isUserParticipant($sessionId, $userId);
        // if (!$isParticipant) {
        //     return [
        //         'success' => false,
        //         'error' => [
        //             'code' => 'NOT_PARTICIPANT',
        //             'message' => 'Vous devez être participant de cette session',
        //         ],
        //     ];
        // }

        // Rejoindre la session
        $presence = $this->presenceRepository->joinSession($sessionId, $userId);

        // Diffuser l'événement
        event(new UserOnline($presence));

        return [
            'success' => true,
            'data' => [
                'sessionId' => $sessionId,
                'userId' => $userId,
                'joinedAt' => $presence->createdAt->format('c'),
            ],
            'message' => 'Utilisateur connecté à la session',
        ];
    }
}
