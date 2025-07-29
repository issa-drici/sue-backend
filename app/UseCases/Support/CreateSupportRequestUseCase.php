<?php

namespace App\UseCases\Support;

use App\Repositories\Support\SupportRequestRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Create Support Request Use Case
 */
class CreateSupportRequestUseCase
{
    public function __construct(
        private SupportRequestRepositoryInterface $supportRequestRepository
    ) {}

    public function execute(array $data): array
    {
        // Vérification de l'authentification
        $user = Auth::user();
        if (!$user) {
            throw new \Exception('Utilisateur non authentifié');
        }

        // Création de la demande de support
        $supportRequest = $this->supportRequestRepository->create([
            'id' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'message' => $data['message']
        ]);

        return [
            'id' => $supportRequest->getId(),
            'message' => $supportRequest->getMessage(),
            'created_at' => now()->format('Y-m-d H:i:s')
        ];
    }
}
