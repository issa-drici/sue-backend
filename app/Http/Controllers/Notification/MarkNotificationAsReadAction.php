<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\UseCases\Notification\MarkNotificationAsReadUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MarkNotificationAsReadAction extends Controller
{
    public function __construct(
        private MarkNotificationAsReadUseCase $markNotificationAsReadUseCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $notification = $this->markNotificationAsReadUseCase->execute($id, $userId);

            return response()->json([
                'success' => true,
                'data' => $notification->toArray(),
                'message' => 'Notification marquée comme lue',
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
