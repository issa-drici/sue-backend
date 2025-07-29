<?php

namespace App\UseCases\Support;

use App\Repositories\Support\SupportRequestRepositoryInterface;
use Illuminate\Support\Facades\Auth;

/**
 * Find All Support Requests Use Case
 */
class FindAllSupportRequestsUseCase
{
    public function __construct(
        private SupportRequestRepositoryInterface $supportRequestRepository
    ) {}

    public function execute(): array
    {
        // Vérification de l'authentification
        $user = Auth::user();
        if (!$user) {
            throw new \Exception('Utilisateur non authentifié');
        }

        // Récupération des demandes de support
        $supportRequests = $this->supportRequestRepository->findAllByUserId($user->id);

        return [
            'data' => array_map(fn($request) => [
                'id' => $request->getId(),
                'message' => $request->getMessage(),
                'created_at' => now()->format('Y-m-d H:i:s')
            ], $supportRequests),
            'meta' => [
                'current_page' => 1,
                'per_page' => 10,
                'total' => count($supportRequests)
            ]
        ];
    }
}
