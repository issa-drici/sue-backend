<?php

namespace App\Http\Controllers\SportSession;

use App\Http\Controllers\Controller;
use App\UseCases\SportSession\FindMyHistoryUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FindMyHistoryAction extends Controller
{
    public function __construct(
        private FindMyHistoryUseCase $findMyHistoryUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $filters = $request->only(['sport']);
            $page = (int) $request->get('page', 1);
            $limit = (int) $request->get('limit', 20);

            $paginator = $this->findMyHistoryUseCase->execute($user->id, $filters, $page, $limit);

            return response()->json([
                'success' => true,
                'data' => array_map(fn($session) => $session->toArray(), $paginator->items()),
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
                    'message' => 'Erreur lors de la récupération de l\'historique',
                ],
            ], 500);
        }
    }
}
