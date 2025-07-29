<?php

namespace App\Http\Controllers\SportSession;

use App\Http\Controllers\Controller;
use App\UseCases\SportSession\DeleteSportSessionUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeleteSportSessionAction extends Controller
{
    public function __construct(
        private DeleteSportSessionUseCase $deleteSportSessionUseCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $success = $this->deleteSportSessionUseCase->execute($id, $userId);

            if (!$success) {
                throw new \Exception('Erreur lors de la suppression de la session');
            }

            return response()->json([
                'success' => true,
                'message' => 'Session supprimée avec succès',
            ]);

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
