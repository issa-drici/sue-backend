<?php

namespace App\UseCases\User;

use App\Repositories\User\UserRepositoryInterface;
use App\Entities\UserProfile;
use App\Models\SportSessionModel;
use App\Models\SportSessionParticipantModel;

class GetUserProfileUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function execute(string $userId): ?UserProfile
    {
        $userProfile = $this->userRepository->getUserProfile($userId);

        if (!$userProfile) {
            return null;
        }

        // Calculer les statistiques
        $stats = $this->calculateUserStats($userId);

        // Créer un nouveau UserProfile avec les stats calculées
        return new UserProfile(
            $userProfile->getId(),
            $userProfile->getFirstname(),
            $userProfile->getLastname(),
            $userProfile->getEmail(),
            $userProfile->getAvatar(),
            $stats,
            $userProfile->getSportsPreferences()
        );
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
