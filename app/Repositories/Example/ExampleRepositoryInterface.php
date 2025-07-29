<?php

namespace App\Repositories\Example;

use App\Entities\ExampleEntity;

/**
 * Example Repository Interface - Template for creating new repository interfaces
 *
 * This interface defines the contract for repository implementations
 * following the Repository pattern in Clean Architecture.
 */
interface ExampleRepositoryInterface
{
    /**
     * Find all examples
     */
    public function findAll(): array;

    /**
     * Find all active examples
     */
    public function findAllActive(): array;

    /**
     * Find example by ID
     */
    public function findById(string $id): ?ExampleEntity;

    /**
     * Find examples by name
     */
    public function findByName(string $name): array;

    /**
     * Create a new example
     */
    public function create(array $data): ExampleEntity;

    /**
     * Update an existing example
     */
    public function update(string $id, array $data): bool;

    /**
     * Delete an example
     */
    public function delete(string $id): bool;

    /**
     * Activate an example
     */
    public function activate(string $id): bool;

    /**
     * Deactivate an example
     */
    public function deactivate(string $id): bool;

    /**
     * Check if example exists
     */
    public function exists(string $id): bool;
}
