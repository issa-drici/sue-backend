<?php

namespace App\Http\Controllers\SportSession;

use App\Http\Controllers\Controller;
use App\UseCases\SportSession\CancelParticipationUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CancelParticipationAction extends Controller
{
    public function __construct(
        private CancelParticipationUseCase $cancelParticipationUseCase
    ) {}

    public function __invoke(Request $request, string $sessionId): JsonResponse
    {
        $userId = $request->user()->id;

        $result = $this->cancelParticipationUseCase->execute($sessionId, $userId);

        if (!$result['success']) {
            $errorCode = match ($result['error']['code']) {
                'VALIDATION_ERROR' => 400,
                'SESSION_NOT_FOUND' => 404,
                'UNAUTHORIZED' => 403,
                'USER_NOT_ACCEPTED' => 400,
                'SESSION_ENDED' => 409,
                'UPDATE_FAILED', 'SESSION_UPDATE_FAILED' => 500,
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
