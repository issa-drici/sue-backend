<?php

namespace App\Http\Controllers\UserExercise;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateUserExerciseProgressAction extends Controller
{
    /**
     * Met à jour le temps de visionnage d'un exercice
     * 
     * @param Request $request La requête HTTP contenant le watch_time
     * @param string $exerciseId L'ID de l'exercice
     * 
     * TODO:
     * - Valider le paramètre watch_time (entier positif)
     * - Vérifier si l'exercice existe
     * - Vérifier si l'utilisateur est authentifié
     * - Créer ou mettre à jour l'enregistrement user_exercise
     * - Si watch_time >= duration de l'exercice, marquer comme complété
     * - Mettre à jour total_training_time dans user_profiles
     * - Retourner le temps de visionnage mis à jour
     */
    public function __invoke(Request $request, string $exerciseId): JsonResponse
    {
        // Données temporaires de test
        return response()->json([
            'watch_time' => $request->input('watch_time', 0),
            'is_completed' => false
        ]);
    }
} 