<?php

namespace App\Http\Controllers\SportSession;

use App\Http\Controllers\Controller;
use App\UseCases\SportSession\CancelSportSessionUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CancelSportSessionAction extends Controller
{
    public function __construct(
        private CancelSportSessionUseCase $cancelSportSessionUseCase
    ) {}

    public function __invoke(Request $request, string $sessionId): JsonResponse
    {
        $userId = $request->user()->id;

        $result = $this->cancelSportSessionUseCase->execute($sessionId, $userId);

        if (!$result['success']) {
            $errorCode = match ($result['error']['code']) {
                'SESSION_NOT_FOUND' => 404,
                'UNAUTHORIZED' => 403,
                'SESSION_ALREADY_CANCELLED', 'SESSION_ENDED' => 400,
                'UPDATE_FAILED' => 500,
                default => 500,
            };

            return response()->json([
                'success' => false,
                'message' => $result['error']['message'],
                'error' => $result['error']['code'],
            ], $errorCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data'],
        ]);
    }
}
