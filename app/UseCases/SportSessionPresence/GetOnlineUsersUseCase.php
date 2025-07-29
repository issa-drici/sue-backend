<?php

namespace App\UseCases\SportSessionPresence;

use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Repositories\SportSessionPresence\SportSessionPresenceRepositoryInterface;

class GetOnlineUsersUseCase
{
    public function __construct(
        private SportSessionPresenceRepositoryInterface $presenceRepository,
        private SportSessionRepositoryInterface $sessionRepository
    ) {}

    public function execute(string $sessionId, int $page = 1, int $limit = 50): array
    {
        // Vérifier que la session existe
        $session = $this->sessionRepository->findById($sessionId);
        if (!$session) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'SESSION_NOT_FOUND',
                    'message' => 'Session non trouvée',
                ],
            ];
        }

        // Récupérer les utilisateurs en ligne
        $presences = $this->presenceRepository->findOnlineUsersBySession($sessionId, $page, $limit);

        return [
            'success' => true,
            'data' => $presences->items(),
            'total' => $presences->total(),
        ];
    }
}
