<?php

namespace App\Http\Controllers\SportSession;

use App\Http\Controllers\Controller;
use App\UseCases\SportSessionPresence\LeaveSessionUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveSessionAction extends Controller
{
    public function __construct(
        private LeaveSessionUseCase $leaveSessionUseCase
    ) {}

    public function __invoke(Request $request, string $sessionId): JsonResponse
    {
        $userId = $request->user()->id;

        $result = $this->leaveSessionUseCase->execute($sessionId, $userId);

        if (!$result['success']) {
            $errorCode = match ($result['error']['code']) {
                'VALIDATION_ERROR' => 400,
                'LEAVE_FAILED' => 500,
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
