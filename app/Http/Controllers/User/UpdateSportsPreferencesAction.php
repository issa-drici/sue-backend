<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\UseCases\User\UpdateSportsPreferencesUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UpdateSportsPreferencesAction extends Controller
{
    public function __construct(
        private UpdateSportsPreferencesUseCase $updateSportsPreferencesUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $sportsPreferences = $request->input('sports_preferences', []);

            // Validation de base
            if (!is_array($sportsPreferences)) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_DATA',
                        'message' => 'Les sports préférés doivent être un tableau'
                    ]
                ], 400);
            }

            $result = $this->updateSportsPreferencesUseCase->execute($userId, $sportsPreferences);

            if ($result === null) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Sports invalides ou utilisateur non trouvé'
                    ]
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Sports préférés mis à jour avec succès',
                'data' => [
                    'sports_preferences' => $result
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Erreur lors de la mise à jour des sports préférés',
                    'details' => config('app.debug') ? $e->getMessage() : null
                ]
            ], 500);
        }
    }
}
