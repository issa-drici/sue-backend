<?php

namespace App\UseCases\SportSession;

use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Services\DateFormatterService;
use App\Services\SessionNotificationService;
use Carbon\Carbon;

/**
 * "Trouver un créneau" : notifications planifiées pour l'organisateur.
 * - Rappel pour confirmer un créneau, 48h avant le premier créneau à venir
 *   (au plus tôt 3h après la création, au plus tard 2h avant ce créneau). Envoyé une fois.
 * - Plus aucun créneau à venir : "Aucun créneau n'a été confirmé", pour qu'il en ajoute.
 */
class SendSlotFindingRemindersUseCase
{
    private const HOURS_BEFORE_FIRST_SLOT = 48;
    private const MIN_HOURS_AFTER_CREATION = 3;
    private const MIN_HOURS_BEFORE_SLOT = 2;

    public function __construct(
        private SportSessionRepositoryInterface $sportSessionRepository,
        private SessionNotificationService $sessionNotificationService
    ) {}

    public function execute(?Carbon $now = null): array
    {
        $now = $now ?? Carbon::now();
        $result = ['reminders' => 0, 'expired' => 0];

        foreach ($this->sportSessionRepository->findSessionsAwaitingSlot() as $row) {
            $session = $row['session'];
            $slots = $session->getSlotProposals();
            $sportName = DateFormatterService::getSportName($session->getSport());
            $organizerId = $session->getOrganizer()->getId();

            if (empty($slots)) {
                if ($row['expiredNotifiedAt'] === null) {
                    $this->sessionNotificationService->notify(
                        $organizerId,
                        $session->getId(),
                        "Aucun créneau n'a été confirmé",
                        "Ta session de {$sportName} attend toujours son créneau : ajoutes-en de nouveaux.",
                        'slot_expired'
                    );
                    $this->sportSessionRepository->markSlotNotificationSent($session->getId(), 'slot_expired_notified_at');
                    $result['expired']++;
                }
                continue;
            }

            if ($row['reminderSentAt'] !== null) {
                continue;
            }

            $firstStart = Carbon::parse(min(array_column($slots, 'startDate')));
            $remindAt = $firstStart->copy()->subHours(self::HOURS_BEFORE_FIRST_SLOT);
            $earliest = Carbon::parse($row['createdAt'])->addHours(self::MIN_HOURS_AFTER_CREATION);
            $latest = $firstStart->copy()->subHours(self::MIN_HOURS_BEFORE_SLOT);
            if ($remindAt->lt($earliest)) {
                $remindAt = $earliest;
            }
            if ($remindAt->gt($latest)) {
                $remindAt = $latest;
            }

            // Créneau trop proche de la création : l'organisateur vient de la créer, pas de rappel
            if ($remindAt->lte(Carbon::parse($row['createdAt'])) || $now->lt($remindAt)) {
                continue;
            }

            $best = $session->getBestSlotProposal();
            $count = $best['availabilityCount'] ?? 0;
            $message = $count > 0
                ? ucfirst(DateFormatterService::formatSlotLabel($best)) . " arrange le plus de monde ({$count} dispo" . ($count > 1 ? 's' : '') . ')'
                : "Personne n'a encore répondu : relance tes invités ou confirme un créneau.";

            $this->sessionNotificationService->notify(
                $organizerId,
                $session->getId(),
                "Choisis le créneau de ta session de {$sportName}",
                $message,
                'slot_reminder'
            );
            $this->sportSessionRepository->markSlotNotificationSent($session->getId(), 'slot_reminder_sent_at');
            $result['reminders']++;
        }

        return $result;
    }
}
