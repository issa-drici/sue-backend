<?php

namespace App\Http\Controllers\SportSession;

use App\Http\Controllers\Controller;
use App\UseCases\SportSession\ProposeSessionSlotUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProposeSessionSlotAction extends Controller
{
    public function __construct(
        private ProposeSessionSlotUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        try {
            $data = $request->validate([
                'date' => 'required|date_format:Y-m-d',
                'startTime' => 'required|date_format:H:i',
                'endTime' => 'required|date_format:H:i',
            ]);

            $session = $this->useCase->execute($id, $request->user()->id, $data);

            return response()->json([
                'success' => true,
                'data' => $session->toArray(),
                'message' => 'Créneau proposé',
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
                str_contains($e->getMessage(), 'organisateur modifie') => [403, 'FORBIDDEN'],
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
