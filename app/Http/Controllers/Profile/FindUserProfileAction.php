<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FindUserProfileAction extends Controller
{
    /**
     * Récupère le profil complet de l'utilisateur
     * 
     * @param Request $request La requête HTTP
     * 
     * TODO:
     * - Récupérer l'utilisateur authentifié
     * - Charger les données de user_profiles
     * - Récupérer la liste des exercices favoris avec leurs détails
     * - Calculer les statistiques globales :
     *   - Total XP
     *   - Temps total d'entraînement
     *   - Nombre de vidéos complétées
     *   - Nombre de jours d'entraînement
     * - Retourner toutes ces informations dans une réponse structurée
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Données temporaires de test
        return response()->json([
            'user' => [
                'id' => 'user-1',
                'full_name' => 'John Doe',
                'email' => 'john@example.com',
                'avatar_url' => 'https://example.com/avatar.jpg'
            ],
            'stats' => [
                'total_xp' => 2000,
                'total_training_time' => 7200,
                'completed_videos' => 20,
                'completed_days' => 15,
                'current_goals' => 'Atteindre 3000 XP ce mois-ci'
            ],
            'favorites' => [
                [
                    'id' => 'favorite-1',
                    'exercise' => [
                        'id' => 'exercise-1',
                        'title' => 'Exercise Title',
                        'level' => 2
                    ]
                ]
            ]
        ]);
    }
} 