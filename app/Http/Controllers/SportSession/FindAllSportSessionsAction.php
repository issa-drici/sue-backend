<?php

namespace App\Http\Controllers\SportSession;

use App\Http\Controllers\Controller;
use App\UseCases\SportSession\FindAllSportSessionsUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FindAllSportSessionsAction extends Controller
{
    public function __construct(
        private FindAllSportSessionsUseCase $findAllSportSessionsUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['sport', 'date', 'organizer_id']);
            $page = (int) $request->get('page', 1);
            $limit = (int) $request->get('limit', 20);

            // Récupérer l'utilisateur connecté
            $userId = $request->user()->id;

            $paginator = $this->findAllSportSessionsUseCase->execute($filters, $page, $limit, $userId);

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
                    'message' => 'Erreur lors de la récupération des sessions',
                ],
            ], 500);
        }
    }
}
