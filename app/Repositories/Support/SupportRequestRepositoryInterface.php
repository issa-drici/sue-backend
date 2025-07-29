<?php

namespace App\Repositories\Support;

use App\Entities\SupportRequest;

/**
 * Support Request Repository Interface
 */
interface SupportRequestRepositoryInterface
{
    /**
     * Find all support requests for a user
     */
    public function findAllByUserId(string $userId): array;

    /**
     * Find support request by ID
     */
    public function findById(string $id): ?SupportRequest;

    /**
     * Create a new support request
     */
    public function create(array $data): SupportRequest;

    /**
     * Update a support request
     */
    public function update(string $id, array $data): bool;

    /**
     * Delete a support request
     */
    public function delete(string $id): bool;
}
