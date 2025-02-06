<?php

namespace App\Http\Controllers\Exercise;

use App\Http\Controllers\Controller;
use App\UseCases\Exercise\FindExerciseByIdAndUserIdUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class FindExerciseByIdAndUserIdAction extends Controller
{
    public function __construct(
        private FindExerciseByIdAndUserIdUseCase $useCase
    ) {}

    /**
     * Récupère les détails d'un exercice spécifique
     * 
     * @param string $exerciseId L'ID de l'exercice à récupérer
     * @param string $userId L'ID de l'utilisateur à récupérer
     * 
     * TODO:
     * - Vérifier si l'exercice existe
     * - Récupérer les données complètes de l'exercice
     * - Vérifier si l'utilisateur connecté a complété cet exercice
     * - Récupérer l'information de completed dans user_exercises (voir si un enregistrement existe pour le user et l'exercise avec completed)
     * - Récupérer l'information de si l'exercice est en favoris ou pas ? (voir si un enregistrement existe pour le user et l'exercise dans la table favorites)
     * - Retourner une réponse JSON avec toutes les informations
     * - Gérer le cas où l'exercice n'existe pas (404)
     */
    public function __invoke(string $exerciseId, string $userId): JsonResponse
    {
        try {
            $result = $this->useCase->execute($exerciseId, $userId);
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