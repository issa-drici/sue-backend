<?php

namespace App\Http\Controllers\SportSession;

use App\Http\Controllers\Controller;
use App\UseCases\SportSessionPresence\UpdateTypingStatusUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateTypingStatusAction extends Controller
{
    public function __construct(
        private UpdateTypingStatusUseCase $updateTypingStatusUseCase
    ) {}

    public function __invoke(Request $request, string $sessionId): JsonResponse
    {
        $userId = $request->user()->id;
        $isTyping = $request->input('isTyping', false);

        $result = $this->updateTypingStatusUseCase->execute($sessionId, $userId, $isTyping);

        if (!$result['success']) {
            $errorCode = match ($result['error']['code']) {
                'VALIDATION_ERROR' => 400,
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
        ]);
    }
}
