<?php

namespace App\Http\Controllers\SportSession;

use App\Http\Controllers\Controller;
use App\UseCases\SportSession\SetSlotAvailabilityUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SetSlotAvailabilityAction extends Controller
{
    public function __construct(
        private SetSlotAvailabilityUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        try {
            $data = $request->validate([
                // Liste vide = aucun créneau ne convient
                'slotIds' => 'present|array',
                'slotIds.*' => 'required|string|uuid',
            ]);

            $session = $this->useCase->execute($id, $request->user()->id, $data['slotIds']);

            return response()->json([
                'success' => true,
                'data' => $session->toArray(),
                'message' => 'Disponibilités enregistrées',
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
