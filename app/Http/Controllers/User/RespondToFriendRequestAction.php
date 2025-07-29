<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\UseCases\User\RespondToFriendRequestUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RespondToFriendRequestAction extends Controller
{
    public function __construct(
        private RespondToFriendRequestUseCase $respondToFriendRequestUseCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $userId = $request->user()->id;
        $response = $request->input('response');

        if (!in_array($response, ['accept', 'decline'])) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'response doit être "accept" ou "decline"'
                ]
            ], 400);
        }

        $result = $this->respondToFriendRequestUseCase->execute($userId, $id, $response);

        if (!$result['success']) {
            $statusCode = match($result['error']['code']) {
                'USER_NOT_FOUND' => 404,
                default => 400
            };

            return response()->json($result, $statusCode);
        }

        return response()->json($result);
    }
}
