<?php

namespace App\UseCases\User;

use App\Repositories\User\UserRepositoryInterface;
use App\Repositories\Friend\FriendRepositoryInterface;
use App\Repositories\FriendRequest\FriendRequestRepositoryInterface;
use App\Models\SportSessionModel;
use App\Models\SportSessionParticipantModel;

class FindUserByIdUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private FriendRepositoryInterface $friendRepository,
        private FriendRequestRepositoryInterface $friendRequestRepository
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

        // Déterminer le statut de la relation
        $relationshipInfo = $this->getRelationshipStatus($currentUserId, $userId);

        return [
            'id' => $user->getId(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'email' => $user->getEmail(),
            'avatar' => $userProfile ? $userProfile->getAvatar() : null,
            'stats' => $stats,
            'isAlreadyFriend' => $isAlreadyFriend,
            'hasPendingRequest' => $relationshipInfo['hasPendingRequest'],
            'relationshipStatus' => $relationshipInfo['relationshipStatus']
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

    private function checkFriendshipStatus(string $currentUserId, string $targetUserId): bool
    {
        // Si l'utilisateur consulte son propre profil, retourner false
        if ($currentUserId === $targetUserId) {
            return false;
        }

        // Vérifier si les deux utilisateurs sont amis
        return $this->friendRepository->areFriends($currentUserId, $targetUserId);
    }

    private function getRelationshipStatus(string $currentUserId, string $targetUserId): array
    {
        // Si l'utilisateur consulte son propre profil, retourner des valeurs par défaut
        if ($currentUserId === $targetUserId) {
            return [
                'hasPendingRequest' => false,
                'relationshipStatus' => 'none'
            ];
        }

        // Vérifier si les utilisateurs sont déjà amis
        $isAlreadyFriend = $this->friendRepository->areFriends($currentUserId, $targetUserId);
        
        if ($isAlreadyFriend) {
            return [
                'hasPendingRequest' => false,
                'relationshipStatus' => 'accepted'
            ];
        }

        // Vérifier si l'utilisateur connecté a envoyé une demande en attente
        $pendingRequestSent = $this->friendRequestRepository->getPendingRequest($currentUserId, $targetUserId);
        
        if ($pendingRequestSent) {
            return [
                'hasPendingRequest' => true,
                'relationshipStatus' => 'pending'
            ];
        }

        // Vérifier si l'utilisateur cible a envoyé une demande en attente
        $pendingRequestReceived = $this->friendRequestRepository->getPendingRequest($targetUserId, $currentUserId);
        
        if ($pendingRequestReceived) {
            return [
                'hasPendingRequest' => false,
                'relationshipStatus' => 'received'
            ];
        }

        // Vérifier le statut de relation complet (inclut declined, cancelled, etc.)
        $relationshipStatus = $this->friendRequestRepository->getRelationshipStatus($currentUserId, $targetUserId);
        
        // Normaliser le statut pour l'API
        $normalizedStatus = $this->normalizeRelationshipStatus($relationshipStatus);
        
        return [
            'hasPendingRequest' => $normalizedStatus === 'pending',
            'relationshipStatus' => $normalizedStatus
        ];
    }

    private function normalizeRelationshipStatus(string $relationshipStatus): string
    {
        return match($relationshipStatus) {
            'pending_sent' => 'pending',
            'pending_received' => 'received',
            'accepted' => 'accepted',
            'declined' => 'declined',
            'cancelled' => 'cancelled',
            default => 'none'
        };
    }
}
