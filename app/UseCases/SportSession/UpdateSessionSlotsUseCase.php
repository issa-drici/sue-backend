<?php

namespace App\UseCases\SportSession;

use App\Entities\SportSession;
use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Services\DateFormatterService;
use App\Services\SessionNotificationService;
use Exception;

/**
 * "Trouver un créneau" : l'organisateur modifie la liste des créneaux proposés.
 * Reçoit la liste complète voulue : un créneau existant garde son id, un nouveau n'en a pas.
 * Un créneau existant dont l'horaire change est traité comme retiré puis ajouté.
 * Ajout : push à tous les invités. Retrait : push à ceux qui avaient coché ce créneau
 * (leurs autres dispos sont conservées).
 */
class UpdateSessionSlotsUseCase
{
    private const MAX_SLOTS = 20;

    public function __construct(
        private SportSessionRepositoryInterface $sportSessionRepository,
        private SessionNotificationService $sessionNotificationService
    ) {}

    public function execute(string $sessionId, string $userId, array $slots): SportSession
    {
        $session = $this->sportSessionRepository->findById($sessionId);

        if (!$session) {
            throw new Exception('Session non trouvée');
        }

        if (!$session->isOrganizer($userId)) {
            throw new Exception('Seul l\'organisateur peut modifier les créneaux');
        }

        if ($session->getStatus() === 'cancelled') {
            throw new Exception('Cette session a été annulée');
        }

        if ($session->isScheduled()) {
            throw new Exception('Le créneau de cette session est déjà confirmé');
        }

        if (count($slots) === 0) {
            throw new Exception('Garde au moins un créneau');
        }

        if (count($slots) > self::MAX_SLOTS) {
            throw new Exception('Pas plus de ' . self::MAX_SLOTS . ' créneaux');
        }

        $current = [];
        foreach ($session->getSlotProposals() as $slot) {
            $current[$slot['id']] = $slot;
        }

        $keptIds = [];
        $newSlots = [];
        $seen = [];
        foreach ($slots as $slot) {
            $key = "{$slot['date']} {$slot['startTime']} {$slot['endTime']}";
            if (isset($seen[$key])) {
                throw new Exception('Le même créneau est proposé deux fois');
            }
            $seen[$key] = true;

            $existing = isset($slot['id']) ? ($current[$slot['id']] ?? null) : null;
            if ($existing
                && $existing['date'] === $slot['date']
                && $existing['startTime'] === $slot['startTime']
                && $existing['endTime'] === $slot['endTime']
            ) {
                $keptIds[] = $slot['id'];
                continue;
            }

            $this->validateNewSlot($slot);
            $newSlots[] = ['date' => $slot['date'], 'startTime' => $slot['startTime'], 'endTime' => $slot['endTime']];
        }

        $removed = array_values(array_filter($current, fn ($slot) => !in_array($slot['id'], $keptIds, true)));

        if (empty($newSlots) && empty($removed)) {
            return $session;
        }

        $this->sportSessionRepository->updateSlotProposals($sessionId, array_column($removed, 'id'), $newSlots);

        $this->notifyAddedSlots($session, $newSlots);
        $this->notifyRemovedSlots($session, $removed);

        return $this->sportSessionRepository->findById($sessionId);
    }

    private function validateNewSlot(array $slot): void
    {
        $date = \DateTime::createFromFormat('Y-m-d', $slot['date'] ?? '');
        $start = \DateTime::createFromFormat('H:i', $slot['startTime'] ?? '');
        $end = \DateTime::createFromFormat('H:i', $slot['endTime'] ?? '');
        if (!$date || !$start || !$end) {
            throw new Exception('Créneau invalide');
        }

        if ($slot['endTime'] === $slot['startTime']) {
            throw new Exception('L\'heure de fin doit être différente de l\'heure de début');
        }

        $startAt = new \DateTime($slot['date'] . ' ' . $slot['startTime'], new \DateTimeZone('Europe/Paris'));
        if ($startAt <= new \DateTime('now', new \DateTimeZone('Europe/Paris'))) {
            throw new Exception('Un créneau proposé est déjà passé');
        }
    }

    private function notifyAddedSlots(SportSession $session, array $newSlots): void
    {
        if (empty($newSlots)) {
            return;
        }

        $sportName = DateFormatterService::getSportName($session->getSport());
        $title = count($newSlots) === 1
            ? 'Nouveau créneau proposé : ' . DateFormatterService::formatSlotLabel($newSlots[0])
            : count($newSlots) . ' nouveaux créneaux proposés';

        foreach ($session->getSlotInvitees() as $invitee) {
            $this->sessionNotificationService->notify(
                $invitee['id'],
                $session->getId(),
                $title,
                "Session de {$sportName} : tu es dispo ?",
                'slot_added'
            );
        }
    }

    private function notifyRemovedSlots(SportSession $session, array $removed): void
    {
        // Par invité : les créneaux retirés qu'il avait cochés
        $removedByUser = [];
        foreach ($removed as $slot) {
            foreach ($slot['availableUserIds'] ?? [] as $userId) {
                $removedByUser[$userId][] = $slot;
            }
        }

        foreach ($removedByUser as $userId => $slots) {
            $title = count($slots) === 1
                ? 'Créneau retiré : ' . DateFormatterService::formatSlotLabel($slots[0])
                : count($slots) . ' créneaux que tu avais cochés ont été retirés';

            $this->sessionNotificationService->notify(
                $userId,
                $session->getId(),
                $title,
                'Tes autres disponibilités sont conservées.',
                'slot_removed'
            );
        }
    }
}
