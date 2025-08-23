<?php

namespace App\UseCases\Profile;

use App\Repositories\User\UserRepositoryInterface;
use App\Models\SportSessionModel;
use App\Models\SportSessionParticipantModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class FindUserProfileUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function execute(): array
    {
        $user = Auth::user();
        if (!$user) {
            throw ValidationException::withMessages([
                'auth' => ['Utilisateur non authentifié']
            ]);
        }

        // Récupérer le profil utilisateur
        $profile = $this->userRepository->getUserProfile($user->id);

        if (!$profile) {
            throw ValidationException::withMessages([
                'profile' => ['Profil utilisateur non trouvé']
            ]);
        }

        // Calculer les statistiques
        $stats = $this->calculateUserStats($user->id);

        return [
            'user' => [
                'id' => $profile->getId(),
                'firstname' => $profile->getFirstname(),
                'lastname' => $profile->getLastname(),
                'email' => $profile->getEmail(),
                'avatar_url' => $profile->getAvatar()
            ],
            'stats' => $stats
        ];
    }

    private function calculateUserStats(string $userId): array
    {
        // Sessions créées par l'utilisateur (excluant les sessions annulées)
        $sessionsCreated = SportSessionModel::where('organizer_id', $userId)
            ->where('status', '!=', 'cancelled')
            ->count();

        // Sessions auxquelles l'utilisateur a participé
        $sessionsParticipated = SportSessionParticipantModel::where('user_id', $userId)
            ->where('status', 'accepted')
            ->count();

        return [
            'sessionsCreated' => $sessionsCreated,
            'sessionsParticipated' => $sessionsParticipated
        ];
    }
}
