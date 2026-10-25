<?php

namespace App\UseCases\SportSession;

use App\Entities\SportSession;
use App\Repositories\SportSession\SportSessionRepositoryInterface;
use Exception;

/**
 * "Trouver un créneau" : un invité retire un créneau qu'il a proposé (ex. erreur d'heure).
 * L'organisateur, lui, retire n'importe quel créneau depuis l'édition de la liste.
 */
class RemoveProposedSlotUseCase
{
    public function __construct(
        private SportSessionRepositoryInterface $sportSessionRepository
    ) {}

    public function execute(string $sessionId, string $userId, string $slotId): SportSession
    {
        $session = $this->sportSessionRepository->findById($sessionId);

        if (!$session) {
            throw new Exception('Session non trouvée');
        }

        if ($session->isScheduled()) {
            throw new Exception('Le créneau de cette session est déjà confirmé');
        }

        $slot = null;
        foreach ($session->getSlotProposals() as $existing) {
            if ($existing['id'] === $slotId) {
                $slot = $existing;
            }
        }

        if (!$slot) {
            throw new Exception('Créneau introuvable');
        }

        if (($slot['proposedBy'] ?? null) !== $userId) {
            throw new Exception('Tu ne peux retirer que les créneaux que tu as proposés');
        }

        if (count($session->getSlotProposals()) <= 1) {
            throw new Exception('Il doit rester au moins un créneau');
        }

        $this->sportSessionRepository->updateSlotProposals($sessionId, [$slotId], []);

        return $this->sportSessionRepository->findById($sessionId);
    }
}
