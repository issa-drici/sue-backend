<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\UseCases\Profile\UpdateUserGoalsUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UpdateUserGoalsAction extends Controller
{
    public function __construct(
        private UpdateUserGoalsUseCase $useCase
    ) {}

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
        try {
            $validated = $request->validate([
                'current_goals' => 'nullable|string|max:500'
            ]);

            $result = $this->useCase->execute($validated['current_goals'] ?? null);

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
