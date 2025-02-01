<?php

namespace App\Http\Controllers\Exercise;

use App\Http\Controllers\Controller;
use App\UseCases\Exercise\FindAllExercisesUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FindAllExercisesAction extends Controller
{
    public function __construct(
        private FindAllExercisesUseCase $useCase
    ) {}

    /**
     * Récupère la liste des exercices avec pagination optionnelle
     * 
     * @param Request $request La requête HTTP contenant les paramètres de filtrage
     * 
     * TODO:
     * - Récupérer le paramètre de niveau (level) depuis la requête
     * - Récupérer les paramètres de pagination (page, per_page)
     * - Filtrer les exercices par niveau si spécifié
     * - Paginer les résultats si demandé
     * - Joindre les informations de completion pour l'utilisateur connecté
     * - Retourner la réponse paginée avec les métadonnées (total, current_page, etc.)
     * - Gérer les cas d'erreur de paramètres invalides
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $result = $this->useCase->execute();
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
} 