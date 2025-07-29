<?php

namespace App\Http\Controllers\SportSession;

use App\Http\Controllers\Controller;
use App\UseCases\SportSession\FindSportSessionByIdUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FindSportSessionByIdAction extends Controller
{
    public function __construct(
        private FindSportSessionByIdUseCase $findSportSessionByIdUseCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        try {
            $session = $this->findSportSessionByIdUseCase->execute($id);

            return response()->json([
                'success' => true,
                'data' => $session->toArray(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SESSION_NOT_FOUND',
                    'message' => $e->getMessage(),
                ],
            ], 404);
        }
    }
}
