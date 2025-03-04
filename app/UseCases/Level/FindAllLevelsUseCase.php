<?php

namespace App\UseCases\Level;

use App\Repositories\Level\LevelRepositoryInterface;

class FindAllLevelsUseCase
{
    public function __construct(
        private LevelRepositoryInterface $levelRepository
    ) {}

    public function execute(): array
    {
        $levels = $this->levelRepository->findAll();

        return [
            'levels' => $levels
        ];
    }
}
