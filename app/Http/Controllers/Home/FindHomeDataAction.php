<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FindHomeDataAction extends Controller
{
    /**
     * Récupère les données pour la page d'accueil
     * 
     * @param Request $request La requête HTTP
     * 
     * TODO:
     * - Vérifier si l'utilisateur est authentifié
     * - Calculer le temps total d'entraînement depuis user_exercises
     * - Calculer l'XP total depuis les exercices complétés
     * - Compter le nombre total de vidéos complétées
     * - Récupérer les 3 derniers exercices complétés avec leurs détails
     * - Retourner toutes ces informations dans une réponse JSON structurée
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Données temporaires de test
        return response()->json([
            'stats' => [
                'total_training_time' => 7200,
                'total_xp' => 1500,
                'completed_videos' => 15
            ],
            'recent_exercises' => [
                [
                    'id' => 'exercise-1',
                    'title' => 'Recent Exercise 1',
                    'completed_at' => '2024-01-10T15:30:00Z'
                ],
                [
                    'id' => 'exercise-2',
                    'title' => 'Recent Exercise 2',
                    'completed_at' => '2024-01-09T14:20:00Z'
                ],
                [
                    'id' => 'exercise-3',
                    'title' => 'Recent Exercise 3',
                    'completed_at' => '2024-01-08T10:15:00Z'
                ]
            ]
        ]);
    }
} 