<?php

namespace App\UseCases\SportSession;

use App\Entities\SportSession;
use App\Events\CommentCreated;
use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Repositories\SportSessionComment\SportSessionCommentRepositoryInterface;
use App\Services\DateFormatterService;
use App\Services\SessionNotificationService;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * "Trouver un créneau" : l'organisateur confirme un créneau.
 * La session redevient une session classique ; les joueurs disponibles sur ce créneau
 * sont inscrits automatiquement (dans la limite de places, par ordre de réponse),
 * les autres restent invités et peuvent répondre comme d'habitude.
 */
class ConfirmSessionSlotUseCase
{
    public function __construct(
        private SportSessionRepositoryInterface $sportSessionRepository,
        private SportSessionCommentRepositoryInterface $commentRepository,
        private SessionNotificationService $sessionNotificationService
    ) {}

    public function execute(string $sessionId, string $userId, string $slotId): SportSession
    {
        $session = $this->sportSessionRepository->findById($sessionId);

        if (!$session) {
            throw new Exception('Session non trouvée');
        }

        if (!$session->isOrganizer($userId)) {
            throw new Exception('Seul l\'organisateur peut confirmer un créneau');
        }

        if ($session->getStatus() === 'cancelled') {
            throw new Exception('Cette session a été annulée');
        }

        if ($session->isScheduled()) {
            throw new Exception('Le créneau de cette session est déjà confirmé');
        }

        $slot = null;
        foreach ($session->getSlotProposals() as $proposal) {
            if ($proposal['id'] === $slotId) {
                $slot = $proposal;
            }
        }
        if (!$slot) {
            throw new Exception('Créneau invalide');
        }

        $newlyAccepted = $this->acceptAvailablePlayers($session, $slot);

        $this->sportSessionRepository->confirmSlot($sessionId, $slotId);

        $slotLabel = DateFormatterService::formatDate($slot['date']) . ' à ' . DateFormatterService::formatTime($slot['startTime']);
        $this->notifyPlayers($session, $newlyAccepted, $slotLabel);
        $this->createSystemComment($session, $userId, $slotLabel);

        return $this->sportSessionRepository->findById($sessionId);
    }

    /**
     * Inscrit les joueurs disponibles sur le créneau retenu. Retourne leurs ids.
     */
    private function acceptAvailablePlayers(SportSession $session, array $slot): array
    {
        $organizerId = $session->getOrganizer()->getId();
        $availableIds = $slot['availableUserIds'] ?? [];
        $maxParticipants = $session->getMaxParticipants();
        $acceptedCount = $session->getAcceptedParticipantsCount();

        $accepted = [];
        // getParticipants() est trié par date d'ajout : premier invité, premier servi
        foreach ($session->getParticipants() as $participant) {
            $participantId = $participant['id'] ?? null;
            if ($participantId === $organizerId
                || !in_array($participantId, $availableIds, true)
                || ($participant['status'] ?? null) !== 'pending'
            ) {
                continue;
            }
            if ($maxParticipants !== null && $acceptedCount >= $maxParticipants) {
                break;
            }
            $this->sportSessionRepository->updateParticipantStatus($session->getId(), $participantId, 'accepted');
            $accepted[] = $participantId;
            $acceptedCount++;
        }

        return $accepted;
    }

    private function notifyPlayers(SportSession $session, array $newlyAccepted, string $slotLabel): void
    {
        $organizerId = $session->getOrganizer()->getId();
        $sportName = DateFormatterService::getSportName($session->getSport());
        $title = "Créneau confirmé : {$slotLabel}";

        foreach ($session->getParticipants() as $participant) {
            $participantId = $participant['id'] ?? null;
            if (!$participantId || $participantId === $organizerId || ($participant['status'] ?? null) === 'declined') {
                continue;
            }

            $message = in_array($participantId, $newlyAccepted, true)
                ? "Session de {$sportName} : tu es inscrit, à {$session->getLocation()}"
                : "Session de {$sportName} à {$session->getLocation()}. Tu viens ?";

            $this->sessionNotificationService->notify($participantId, $session->getId(), $title, $message, 'slot_confirmed');
        }
    }

    private function createSystemComment(SportSession $session, string $userId, string $slotLabel): void
    {
        try {
            $comment = $this->commentRepository->createComment(
                sessionId: $session->getId(),
                userId: $userId,
                content: "a confirmé le créneau : {$slotLabel} 📅"
            );
            broadcast(new CommentCreated($comment, $session->getId()));
        } catch (\Exception $e) {
            Log::error('Failed to create system comment for slot confirmation', [
                'sessionId' => $session->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
