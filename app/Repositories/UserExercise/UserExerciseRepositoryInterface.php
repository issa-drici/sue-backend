<?php

namespace App\Repositories\UserExercise;

use App\Entities\UserExercise;
use App\Models\UserExerciseModel;

interface UserExerciseRepositoryInterface
{
    public function findByUserAndExercise(string $userId, string $exerciseId): ?UserExercise;
    public function save(UserExercise $userExercise): UserExercise;
    public function updateWatchTime(string $userId, string $exerciseId, int $watchTime): UserExercise;
    public function markAsCompleted(string $userId, string $exerciseId): void;
} 