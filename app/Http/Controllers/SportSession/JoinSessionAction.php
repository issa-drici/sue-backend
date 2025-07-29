<?php

namespace App\Http\Controllers\SportSession;

use App\Http\Controllers\Controller;
use App\UseCases\SportSessionPresence\JoinSessionUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JoinSessionAction extends Controller
{
    public function __construct(
        private JoinSessionUseCase $joinSessionUseCase
    ) {}

    public function __invoke(Request $request, string $sessionId): JsonResponse
    {
        $userId = $request->user()->id;

        $result = $this->joinSessionUseCase->execute($sessionId, $userId);

        if (!$result['success']) {
            $errorCode = match ($result['error']['code']) {
                'VALIDATION_ERROR' => 400,
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
        ]);
    }
}
