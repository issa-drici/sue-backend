<?php

namespace App\UseCases\Example;

use App\Repositories\Example\ExampleRepositoryInterface;

/**
 * Find All Examples Use Case - Template for creating new use cases
 *
 * This use case demonstrates how to implement business logic
 * following the Clean Architecture pattern.
 */
class FindAllExamplesUseCase
{
    public function __construct(
        private ExampleRepositoryInterface $exampleRepository
    ) {}

    /**
     * Execute the use case
     *
     * @param array $filters Optional filters to apply
     * @return array Formatted response data
     */
    public function execute(array $filters = []): array
    {
        $examples = $this->exampleRepository->findAll();

        // Apply filters if provided
        if (!empty($filters)) {
            $examples = $this->applyFilters($examples, $filters);
        }

        return $this->formatResponse($examples);
    }

    /**
     * Apply filters to the examples
     */
    private function applyFilters(array $examples, array $filters): array
    {
        return array_filter($examples, function ($example) use ($filters) {
            // Filter by active status
            if (isset($filters['active']) && $filters['active'] !== null) {
                if ($example->isActive() !== $filters['active']) {
                    return false;
                }
            }

            // Filter by name
            if (isset($filters['name']) && !empty($filters['name'])) {
                if (stripos($example->getName(), $filters['name']) === false) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Format the response for the API
     */
    private function formatResponse(array $examples): array
    {
        return [
            'data' => array_map(fn($example) => [
                'id' => $example->getId(),
                'name' => $example->getName(),
                'description' => $example->getDescription(),
                'is_active' => $example->isActive(),
                'created_at' => $example->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $example->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ], $examples),
            'meta' => [
                'total' => count($examples),
                'timestamp' => now()->toISOString(),
            ]
        ];
    }
}
