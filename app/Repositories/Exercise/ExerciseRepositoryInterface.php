<?php

namespace App\Repositories\Exercise;

use App\Entities\Exercise;
use Illuminate\Pagination\LengthAwarePaginator;

interface ExerciseRepositoryInterface
{
    public function findAll(): array;
    public function findByIds(array $ids): array;
    public function findCompletedExerciseIds(string $userId): array;
} 