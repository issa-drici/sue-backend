<?php

namespace App\Http\Controllers\Exercise;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class FindExerciseByIdAction extends Controller
{
    /**
     * Récupère les détails d'un exercice spécifique
     * 
     * @param string $exerciseId L'ID de l'exercice à récupérer
     * 
     * TODO:
     * - Vérifier si l'exercice existe
     * - Récupérer les données complètes de l'exercice
     * - Vérifier si l'utilisateur connecté a complété cet exercice
     * - Récupérer le temps de visionnage de l'utilisateur
     * - Retourner une réponse JSON avec toutes les informations
     * - Gérer le cas où l'exercice n'existe pas (404)
     */
    public function __invoke(string $exerciseId): JsonResponse
    {
        // Données temporaires de test
        return response()->json([
            'id' => $exerciseId,
            'level' => 1,
            'banner_url' => 'https://example.com/banner.jpg',
            'title' => 'Exercise Title',
            'description' => 'Detailed description of the exercise',
            'duration' => 300,
            'xp_value' => 100,
            'user_progress' => [
                'is_completed' => false,
                'watch_time' => 150
            ]
        ]);
    }
} 