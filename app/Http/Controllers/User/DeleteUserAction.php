<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\UseCases\User\DeleteUserUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeleteUserAction extends Controller
{
    public function __construct(
        private DeleteUserUseCase $deleteUserUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $result = $this->deleteUserUseCase->execute($userId);

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
