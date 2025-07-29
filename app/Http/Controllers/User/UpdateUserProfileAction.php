<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\UseCases\User\UpdateUserProfileUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UpdateUserProfileAction extends Controller
{
    public function __construct(
        private UpdateUserProfileUseCase $updateUserProfileUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $data = $request->only(['firstname', 'lastname', 'avatar']);

            $profile = $this->updateUserProfileUseCase->execute($userId, $data);

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Impossible de mettre à jour le profil'
                    ]
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $profile->toArray(),
                'message' => 'Profil mis à jour avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Erreur lors de la mise à jour du profil',
                    'details' => config('app.debug') ? $e->getMessage() : null
                ]
            ], 500);
        }
    }
}
