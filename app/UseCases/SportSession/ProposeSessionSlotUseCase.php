<?php

namespace App\UseCases\SportSession;

use App\Entities\SportSession;
use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Repositories\User\UserRepositoryInterface;
use App\Services\DateFormatterService;
use App\Services\SessionNotificationService;
use App\Services\SlotProposalValidator;
use Exception;

/**
 * "Trouver un créneau" : un invité propose un créneau en plus de ceux de l'organisateur,
 * quand aucun ne lui va. Les autres peuvent voter dessus ; l'organisateur garde le dernier mot
 * (il peut le retirer et c'est lui qui confirme).
 */
class ProposeSessionSlotUseCase
{
    public const MAX_PROPOSALS_PER_USER = 2;
    private const MAX_SLOTS = 20;

    public function __construct(
        private SportSessionRepositoryInterface $sportSessionRepository,
        private UserRepositoryInterface $userRepository,
        private SessionNotificationService $sessionNotificationService
    ) {}

    public function execute(string $sessionId, string $userId, array $slot): SportSession
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
            throw new Exception("L'organisateur modifie directement la liste des créneaux");
        }

        $participant = $this->sportSessionRepository->findParticipant($sessionId, $userId);
        if (!$participant) {
            throw new Exception('Vous n\'êtes pas invité à cette session');
        }

        SlotProposalValidator::validate($slot);

        $slots = $session->getSlotProposals();
        $key = SlotProposalValidator::startKey($slot);
        foreach ($slots as $existing) {
            if (SlotProposalValidator::startKey($existing) === $key) {
                throw new Exception('Un créneau commence déjà à cette heure : coche-le plutôt');
            }
        }

        $mine = array_filter($slots, fn ($existing) => ($existing['proposedBy'] ?? null) === $userId);
        if (count($mine) >= self::MAX_PROPOSALS_PER_USER) {
            throw new Exception('Tu as déjà proposé ' . self::MAX_PROPOSALS_PER_USER . ' créneaux');
        }

        if (count($slots) >= self::MAX_SLOTS) {
            throw new Exception('Il y a déjà assez de créneaux proposés');
        }

        $newSlot = [
            'date' => $slot['date'],
            'startTime' => $slot['startTime'],
            'endTime' => $slot['endTime'],
            'proposedBy' => $userId,
        ];
        $this->sportSessionRepository->updateSlotProposals($sessionId, [], [$newSlot]);

        // Proposer un créneau après avoir décliné revient à reconsidérer l'invitation
        if ($participant['status'] === 'declined') {
            $this->sportSessionRepository->updateParticipantStatus($sessionId, $userId, 'pending');
        }

        $this->notify($session, $userId, $newSlot);

        return $this->sportSessionRepository->findById($sessionId);
    }

    private function notify(SportSession $session, string $userId, array $slot): void
    {
        $user = $this->userRepository->findById($userId);
        $firstname = $user ? $user->getFirstname() : 'Un invité';
        $label = DateFormatterService::formatSlotLabel($slot);
        $sportName = DateFormatterService::getSportName($session->getSport());

        $this->sessionNotificationService->notify(
            $session->getOrganizer()->getId(),
            $session->getId(),
            "{$firstname} propose un autre créneau",
            ucfirst($label) . " : garde-le ou retire-le, c'est toi qui confirmes.",
            'slot_added'
        );

        foreach ($session->getSlotInvitees() as $invitee) {
            if ($invitee['id'] === $userId) {
                continue;
            }
            $this->sessionNotificationService->notify(
                $invitee['id'],
                $session->getId(),
                'Nouveau créneau proposé : ' . $label,
                "Proposé par {$firstname} pour la session de {$sportName} : tu es dispo ?",
                'slot_added'
            );
        }
    }
}
