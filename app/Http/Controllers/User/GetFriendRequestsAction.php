<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\UseCases\User\GetFriendRequestsUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GetFriendRequestsAction extends Controller
{
    public function __construct(
        private GetFriendRequestsUseCase $getFriendRequestsUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('limit', 20);

        $requests = $this->getFriendRequestsUseCase->execute($userId, $page, $limit);

        return response()->json([
            'success' => true,
            'data' => $requests->items(),
            'pagination' => [
                'page' => $requests->currentPage(),
                'limit' => $requests->perPage(),
                'total' => $requests->total(),
                'totalPages' => $requests->lastPage()
            ]
        ]);
    }
}
