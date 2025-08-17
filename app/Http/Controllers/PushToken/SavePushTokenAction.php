<?php

namespace App\Http\Controllers\PushToken;

use App\Http\Controllers\Controller;
use App\UseCases\PushToken\SavePushTokenUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SavePushTokenAction extends Controller
{
    public function __construct(
        private SavePushTokenUseCase $savePushTokenUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'token' => 'required|string|max:255',
                'platform' => 'nullable|string|in:expo,ios,android',
                'device_id' => 'nullable|string|max:255',
            ]);

            $userId = $request->user()->id;
            $result = $this->savePushTokenUseCase->execute($userId, $data['token'], $data['platform'] ?? 'expo', $data['device_id'] ?? null);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Token push enregistré avec succès',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'TOKEN_SAVE_ERROR',
                        'message' => $result['error'] ?? 'Erreur lors de l\'enregistrement du token',
                    ],
                ], 400);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Données invalides',
                    'details' => $e->errors(),
                ],
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }
}
