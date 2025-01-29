<?php

namespace App\Http\Controllers\UserExercise;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompleteUserExerciseAction extends Controller
{
    /**
     * Marque un exercice comme complété pour l'utilisateur
     * 
     * @param Request $request La requête HTTP
     * @param string $exerciseId L'ID de l'exercice à compléter
     * 
     * TODO:
     * - Vérifier si l'exercice existe
     * - Vérifier si l'utilisateur est authentifié
     * - Vérifier si un enregistrement user_exercise existe déjà
     * - Si oui, mettre à jour completed_at
     * - Si non, créer un nouvel enregistrement
     * - Mettre à jour les statistiques dans user_profiles:
     *   - Incrémenter completed_videos
     *   - Ajouter xp_value aux total_xp
     *   - Ajouter duration au total_training_time
     * - Retourner les statistiques mises à jour
     */
    public function __invoke(Request $request, string $exerciseId): JsonResponse
    {
        // Données temporaires de test
        return response()->json([
            'stats' => [
                'total_xp' => 1000,
                'completed_videos' => 10,
                'total_training_time' => 3600
            ]
        ]);
    }
} 