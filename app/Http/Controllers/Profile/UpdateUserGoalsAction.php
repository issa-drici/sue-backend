<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateUserGoalsAction extends Controller
{
    /**
     * Met à jour les objectifs de l'utilisateur
     * 
     * @param Request $request La requête HTTP contenant les nouveaux objectifs
     * 
     * TODO:
     * - Valider le paramètre current_goals (string, max:500)
     * - Récupérer l'utilisateur authentifié
     * - Mettre à jour le champ current_goals dans user_profiles
     * - Retourner les objectifs mis à jour
     * - Gérer le cas où le profil n'existe pas encore
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Données temporaires de test
        return response()->json([
            'current_goals' => $request->input('current_goals')
        ]);
    }
} 