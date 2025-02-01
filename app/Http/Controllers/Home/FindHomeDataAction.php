<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\UseCases\Home\FindHomeDataUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FindHomeDataAction extends Controller
{
    public function __construct(
        private FindHomeDataUseCase $useCase
    ) {}

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
        try {
            $result = $this->useCase->execute();
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