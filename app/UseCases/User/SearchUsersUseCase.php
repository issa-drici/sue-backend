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

        // Optimisation : récupérer toutes les données de relations en une seule fois
        $userIds = $paginator->getCollection()->pluck('id')->toArray();

        if (empty($userIds)) {
            return $paginator;
        }

        // Récupérer toutes les relations d'amitié en une requête
        $friendships = $this->getFriendshipsData($currentUserId, $userIds);

        // Récupérer tous les statuts de demandes d'amis en une requête
        $friendRequests = $this->getFriendRequestsData($currentUserId, $userIds);

        // Récupérer tous les comptes d'amis en commun en une requête
        $mutualFriendsCounts = $this->getMutualFriendsCounts($currentUserId, $userIds);

        // Enrichir les résultats avec les informations d'amitié
        $paginator->getCollection()->transform(function ($user) use ($currentUserId, $friendships, $friendRequests, $mutualFriendsCounts) {
            $userData = $user->toArray();
            $userId = $user->id;

            // Récupérer les données pré-calculées
            $isFriend = $friendships[$userId] ?? false;
            $relationshipStatus = $friendRequests[$userId] ?? 'none';
            $mutualFriends = $mutualFriendsCounts[$userId] ?? 0;

            // Déterminer s'il y a une demande en attente (seulement pour pending, pas cancelled ou declined)
            $hasPendingRequest = in_array($relationshipStatus, ['pending_sent', 'pending_received']);

            // Normaliser le statut pour l'API
            $normalizedStatus = $this->normalizeRelationshipStatus($relationshipStatus, $isFriend);

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
     * Récupère toutes les relations d'amitié en une seule requête
     */
    private function getFriendshipsData(string $currentUserId, array $userIds): array
    {
        $friendships = \App\Models\FriendModel::where('user_id', $currentUserId)
            ->whereIn('friend_id', $userIds)
            ->pluck('friend_id')
            ->toArray();

        $result = [];
        foreach ($userIds as $userId) {
            $result[$userId] = in_array($userId, $friendships);
        }

        return $result;
    }

    /**
     * Récupère tous les statuts de demandes d'amis en une seule requête
     */
    private function getFriendRequestsData(string $currentUserId, array $userIds): array
    {
        $requests = \App\Models\FriendRequestModel::where(function ($query) use ($currentUserId, $userIds) {
            $query->where('sender_id', $currentUserId)
                  ->whereIn('receiver_id', $userIds);
        })->orWhere(function ($query) use ($currentUserId, $userIds) {
            $query->where('receiver_id', $currentUserId)
                  ->whereIn('sender_id', $userIds);
        })->get();

        $result = [];
        foreach ($userIds as $userId) {
            $result[$userId] = 'none';
        }

        foreach ($requests as $request) {
            $otherUserId = $request->sender_id === $currentUserId ? $request->receiver_id : $request->sender_id;

            if (in_array($otherUserId, $userIds)) {
                switch ($request->status) {
                    case 'pending':
                        if ($request->cancelled_at) {
                            $result[$otherUserId] = 'cancelled';
                        } else {
                            $result[$otherUserId] = $request->sender_id === $currentUserId ? 'pending_sent' : 'pending_received';
                        }
                        break;
                    case 'accepted':
                        $result[$otherUserId] = 'accepted';
                        break;
                    case 'declined':
                        $result[$otherUserId] = 'declined';
                        break;
                    case 'cancelled':
                        $result[$otherUserId] = 'cancelled';
                        break;
                }
            }
        }

        return $result;
    }

    /**
     * Récupère tous les comptes d'amis en commun en une seule requête
     */
    private function getMutualFriendsCounts(string $currentUserId, array $userIds): array
    {
        // Récupérer les amis de l'utilisateur connecté
        $currentUserFriends = \App\Models\FriendModel::where('user_id', $currentUserId)
            ->pluck('friend_id')
            ->toArray();

        if (empty($currentUserFriends)) {
            return array_fill_keys($userIds, 0);
        }

        // Récupérer les amis de tous les autres utilisateurs en une requête
        $allFriends = \App\Models\FriendModel::whereIn('user_id', $userIds)
            ->whereIn('friend_id', $currentUserFriends)
            ->selectRaw('user_id, COUNT(*) as mutual_count')
            ->groupBy('user_id')
            ->pluck('mutual_count', 'user_id')
            ->toArray();

        $result = [];
        foreach ($userIds as $userId) {
            $result[$userId] = $allFriends[$userId] ?? 0;
        }

        return $result;
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
                // Les demandes refusées sont traitées comme "none" pour permettre de rechercher à nouveau l'utilisateur
                return 'none';
            case 'cancelled':
                return 'cancelled';
            case 'none':
            default:
                return 'none';
        }
    }
}
