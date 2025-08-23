<?php

namespace App\UseCases\User;

use App\Repositories\User\UserRepositoryInterface;
use App\Repositories\Friend\FriendRepositoryInterface;
use App\Models\SportSessionModel;
use App\Models\SportSessionParticipantModel;

class FindUserByIdUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private FriendRepositoryInterface $friendRepository
    ) {}

    public function execute(string $userId, string $currentUserId): ?array
    {
        // Récupérer l'utilisateur et son profil
        $user = $this->userRepository->findById($userId);
        $userProfile = $this->userRepository->getUserProfile($userId);
 
        if (!$user) {
            return null;
        }

        // Calculer les statistiques
        $stats = $this->calculateUserStats($userId);

        // Vérifier si l'utilisateur connecté est ami avec l'utilisateur demandé
        $isAlreadyFriend = $this->checkFriendshipStatus($currentUserId, $userId);

        return [
            'id' => $user->getId(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'email' => $user->getEmail(),
            'avatar' => $userProfile ? $userProfile->getAvatar() : null,
            'stats' => $stats,
            'isAlreadyFriend' => $isAlreadyFriend
        ];
    }

    private function calculateUserStats(string $userId): array
    {
        // Sessions créées par l'utilisateur
        $sessionsCreated = SportSessionModel::where('organizer_id', $userId)->count();

        // Sessions auxquelles l'utilisateur a participé
        $sessionsParticipated = SportSessionParticipantModel::where('user_id', $userId)
            ->where('status', 'accepted')
            ->count();

        return [
            'sessionsCreated' => $sessionsCreated,
            'sessionsParticipated' => $sessionsParticipated
        ];
    }

    private function checkFriendshipStatus(string $currentUserId, string $targetUserId): bool
    {
        // Si l'utilisateur consulte son propre profil, retourner false
        if ($currentUserId === $targetUserId) {
            return false;
        }

        // Vérifier si les deux utilisateurs sont amis
        return $this->friendRepository->areFriends($currentUserId, $targetUserId);
    }
}
