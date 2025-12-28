<?php

namespace App\UseCases\User;

use App\Repositories\User\UserRepositoryInterface;
use App\Repositories\Friend\FriendRepositoryInterface;
use App\Repositories\FriendRequest\FriendRequestRepositoryInterface;
use App\Models\UserProfileModel;
use App\Models\FileModel;

class CheckContactsUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private FriendRepositoryInterface $friendRepository,
        private FriendRequestRepositoryInterface $friendRequestRepository
    ) {}

    public function execute(array $phoneNumbers, string $currentUserId): array
    {
        if (empty($phoneNumbers)) {
            return [];
        }

        // Normaliser les numéros de téléphone
        $normalizedPhones = array_map([$this, 'normalizePhoneNumber'], $phoneNumbers);
        
        // Créer un mapping du numéro original vers le numéro normalisé
        $originalToNormalized = [];
        foreach ($phoneNumbers as $index => $originalPhone) {
            $normalized = $normalizedPhones[$index];
            $originalToNormalized[$normalized] = $originalPhone;
        }

        // Trouver les utilisateurs correspondants
        $foundUsers = $this->userRepository->findByPhoneNumbers($normalizedPhones);

        // Récupérer les IDs des utilisateurs trouvés
        $foundUserIds = array_map(fn($user) => $user->getId(), array_values($foundUsers));

        // Récupérer les relations d'amitié et demandes en une seule fois
        $friendships = $this->getFriendshipsData($currentUserId, $foundUserIds);
        $friendRequests = $this->getFriendRequestsData($currentUserId, $foundUserIds);

        // Récupérer les avatars
        $avatars = $this->getAvatars($foundUserIds);

        // Construire la réponse
        $result = [];
        foreach ($normalizedPhones as $normalizedPhone) {
            $originalPhone = $originalToNormalized[$normalizedPhone];
            
            if (isset($foundUsers[$normalizedPhone])) {
                $user = $foundUsers[$normalizedPhone];
                $userId = $user->getId();
                
                $isFriend = $friendships[$userId] ?? false;
                $relationshipStatus = $friendRequests[$userId] ?? 'none';
                $hasPendingRequest = in_array($relationshipStatus, ['pending_sent', 'pending_received']);

                $result[] = [
                    'phoneNumber' => $originalPhone,
                    'isRegistered' => true,
                    'user' => [
                        'id' => $user->getId(),
                        'firstname' => $user->getFirstname(),
                        'lastname' => $user->getLastname(),
                        'avatar' => $avatars[$userId] ?? null,
                        'relationship' => [
                            'isFriend' => $isFriend,
                            'hasPendingRequest' => $hasPendingRequest,
                        ],
                    ],
                ];
            } else {
                $result[] = [
                    'phoneNumber' => $originalPhone,
                    'isRegistered' => false,
                ];
            }
        }

        return $result;
    }

    /**
     * Normalise un numéro de téléphone pour la comparaison
     */
    private function normalizePhoneNumber(string $phone): string
    {
        // Supprimer tous les caractères non numériques sauf le +
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // Si le numéro commence par 0 et n'a pas de préfixe international, le remplacer par +33
        if (preg_match('/^0(\d{9})$/', $phone, $matches)) {
            $phone = '+33' . $matches[1];
        }

        // S'assurer que le numéro commence par +
        if (!str_starts_with($phone, '+')) {
            // Si c'est un numéro français sans préfixe, ajouter +33
            if (preg_match('/^33(\d{9})$/', $phone, $matches)) {
                $phone = '+33' . $matches[1];
            } elseif (preg_match('/^(\d{9})$/', $phone)) {
                $phone = '+33' . $phone;
            }
        }

        return $phone;
    }

    /**
     * Récupère toutes les relations d'amitié en une seule requête
     */
    private function getFriendshipsData(string $currentUserId, array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

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
        if (empty($userIds)) {
            return [];
        }

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
     * Récupère les URLs des avatars pour les utilisateurs
     */
    private function getAvatars(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $profiles = UserProfileModel::whereIn('user_id', $userIds)
            ->whereNotNull('avatar_file_id')
            ->with('avatarFile')
            ->get();

        $result = [];
        foreach ($profiles as $profile) {
            if ($profile->avatarFile) {
                $result[$profile->user_id] = $profile->avatarFile->url;
            }
        }

        return $result;
    }
}

