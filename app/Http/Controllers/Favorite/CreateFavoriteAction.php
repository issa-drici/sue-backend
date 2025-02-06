<?php

namespace App\Http\Controllers\Favorite;

use App\Http\Controllers\Controller;
use App\UseCases\Favorite\CreateFavoriteUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CreateFavoriteAction extends Controller
{
    public function __construct(
        private CreateFavoriteUseCase $useCase
    ) {}

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
        try {
            $exerciseId = $request->input('exercise_id');
            if (!$exerciseId) {
                throw ValidationException::withMessages([
                    'exercise_id' => ['Le champ exercise_id est obligatoire']
                ]);
            }

            $result = $this->useCase->execute($exerciseId);
            return response()->json($result, 201);
        } catch (ValidationException $e) {
            if (isset($e->errors()['favorite'])) {
                return response()->json([
                    'message' => 'Conflit',
                    'errors' => $e->errors()
                ], 409);
            }
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