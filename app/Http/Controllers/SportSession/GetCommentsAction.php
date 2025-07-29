<?php

namespace App\Http\Controllers\SportSession;

use App\Http\Controllers\Controller;
use App\UseCases\SportSessionComment\GetCommentsUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetCommentsAction extends Controller
{
    public function __construct(
        private GetCommentsUseCase $getCommentsUseCase
    ) {}

    public function __invoke(Request $request, string $sessionId): JsonResponse
    {
        $result = $this->getCommentsUseCase->execute($sessionId);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'pagination' => $result['pagination'],
        ]);
    }
}
