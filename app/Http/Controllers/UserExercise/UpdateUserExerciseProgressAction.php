<?php

namespace App\Http\Controllers\UserExercise;

use App\Http\Controllers\Controller;
use App\UseCases\UserExercise\UpdateUserExerciseProgressUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UpdateUserExerciseProgressAction extends Controller
{
    public function __construct(
        private UpdateUserExerciseProgressUseCase $useCase
    ) {}

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
        try {
            $watchTime = $request->input('watch_time', 0);
            $result = $this->useCase->execute($exerciseId, $watchTime);

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