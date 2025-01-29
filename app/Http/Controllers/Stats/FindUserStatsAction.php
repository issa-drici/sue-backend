<?php

namespace App\Http\Controllers\Stats;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FindUserStatsAction extends Controller
{
    /**
     * Récupère les statistiques de l'utilisateur sur une période donnée
     * 
     * @param Request $request La requête HTTP contenant les paramètres de filtrage
     * 
     * TODO:
     * - Valider les paramètres start_date, end_date ou range
     * - Si range est spécifié (day, week, month), calculer les dates
     * - Calculer pour la période :
     *   - Total XP gagné
     *   - Nombre d'exercices complétés
     *   - Temps total d'entraînement
     * - Pour chaque jour de la période :
     *   - XP gagné
     *   - Temps d'entraînement
     * - Gérer les cas d'erreur de paramètres invalides
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Données temporaires de test
        return response()->json([
            'period' => [
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-07'
            ],
            'totals' => [
                'xp' => 500,
                'completed_exercises' => 5,
                'training_time' => 1800
            ],
            'daily_stats' => [
                [
                    'date' => '2024-01-01',
                    'xp' => 100,
                    'training_time' => 300
                ],
                [
                    'date' => '2024-01-02',
                    'xp' => 200,
                    'training_time' => 600
                ]
            ]
        ]);
    }
} 