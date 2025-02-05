<?php

namespace App\UseCases\UserExercise;

use App\Repositories\UserExercise\UserExerciseRepositoryInterface;
use App\Entities\UserExercise;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Repositories\Exercise\ExerciseRepositoryInterface;
use Illuminate\Support\Str;

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
        
        $userExercise = $this->userExerciseRepository->findByUserAndExerciseForDate(
            $user->id, 
            $exerciseId,
            $today
        );

        if (!$userExercise) {
            // Création d'une nouvelle entité
            $userExercise = new UserExercise(
                (string) Str::uuid(),
                $user->id,
                $exerciseId,
                null,
                $watchTime,
                $today
            );
        } else {
            // Mise à jour du temps de visionnage
            $userExercise->setWatchTime($userExercise->getWatchTime() + $watchTime);
        }

        // Sauvegarde de l'entité
        $userExercise = $this->userExerciseRepository->save($userExercise);

        // Marquer comme complété si nécessaire
        $isCompleted = false;
        if ($userExercise->getWatchTime() >= $exercise->getDuration() && !$userExercise->getCompletedAt()) {
            $userExercise->setCompletedAt(now());
            $this->userExerciseRepository->save($userExercise);
            $isCompleted = true;
        }

        return [
            'watch_time' => $userExercise->getWatchTime(),
            'is_completed' => $isCompleted
        ];
    }
} 