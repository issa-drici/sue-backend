<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\UseCases\Notification\DeleteNotificationUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeleteNotificationAction extends Controller
{
    public function __construct(
        private DeleteNotificationUseCase $deleteNotificationUseCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $success = $this->deleteNotificationUseCase->execute($id, $userId);

            if (!$success) {
                throw new \Exception('Erreur lors de la suppression de la notification');
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification supprimée',
            ]);

        } catch (\Exception $e) {
            $statusCode = 500;
            $errorCode = 'INTERNAL_ERROR';

            if (str_contains($e->getMessage(), 'Notification non trouvée')) {
                $statusCode = 404;
                $errorCode = 'NOTIFICATION_NOT_FOUND';
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
