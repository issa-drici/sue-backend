<?php

namespace App\UseCases\SportSession;

use App\Repositories\SportSession\SportSessionRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class FindMyParticipationsUseCase
{
    public function __construct(
        private SportSessionRepositoryInterface $sportSessionRepository
    ) {}

    public function execute(string $userId, array $filters = [], int $page = 1, int $limit = 20): LengthAwarePaginator
    {
        // Validation des filtres
        $validFilters = $this->validateFilters($filters);

        return $this->sportSessionRepository->findByParticipantPaginated($userId, $validFilters, $page, $limit);
    }

    private function validateFilters(array $filters): array
    {
        $validFilters = [];

        if (isset($filters['sport']) && in_array($filters['sport'], ['tennis', 'golf', 'musculation', 'football', 'basketball'])) {
            $validFilters['sport'] = $filters['sport'];
        }

        if (isset($filters['date']) && $this->isValidDate($filters['date'])) {
            $validFilters['date'] = $filters['date'];
        }

        return $validFilters;
    }

    private function isValidDate(string $date): bool
    {
        $dateTime = \DateTime::createFromFormat('Y-m-d', $date);
        return $dateTime && $dateTime->format('Y-m-d') === $date;
    }
}
