<?php

namespace App\Http\Controllers\SportSession;

use App\Http\Controllers\Controller;
use App\UseCases\SportSessionPresence\GetOnlineUsersUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetOnlineUsersAction extends Controller
{
    public function __construct(
        private GetOnlineUsersUseCase $getOnlineUsersUseCase
    ) {}

    public function __invoke(Request $request, string $sessionId): JsonResponse
    {
        $page = (int) $request->query('page', 1);
        $limit = (int) $request->query('limit', 50);

        $result = $this->getOnlineUsersUseCase->execute($sessionId, $page, $limit);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'total' => $result['total'],
        ]);
    }
}
