<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\UseCases\Profile\FindUserProfileUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FindUserProfileAction extends Controller
{
    public function __construct(
        private FindUserProfileUseCase $useCase
    ) {}

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