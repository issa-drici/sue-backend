<?php

namespace App\Http\Controllers\Favorite;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FindAllFavoritesAction extends Controller
{
    /**
     * Récupère tous les exercices favoris de l'utilisateur
     * 
     * @param Request $request La requête HTTP
     * 
     * TODO:
     * - Vérifier si l'utilisateur est authentifié
     * - Récupérer tous les favoris de l'utilisateur
     * - Joindre les informations des exercices
     * - Paginer les résultats si nécessaire
     * - Retourner la liste des favoris avec les détails des exercices
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Données temporaires de test
        return response()->json([
            'data' => [
                [
                    'id' => 'favorite-1',
                    'exercise' => [
                        'id' => 'exercise-1',
                        'title' => 'Exercise 1',
                        'level' => 1
                    ]
                ],
                [
                    'id' => 'favorite-2',
                    'exercise' => [
                        'id' => 'exercise-2',
                        'title' => 'Exercise 2',
                        'level' => 2
                    ]
                ]
            ]
        ]);
    }
} 