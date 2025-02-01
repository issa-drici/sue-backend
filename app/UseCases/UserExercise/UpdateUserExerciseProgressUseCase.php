<?php

namespace App\UseCases\UserExercise;

use App\Models\ExerciseModel;
use App\Repositories\UserExercise\UserExerciseRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UpdateUserExerciseProgressUseCase
{
    public function __construct(
        private UserExerciseRepositoryInterface $userExerciseRepository
    ) {}

    public function execute(string $exerciseId, int $watchTime): array
    {
        // Validation du watch_time
        if ($watchTime < 0) {
            throw ValidationException::withMessages([
                'watch_time' => ['Le temps de visionnage doit être positif']
            ]);
        }

        // Vérification de l'authentification
        $user = Auth::user();
        if (!$user) {
            throw ValidationException::withMessages([
                'auth' => ['Utilisateur non authentifié']
            ]);
        }

        // Vérification de l'existence de l'exercice
        $exercise = ExerciseModel::find($exerciseId);
        if (!$exercise) {
            throw ValidationException::withMessages([
                'exercise_id' => ['Exercice non trouvé']
            ]);
        }

        // Mise à jour du temps de visionnage
        $userExercise = $this->userExerciseRepository->updateWatchTime(
            $user->id,
            $exerciseId,
            $watchTime
        );

        // Marquer comme complété si nécessaire
        $isCompleted = false;
        if ($watchTime >= $exercise->duration && !$userExercise->getCompletedAt()) {
            $this->userExerciseRepository->markAsCompleted($user->id, $exerciseId);
            $isCompleted = true;
        }

        return [
            'watch_time' => $watchTime,
            'is_completed' => $isCompleted
        ];
    }
} 