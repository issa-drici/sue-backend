<?php

namespace App\Http\Controllers\SportSession;

use App\Http\Controllers\Controller;
use App\UseCases\SportSessionComment\UpdateCommentUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateCommentAction extends Controller
{
    public function __construct(
        private UpdateCommentUseCase $updateCommentUseCase
    ) {}

    public function __invoke(Request $request, string $sessionId, string $commentId): JsonResponse
    {
        $userId = $request->user()->id;
        $content = $request->input('content');
        $mentions = $request->input('mentions');

        $result = $this->updateCommentUseCase->execute($commentId, $userId, $content, $mentions);

        if (!$result['success']) {
            $errorCode = match ($result['error']['code']) {
                'VALIDATION_ERROR' => 400,
                'COMMENT_NOT_FOUND' => 404,
                'FORBIDDEN' => 403,
                'UPDATE_FAILED' => 500,
                default => 500,
            };

            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], $errorCode);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'message' => $result['message'],
        ]);
    }
}
