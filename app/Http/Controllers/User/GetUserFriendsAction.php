<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\UseCases\User\GetUserFriendsUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GetUserFriendsAction extends Controller
{
    public function __construct(
        private GetUserFriendsUseCase $getUserFriendsUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('limit', 20);

        $friends = $this->getUserFriendsUseCase->execute($userId, $page, $limit);

        return response()->json([
            'success' => true,
            'data' => $friends->items(),
            'pagination' => [
                'page' => $friends->currentPage(),
                'limit' => $friends->perPage(),
                'total' => $friends->total(),
                'totalPages' => $friends->lastPage()
            ]
        ]);
    }
}
