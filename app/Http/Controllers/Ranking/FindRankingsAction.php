<?php

namespace App\Http\Controllers\Ranking;

use App\Http\Controllers\Controller;
use App\UseCases\Ranking\FindRankingsUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FindRankingsAction extends Controller
{
    public function __construct(
        private FindRankingsUseCase $useCase
    ) {}

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
        try {
            $type = $request->input('range', 'week');
            $result = $this->useCase->execute($type);
            return response()->json($result);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
} 