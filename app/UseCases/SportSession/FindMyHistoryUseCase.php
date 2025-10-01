<?php

namespace App\UseCases\SportSession;

use App\Repositories\SportSession\SportSessionRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class FindMyHistoryUseCase
{
    public function __construct(
        private SportSessionRepositoryInterface $sportSessionRepository
    ) {}

    public function execute(string $userId, array $filters = [], int $page = 1, int $limit = 20): LengthAwarePaginator
    {
        // Validation des filtres
        $validFilters = $this->validateFilters($filters);

        // Ajouter automatiquement le filtre pour les sessions passées
        $validFilters['past_sessions'] = true;

        return $this->sportSessionRepository->findMySessions($userId, $validFilters, $page, $limit);
    }

    private function validateFilters(array $filters): array
    {
        $validFilters = [];

        if (isset($filters['sport']) && \App\Services\SportService::isValidSport($filters['sport'])) {
            $validFilters['sport'] = $filters['sport'];
        }

        return $validFilters;
    }
}
