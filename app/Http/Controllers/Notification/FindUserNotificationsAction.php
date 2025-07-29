<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\UseCases\Notification\FindUserNotificationsUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FindUserNotificationsAction extends Controller
{
    public function __construct(
        private FindUserNotificationsUseCase $findUserNotificationsUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $page = (int) $request->get('page', 1);
            $limit = (int) $request->get('limit', 20);

            $paginator = $this->findUserNotificationsUseCase->execute($userId, $page, $limit);

            return response()->json([
                'success' => true,
                'data' => array_map(function ($item) {
                    return $item->toArray();
                }, $paginator->items()),
                'pagination' => [
                    'page' => $paginator->currentPage(),
                    'limit' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'totalPages' => $paginator->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Erreur lors de la récupération des notifications',
                ],
            ], 500);
        }
    }
}
