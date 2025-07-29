<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\UseCases\Notification\MarkAllNotificationsAsReadUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MarkAllNotificationsAsReadAction extends Controller
{
    public function __construct(
        private MarkAllNotificationsAsReadUseCase $markAllNotificationsAsReadUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $result = $this->markAllNotificationsAsReadUseCase->execute($userId);

            return response()->json([
                'success' => true,
                'data' => [
                    'updatedCount' => $result['updatedCount']
                ],
                'message' => 'Toutes les notifications ont été marquées comme lues',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }
}
