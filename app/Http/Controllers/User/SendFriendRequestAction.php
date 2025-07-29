<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\UseCases\User\SendFriendRequestUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SendFriendRequestAction extends Controller
{
    public function __construct(
        private SendFriendRequestUseCase $sendFriendRequestUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $senderId = $request->user()->id;
        $receiverId = $request->input('userId');

        if (!$receiverId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'userId est requis'
                ]
            ], 400);
        }

        $result = $this->sendFriendRequestUseCase->execute($senderId, $receiverId);

        if (!$result['success']) {
            $statusCode = match($result['error']['code']) {
                'USER_NOT_FOUND' => 404,
                'FRIEND_REQUEST_EXISTS' => 409,
                default => 400
            };

            return response()->json($result, $statusCode);
        }

        return response()->json($result, 201);
    }
}
