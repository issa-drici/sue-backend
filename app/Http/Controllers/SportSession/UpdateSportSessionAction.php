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
                'date' => 'sometimes|date_format:Y-m-d|after_or_equal:today',
                'time' => 'sometimes|date_format:H:i',
                'location' => 'sometimes|string|max:200',
            ]);

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
