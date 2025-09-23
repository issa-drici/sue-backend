<?php

namespace App\Http\Controllers\PushToken;

use App\Http\Controllers\Controller;
use App\UseCases\PushToken\DeletePushTokenUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeletePushTokenAction extends Controller
{
    public function __construct(
        private DeletePushTokenUseCase $deletePushTokenUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'token' => 'required|string|max:255',
            ]);

            $userId = $request->user()->id;
            $result = $this->deletePushTokenUseCase->execute($userId, $data['token']);

            if ($result['success']) {
                return response()->json(['success' => true], 200);
            }

            $code = $result['error'] === 'TOKEN_NOT_FOUND_OR_ALREADY_DELETED' ? 404 : 500;

            return response()->json([
                'success' => false,
                'error' => $result['error']
            ], $code);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Données invalides',
                    'details' => $e->errors(),
                ],
            ], 400);
        }
    }
}


