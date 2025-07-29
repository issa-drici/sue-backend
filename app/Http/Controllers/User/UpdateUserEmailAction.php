<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\UseCases\User\UpdateUserEmailUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UpdateUserEmailAction extends Controller
{
    public function __construct(
        private UpdateUserEmailUseCase $updateUserEmailUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $newEmail = $request->input('newEmail');
        $currentEmail = $request->input('currentEmail');

        if (!$newEmail || !$currentEmail) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'newEmail et currentEmail sont requis'
                ]
            ], 400);
        }

        $result = $this->updateUserEmailUseCase->execute($userId, $newEmail, $currentEmail);

        if (!$result['success']) {
            $statusCode = match($result['error']['code']) {
                'EMAIL_ALREADY_EXISTS' => 409,
                default => 400
            };

            return response()->json($result, $statusCode);
        }

        return response()->json($result);
    }
}
