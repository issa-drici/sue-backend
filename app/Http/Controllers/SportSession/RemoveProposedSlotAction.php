<?php

namespace App\Http\Controllers\SportSession;

use App\Http\Controllers\Controller;
use App\UseCases\SportSession\RemoveProposedSlotUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RemoveProposedSlotAction extends Controller
{
    public function __construct(
        private RemoveProposedSlotUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $id, string $slotId): JsonResponse
    {
        try {
            $session = $this->useCase->execute($id, $request->user()->id, $slotId);

            return response()->json([
                'success' => true,
                'data' => $session->toArray(),
                'message' => 'Créneau retiré',
            ]);

        } catch (\Exception $e) {
            [$statusCode, $errorCode] = match (true) {
                str_contains($e->getMessage(), 'Session non trouvée'),
                str_contains($e->getMessage(), 'Créneau introuvable') => [404, 'NOT_FOUND'],
                str_contains($e->getMessage(), 'que les créneaux que tu as proposés') => [403, 'FORBIDDEN'],
                default => [400, 'INVALID_REQUEST'],
            };

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $errorCode,
                    'message' => $e->getMessage(),
                ],
            ], $statusCode);
        }
    }
}
