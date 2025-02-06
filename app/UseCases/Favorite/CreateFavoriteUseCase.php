<?php

namespace App\UseCases\Favorite;

use App\Repositories\Exercise\ExerciseRepositoryInterface;
use App\Repositories\Favorite\FavoriteRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class CreateFavoriteUseCase
{
    public function __construct(
        private ExerciseRepositoryInterface $exerciseRepository,
        private FavoriteRepositoryInterface $favoriteRepository
    ) {}

    public function execute(string $exerciseId): array
    {
        // Vérification de l'authentification
        $user = Auth::user();
        if (!$user) {
            throw ValidationException::withMessages([
                'auth' => ['Utilisateur non authentifié']
            ]);
        }

        // Vérification si l'exercice existe
        $exercise = $this->exerciseRepository->findById($exerciseId);
        if (!$exercise) {
            throw ValidationException::withMessages([
                'exercise' => ['Exercice non trouvé']
            ]);
        }

        // Vérification si déjà en favori
        $existingFavorite = $this->favoriteRepository->findByUserAndExercise($user->id, $exerciseId);
        if ($existingFavorite) {
            throw ValidationException::withMessages([
                'favorite' => ['Cet exercice est déjà en favori']
            ]);
        }

        // Création du favori
        $favorite = $this->favoriteRepository->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'exercise_id' => $exerciseId
        ]);

        return [
            'id' => $favorite['id'],
            'exercise' => [
                'id' => $exercise->getId(),
                'title' => $exercise->getTitle(),
                'level' => $exercise->getLevel()
            ]
        ];
    }
} 