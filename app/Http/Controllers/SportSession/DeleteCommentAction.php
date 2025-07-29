<?php

namespace App\Http\Controllers\SportSession;

use App\Http\Controllers\Controller;
use App\UseCases\SportSessionComment\DeleteCommentUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeleteCommentAction extends Controller
{
    public function __construct(
        private DeleteCommentUseCase $deleteCommentUseCase
    ) {}

    public function __invoke(Request $request, string $sessionId, string $commentId): JsonResponse
    {
        $userId = $request->user()->id;

        $result = $this->deleteCommentUseCase->execute($commentId, $userId);

        if (!$result['success']) {
            $errorCode = match ($result['error']['code']) {
                'VALIDATION_ERROR' => 400,
                'COMMENT_NOT_FOUND' => 404,
                'FORBIDDEN' => 403,
                'DELETE_FAILED' => 500,
                default => 500,
            };

            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], $errorCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }
}
