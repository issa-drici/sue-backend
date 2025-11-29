<?php

namespace App\Repositories\SportSession;

use App\Entities\SportSession;
use Illuminate\Pagination\LengthAwarePaginator;

interface SportSessionRepositoryInterface
{
    public function findById(string $id): ?SportSession;

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
     * Trouve les sessions actives qui commencent à une date et heure précises
     */
    public function findByDateAndTime(string $date, string $time): array;
}
