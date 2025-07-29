<?php

namespace App\UseCases\User;

use App\Repositories\FriendRequest\FriendRequestRepositoryInterface;

class CancelFriendRequestUseCase
{
    public function __construct(
        private FriendRequestRepositoryInterface $friendRequestRepository
    ) {}

    public function execute(string $senderId, string $receiverId): array
    {
        // Valider que les IDs sont des UUIDs valides
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $senderId)) {
            throw new \InvalidArgumentException('ID de l\'expéditeur invalide');
        }

        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $receiverId)) {
            throw new \InvalidArgumentException('ID de l\'utilisateur cible invalide');
        }

        // Rechercher la demande d'ami basée sur les IDs des utilisateurs
        $friendRequest = $this->friendRequestRepository->findRequestByUsers($senderId, $receiverId);

        if (!$friendRequest) {
            throw new \InvalidArgumentException('Demande d\'ami introuvable');
        }

        // Vérifier que l'utilisateur connecté est le propriétaire de la demande
        if ($friendRequest->getSenderId() !== $senderId) {
            throw new \Exception('UNAUTHORIZED: Vous ne pouvez annuler que vos propres demandes d\'ami');
        }

        // Vérifier que la demande est en statut pending
        if (!$friendRequest->isPending()) {
            throw new \Exception('ALREADY_PROCESSED: Cette demande d\'ami a déjà été acceptée ou refusée');
        }

        // Annuler la demande
        $result = $this->friendRequestRepository->cancelRequest($friendRequest->getId());

        if (!$result) {
            throw new \Exception('Erreur lors de l\'annulation de la demande');
        }

        return [
            'requestId' => $friendRequest->getId(),
            'senderId' => $friendRequest->getSenderId(),
            'receiverId' => $friendRequest->getReceiverId(),
            'cancelledAt' => now()->toISOString()
        ];
    }
}
