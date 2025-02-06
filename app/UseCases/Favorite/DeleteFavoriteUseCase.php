<?php

namespace App\UseCases\Favorite;

use App\Repositories\Favorite\FavoriteRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class DeleteFavoriteUseCase
{
    public function __construct(
        private FavoriteRepositoryInterface $favoriteRepository
    ) {}

    public function execute(string $exerciseId): void
    {
        // Vérification de l'authentification
        $user = Auth::user();
        if (!$user) {
            throw ValidationException::withMessages([
                'auth' => ['Utilisateur non authentifié']
            ]);
        }

        // Vérification si le favori existe
        $favorite = $this->favoriteRepository->findByUserAndExercise($user->id, $exerciseId);
        if (!$favorite) {
            throw ValidationException::withMessages([
                'favorite' => ['Favori non trouvé']
            ]);
        }

        // Suppression du favori
        $this->favoriteRepository->delete($favorite['id']);
    }
} 