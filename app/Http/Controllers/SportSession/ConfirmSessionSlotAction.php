<?php

namespace App\Http\Controllers\SportSession;

use App\Http\Controllers\Controller;
use App\UseCases\SportSession\ConfirmSessionSlotUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfirmSessionSlotAction extends Controller
{
    public function __construct(
        private ConfirmSessionSlotUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        try {
            $data = $request->validate([
                'slotId' => 'required|string|uuid',
            ]);

            $session = $this->useCase->execute($id, $request->user()->id, $data['slotId']);

            return response()->json([
                'success' => true,
                'data' => $session->toArray(),
                'message' => 'Créneau confirmé',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Données invalides',
                    'details' => $e->errors(),
                ],
            ], 400);

        } catch (\Exception $e) {
            [$statusCode, $errorCode] = match (true) {
                str_contains($e->getMessage(), 'Session non trouvée') => [404, 'SESSION_NOT_FOUND'],
                str_contains($e->getMessage(), 'pas invité'),
                str_contains($e->getMessage(), 'Seul l\'organisateur') => [403, 'FORBIDDEN'],
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
