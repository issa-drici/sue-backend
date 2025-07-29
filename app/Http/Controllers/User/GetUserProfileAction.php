<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\UseCases\User\GetUserProfileUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GetUserProfileAction extends Controller
{
    public function __construct(
        private GetUserProfileUseCase $getUserProfileUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $profile = $this->getUserProfileUseCase->execute($userId);

        if (!$profile) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'USER_NOT_FOUND',
                    'message' => 'Profil utilisateur non trouvé'
                ]
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $profile->toArray()
        ]);
    }
}
