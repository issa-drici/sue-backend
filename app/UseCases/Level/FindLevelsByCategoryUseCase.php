<?php

namespace App\UseCases\Level;

use App\Repositories\Level\LevelRepositoryInterface;

class FindLevelsByCategoryUseCase
{
    public function __construct(
        private LevelRepositoryInterface $levelRepository
    ) {}

    public function execute(string $category): array
    {
        $levels = $this->levelRepository->findByCategory($category);

        return [
            'levels' => $levels
        ];
    }
}
