<?php

namespace App\Repositories\SportSession;

use App\Entities\SportSession;
use Illuminate\Pagination\LengthAwarePaginator;

interface SportSessionRepositoryInterface
{
    public function findById(string $id): ?SportSession;

    public function findByShareToken(string $shareToken): ?SportSession;

    public function findAll(array $filters = [], int $page = 1, int $limit = 20): LengthAwarePaginator;

    public function create(array $data): SportSession;

    public function update(string $id, array $data): ?SportSession;

    public function delete(string $id): bool;

    public function findByOrganizer(string $organizerId, array $filters = []): array;

    public function findByParticipant(string $userId, array $filters = []): array;

    public function findByParticipantPaginated(string $userId, array $filters = [], int $page = 1, int $limit = 20): LengthAwarePaginator;

    public function findMySessions(string $userId, array $filters = [], int $page = 1, int $limit = 20): LengthAwarePaginator;

    public function addParticipant(string $sessionId, string $userId, string $status = 'pending'): bool;

    public function updateParticipantStatus(string $sessionId, string $userId, string $status): bool;

    public function removeParticipant(string $sessionId, string $userId): bool;

    public function addComment(string $sessionId, string $userId, string $content): bool;

    public function getComments(string $sessionId): array;

    public function isUserInvited(string $sessionId, string $userId): bool;

    public function isUserParticipant(string $sessionId, string $userId): bool;

    public function findParticipant(string $sessionId, string $userId): ?array;

    public function inviteUser(string $sessionId, string $userId): bool;

    /**
     * "Trouver un créneau" : remplace les disponibilités de l'utilisateur sur les créneaux de la session
     * et marque qu'il a répondu (même sans créneau).
     */
    public function setSlotAvailabilities(string $sessionId, string $userId, array $slotIds): void;

    /**
     * "Trouver un créneau" : fixe la session sur le créneau choisi (dates recopiées, scheduling = scheduled).
     */
    public function confirmSlot(string $sessionId, string $slotId): void;

    /**
     * "Trouver un créneau" : retire des créneaux et en ajoute de nouveaux ([{date, startTime, endTime,
     * proposedBy?}], heure de Paris), puis recale la plage start_date / end_date sur les créneaux à venir.
     * Un créneau proposé par un invité (proposedBy) le compte d'office comme dispo.
     */
    public function updateSlotProposals(string $sessionId, array $removedSlotIds, array $newSlots): void;

    /**
     * "Trouver un créneau" : sessions actives en attente de créneau, avec les infos utiles aux rappels
     * ([session, createdAt, reminderSentAt, expiredNotifiedAt]).
     */
    public function findSessionsAwaitingSlot(): array;

    /**
     * Marque une notification "Trouver un créneau" comme envoyée (slot_reminder_sent_at | slot_expired_notified_at).
     */
    public function markSlotNotificationSent(string $sessionId, string $column): void;

    /**
     * Trouve les sessions actives qui commencent à une date et heure précises
     */
    public function findByDateAndTime(string $date, string $time): array;
}
