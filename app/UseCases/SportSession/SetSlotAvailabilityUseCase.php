<?php

namespace App\UseCases\SportSession;

use App\Entities\SportSession;
use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Repositories\User\UserRepositoryInterface;
use App\Services\SessionNotificationService;
use Exception;

/**
 * "Trouver un créneau" : un invité indique les créneaux où il peut jouer.
 * Une liste vide veut dire "aucun créneau ne me va" (compte comme une réponse).
 * Modifiable tant que l'organisateur n'a pas confirmé de créneau.
 */
class SetSlotAvailabilityUseCase
{
    public function __construct(
        private SportSessionRepositoryInterface $sportSessionRepository,
        private UserRepositoryInterface $userRepository,
        private SessionNotificationService $sessionNotificationService
    ) {}

    public function execute(string $sessionId, string $userId, array $slotIds): SportSession
    {
        $session = $this->sportSessionRepository->findById($sessionId);

        if (!$session) {
            throw new Exception('Session non trouvée');
        }

        if ($session->getStatus() === 'cancelled') {
            throw new Exception('Cette session a été annulée');
        }

        if ($session->isScheduled()) {
            throw new Exception('Le créneau de cette session est déjà confirmé');
        }

        if ($session->isOrganizer($userId)) {
            throw new Exception("L'organisateur n'a pas à donner ses disponibilités");
        }

        $participant = $this->sportSessionRepository->findParticipant($sessionId, $userId);
        if (!$participant) {
            throw new Exception('Vous n\'êtes pas invité à cette session');
        }

        $sessionSlotIds = array_column($session->getSlotProposals(), 'id');
        foreach ($slotIds as $slotId) {
            if (!in_array($slotId, $sessionSlotIds, true)) {
                throw new Exception('Créneau invalide');
            }
        }

        $isUpdate = $this->hasAlreadyResponded($session, $userId);

        $this->sportSessionRepository->setSlotAvailabilities($sessionId, $userId, $slotIds);

        // Donner ses dispos après avoir décliné revient à reconsidérer l'invitation
        if ($participant['status'] === 'declined') {
            $this->sportSessionRepository->updateParticipantStatus($sessionId, $userId, 'pending');
        }

        $updatedSession = $this->sportSessionRepository->findById($sessionId);

        if (!$isUpdate && $this->everyoneResponded($updatedSession)) {
            $this->notifyEveryoneResponded($updatedSession);
        } else {
            $this->notifyOrganizer($session, $userId, count(array_unique($slotIds)), $isUpdate);
        }

        return $updatedSession;
    }

    private function hasAlreadyResponded(SportSession $session, string $userId): bool
    {
        foreach ($session->getParticipants() as $participant) {
            if (($participant['id'] ?? null) === $userId) {
                return (bool) ($participant['hasGivenAvailability'] ?? false);
            }
        }
        return false;
    }

    private function everyoneResponded(SportSession $session): bool
    {
        $invitees = $session->getSlotInvitees();
        foreach ($invitees as $invitee) {
            if (empty($invitee['hasGivenAvailability'])) {
                return false;
            }
        }
        return count($invitees) > 0;
    }

    private function notifyEveryoneResponded(SportSession $session): void
    {
        $best = $session->getBestSlotProposal();
        $count = $best['availabilityCount'] ?? 0;
        $message = $count > 0
            ? ucfirst(\App\Services\DateFormatterService::formatSlotLabel($best)) . " arrange le plus de monde ({$count} dispo" . ($count > 1 ? 's' : '') . ') : tu peux confirmer'
            : 'Aucun créneau ne convient à tes invités : propose-en d\'autres';

        $this->sessionNotificationService->notify(
            $session->getOrganizer()->getId(),
            $session->getId(),
            'Tout le monde a répondu',
            $message,
            'slot_availability'
        );
    }

    private function notifyOrganizer(SportSession $session, string $userId, int $availableCount, bool $isUpdate): void
    {
        $user = $this->userRepository->findById($userId);
        $firstname = $user ? $user->getFirstname() : 'Un invité';
        $totalCount = count($session->getSlotProposals());

        $title = $isUpdate
            ? "{$firstname} a modifié ses disponibilités"
            : "{$firstname} a donné ses disponibilités";

        if ($availableCount === 0) {
            $message = 'Aucun des créneaux proposés ne lui convient';
        } elseif ($availableCount === 1) {
            $message = "1 créneau sur {$totalCount} lui convient";
        } else {
            $message = "{$availableCount} créneaux sur {$totalCount} lui conviennent";
        }

        $this->sessionNotificationService->notify(
            $session->getOrganizer()->getId(),
            $session->getId(),
            $title,
            $message,
            'slot_availability'
        );
    }
}
