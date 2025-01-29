<?php

namespace App\Http\Controllers\Ranking;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FindRankingsAction extends Controller
{
    /**
     * Récupère le classement des utilisateurs
     * 
     * @param Request $request La requête HTTP contenant le type de classement
     * 
     * TODO:
     * - Valider le paramètre type (day, week, month)
     * - Calculer la période en fonction du type
     * - Récupérer tous les utilisateurs avec leurs statistiques sur la période
     * - Calculer le total d'XP pour chaque utilisateur sur la période
     * - Trier par XP décroissant
     * - Ajouter le streak depuis user_profiles
     * - Identifier l'utilisateur connecté dans la liste
     * - Retourner la liste paginée avec la position de l'utilisateur
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Données temporaires de test
        return response()->json([
            'current_user_rank' => 5,
            'rankings' => [
                [
                    'rank' => 1,
                    'user_id' => 'user-1',
                    'full_name' => 'John Doe',
                    'total_xp' => 2000,
                    'streak' => 7,
                    'is_current_user' => false
                ],
                [
                    'rank' => 2,
                    'user_id' => 'user-2',
                    'full_name' => 'Jane Smith',
                    'total_xp' => 1800,
                    'streak' => 5,
                    'is_current_user' => false
                ]
            ],
            'period' => [
                'type' => $request->input('type', 'week'),
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-07'
            ]
        ]);
    }
} 