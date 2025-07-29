<?php

namespace App\UseCases\SportSession;

use App\Repositories\SportSession\SportSessionRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class FindAllSportSessionsUseCase
{
    public function __construct(
        private SportSessionRepositoryInterface $sportSessionRepository
    ) {}

    public function execute(array $filters = [], int $page = 1, int $limit = 20, string $userId = null): LengthAwarePaginator
    {
        // Validation des filtres
        $validFilters = $this->validateFilters($filters);

        // Si un userId est fourni, retourner uniquement les sessions de l'utilisateur
        if ($userId) {
            return $this->sportSessionRepository->findMySessions($userId, $validFilters, $page, $limit);
        }

        // Sinon, retourner toutes les sessions (pour compatibilité)
        return $this->sportSessionRepository->findAll($validFilters, $page, $limit);
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

        if (isset($filters['organizer_id']) && is_string($filters['organizer_id'])) {
            $validFilters['organizer_id'] = $filters['organizer_id'];
        }

        return $validFilters;
    }

    private function isValidDate(string $date): bool
    {
        $dateTime = \DateTime::createFromFormat('Y-m-d', $date);
        return $dateTime && $dateTime->format('Y-m-d') === $date;
    }
}
