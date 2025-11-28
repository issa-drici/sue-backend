<?php

namespace App\Http\Controllers\SportSession;

use App\Http\Controllers\Controller;
use App\UseCases\SportSession\UpdateSportSessionUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UpdateSportSessionAction extends Controller
{
    public function __construct(
        private UpdateSportSessionUseCase $updateSportSessionUseCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        try {
            $data = $request->validate([
                'sport' => 'sometimes|in:' . \App\Services\SportService::getValidationRule(),
                'date' => 'sometimes|date_format:Y-m-d|after_or_equal:today',
                'startTime' => 'sometimes|date_format:H:i',
                'endTime' => 'sometimes|date_format:H:i',
                'location' => 'sometimes|string|max:200',
                'maxParticipants' => 'sometimes|nullable|integer|min:1|max:100',
                'pricePerPerson' => 'sometimes|nullable|numeric|min:0',
            ]);

            // Validation personnalisée pour s'assurer que endTime > startTime si les deux sont fournis
            if (isset($data['startTime']) && isset($data['endTime'])) {
                if (strtotime($data['endTime']) <= strtotime($data['startTime'])) {
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'VALIDATION_ERROR',
                            'message' => 'L\'heure de fin doit être après l\'heure de début',
                        ],
                    ], 400);
                }
            }

            $userId = $request->user()->id;
            $session = $this->updateSportSessionUseCase->execute($id, $data, $userId);

            return response()->json([
                'success' => true,
                'data' => $session->toArray(),
                'message' => 'Session mise à jour avec succès',
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
            $statusCode = 500;
            $errorCode = 'INTERNAL_ERROR';

            if (str_contains($e->getMessage(), 'Session non trouvée')) {
                $statusCode = 404;
                $errorCode = 'SESSION_NOT_FOUND';
            } elseif (str_contains($e->getMessage(), 'pas autorisé')) {
                $statusCode = 403;
                $errorCode = 'FORBIDDEN';
            }

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
