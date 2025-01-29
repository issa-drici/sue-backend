<?php

namespace App\Http\Controllers\Exercise;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FindAllExercisesAction extends Controller
{
    /**
     * Récupère la liste des exercices avec pagination optionnelle
     * 
     * @param Request $request La requête HTTP contenant les paramètres de filtrage
     * 
     * TODO:
     * - Récupérer le paramètre de niveau (level) depuis la requête
     * - Récupérer les paramètres de pagination (page, per_page)
     * - Filtrer les exercices par niveau si spécifié
     * - Paginer les résultats si demandé
     * - Joindre les informations de completion pour l'utilisateur connecté
     * - Retourner la réponse paginée avec les métadonnées (total, current_page, etc.)
     * - Gérer les cas d'erreur de paramètres invalides
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Données temporaires de test
        return response()->json([
            'data' => [
                [
                    'id' => 'exercise-1',
                    'level' => 1,
                    'title' => 'Exercise 1',
                    'description' => 'Description of exercise 1',
                    'duration' => 300,
                    'xp_value' => 100,
                    'is_completed' => false
                ],
                [
                    'id' => 'exercise-2',
                    'level' => 2,
                    'title' => 'Exercise 2',
                    'description' => 'Description of exercise 2',
                    'duration' => 600,
                    'xp_value' => 200,
                    'is_completed' => true
                ]
            ],
            'meta' => [
                'current_page' => 1,
                'per_page' => 10,
                'total' => 2
            ]
        ]);
    }
} 