<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\UseCases\User\RemoveFriendUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RemoveFriendAction extends Controller
{
    public function __construct(
        private RemoveFriendUseCase $removeFriendUseCase
    ) {}

    public function __invoke(Request $request, string $friendId): JsonResponse
    {
        $userId = $request->user()->id;

        $result = $this->removeFriendUseCase->execute($userId, $friendId);

        if (!$result['success']) {
            $statusCode = match ($result['error']['code']) {
                'FRIEND_NOT_FOUND' => 404,
                'FORBIDDEN' => 403,
                'INVALID_FRIEND_ID' => 400,
                default => 400
            };

            return response()->json($result, $statusCode);
        }

        return response()->json($result, 200);
    }
}
