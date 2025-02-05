<?php

namespace App\UseCases\UserExercise;

use App\Repositories\UserExercise\UserExerciseRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Repositories\Exercise\ExerciseRepositoryInterface;

class UpdateUserExerciseProgressUseCase
{
    public function __construct(
        private UserExerciseRepositoryInterface $userExerciseRepository,
        private ExerciseRepositoryInterface $exerciseRepository
    ) {}

    public function execute(string $exerciseId, int $watchTime): array
    {
        // Vérification de l'authentification
        $user = Auth::user();
        if (!$user) {
            throw ValidationException::withMessages([
                'auth' => ['Utilisateur non authentifié']
            ]);
        }
        $exercise = $this->exerciseRepository->findById($exerciseId);
        if (!$exercise) {
            throw ValidationException::withMessages([
                'exercise' => ['Exercice non trouvé']
            ]);
        }
        $today = now()->startOfDay();
        $existingExercise = $this->userExerciseRepository->findByUserAndExerciseForDate(
            $user->id, 
            $exerciseId, 
            $today
        );

        // On ajoute toujours le watch_time
        $newWatchTime = $existingExercise 
            ? $existingExercise->getWatchTime() + $watchTime 
            : $watchTime;
            
        $userExercise = $this->userExerciseRepository->updateWatchTime(
            $user->id,
            $exerciseId,
            $newWatchTime
        );

        // Marquer comme complété si nécessaire
        $isCompleted = false;
        if ($userExercise->getWatchTime() >= $exercise->getDuration() && !$userExercise->getCompletedAt()) {
            $this->userExerciseRepository->markAsCompleted($user->id, $exerciseId);
            $isCompleted = true;
        }

        return [
            'watch_time' => $userExercise->getWatchTime(),
            'is_completed' => $isCompleted
        ];
    }
} 