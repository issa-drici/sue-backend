<?php

namespace App\Http\Controllers\Favorite;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreateFavoriteAction extends Controller
{
    /**
     * Ajoute un exercice aux favoris de l'utilisateur
     * 
     * @param Request $request La requête HTTP contenant l'exercise_id
     * 
     * TODO:
     * - Valider le paramètre exercise_id
     * - Vérifier si l'exercice existe
     * - Vérifier si l'utilisateur est authentifié
     * - Vérifier si l'exercice n'est pas déjà en favori
     * - Créer l'enregistrement dans la table favorites
     * - Retourner le favori créé avec les informations de l'exercice
     * - Gérer le cas où l'exercice est déjà en favori (409 Conflict)
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Données temporaires de test
        return response()->json([
            'id' => 'favorite-1',
            'exercise' => [
                'id' => $request->input('exercise_id'),
                'title' => 'Exercise Title',
                'level' => 1
            ]
        ], 201);
    }
} 