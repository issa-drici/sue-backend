<?php

namespace App\UseCases\User;

use App\Repositories\User\UserRepositoryInterface;
use App\Repositories\Friend\FriendRepositoryInterface;
use App\Repositories\FriendRequest\FriendRequestRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchUsersUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private FriendRepositoryInterface $friendRepository,
        private FriendRequestRepositoryInterface $friendRequestRepository
    ) {}

    public function execute(string $query, string $currentUserId, int $page = 1, int $limit = 20): LengthAwarePaginator
    {
        $paginator = $this->userRepository->searchUsers($query, $currentUserId, $page, $limit);

        // Enrichir les résultats avec les informations d'amitié
        $paginator->getCollection()->transform(function ($user) use ($currentUserId) {
            $userData = $user->toArray();

            // Vérifier si c'est un ami
            $isFriend = $this->friendRepository->areFriends($currentUserId, $user->id);

            // Obtenir le statut de relation complet
            $relationshipStatus = $this->friendRequestRepository->getRelationshipStatus($currentUserId, $user->id);

            // Déterminer s'il y a une demande en attente (seulement pour pending, pas cancelled)
            $hasPendingRequest = in_array($relationshipStatus, ['pending_sent', 'pending_received']);

            // Normaliser le statut pour l'API
            $normalizedStatus = $this->normalizeRelationshipStatus($relationshipStatus, $isFriend);

            // Compter les amis en commun
            $mutualFriends = $this->friendRepository->getMutualFriendsCount($currentUserId, $user->id);

            // Ajouter les informations de relation
            $userData['relationship'] = [
                'status' => $normalizedStatus,
                'isFriend' => $isFriend,
                'hasPendingRequest' => $hasPendingRequest,
                'mutualFriends' => $mutualFriends
            ];

            return $userData;
        });

        return $paginator;
    }

    /**
     * Normalise le statut de relation pour l'API
     */
    private function normalizeRelationshipStatus(string $relationshipStatus, bool $isFriend): string
    {
        // Si ce sont des amis, priorité sur tout autre statut
        if ($isFriend) {
            return 'accepted';
        }

        // Normaliser les statuts internes vers les statuts de l'API
        switch ($relationshipStatus) {
            case 'pending_sent':
            case 'pending_received':
                return 'pending';
            case 'accepted':
                return 'accepted';
            case 'declined':
                return 'declined';
            case 'cancelled':
                return 'cancelled';
            case 'none':
            default:
                return 'none';
        }
    }
}
