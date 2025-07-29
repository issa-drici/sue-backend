<?php

namespace App\Http\Controllers\SportSession;

use App\Http\Controllers\Controller;
use App\UseCases\SportSessionComment\CreateCommentUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreateCommentAction extends Controller
{
    public function __construct(
        private CreateCommentUseCase $createCommentUseCase
    ) {}

    public function __invoke(Request $request, string $sessionId): JsonResponse
    {
        $userId = $request->user()->id;
        $content = $request->input('content');
        $mentions = $request->input('mentions');

        $result = $this->createCommentUseCase->execute($sessionId, $userId, $content, $mentions);

        if (!$result['success']) {
            $errorCode = match ($result['error']['code']) {
                'VALIDATION_ERROR' => 400,
                'SESSION_NOT_FOUND' => 404,
                'NOT_PARTICIPANT' => 403,
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
        ], 201);
    }
}
