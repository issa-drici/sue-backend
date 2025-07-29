<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\UseCases\User\UpdateUserPasswordUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UpdateUserPasswordAction extends Controller
{
    public function __construct(
        private UpdateUserPasswordUseCase $updateUserPasswordUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $currentPassword = $request->input('currentPassword');
        $newPassword = $request->input('newPassword');

        if (!$currentPassword || !$newPassword) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'currentPassword et newPassword sont requis'
                ]
            ], 400);
        }

        $result = $this->updateUserPasswordUseCase->execute($userId, $currentPassword, $newPassword);

        if (!$result['success']) {
            $statusCode = match($result['error']['code']) {
                'INVALID_CURRENT_PASSWORD' => 400,
                default => 400
            };

            return response()->json($result, $statusCode);
        }

        return response()->json($result);
    }
}
