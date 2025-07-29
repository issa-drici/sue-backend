<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\UseCases\Notification\GetUnreadCountUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GetUnreadCountAction extends Controller
{
    public function __construct(
        private GetUnreadCountUseCase $getUnreadCountUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $count = $this->getUnreadCountUseCase->execute($userId);

            return response()->json([
                'success' => true,
                'data' => [
                    'unreadCount' => $count
                ],
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
