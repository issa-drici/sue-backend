<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\UseCases\User\SearchUsersUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SearchUsersAction extends Controller
{
    public function __construct(
        private SearchUsersUseCase $searchUsersUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $currentUserId = $request->user()->id;
        $query = $request->get('q');
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('limit', 20);

        // Nettoyer et valider la requête
        $query = $this->cleanSearchQuery($query);

        if (empty($query)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Le paramètre q est requis et doit contenir au moins 2 caractères valides'
                ]
            ], 400);
        }

        $users = $this->searchUsersUseCase->execute($query, $currentUserId, $page, $limit);

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'pagination' => [
                'page' => $users->currentPage(),
                'limit' => $users->perPage(),
                'total' => $users->total(),
                'totalPages' => $users->lastPage()
            ]
        ]);
    }

    /**
     * Nettoie et valide une requête de recherche
     */
    private function cleanSearchQuery(?string $query): string
    {
        if (!$query) {
            return '';
        }

        // Supprimer les espaces en début et fin
        $query = trim($query);

        // Remplacer les espaces multiples par un seul espace
        $query = preg_replace('/\s+/', ' ', $query);

        // Supprimer les caractères spéciaux dangereux mais garder les caractères utiles
        $query = preg_replace('/[^\w\s@.-]/', '', $query);

        // Vérifier que la requête fait au moins 2 caractères après nettoyage
        if (strlen($query) < 2) {
            return '';
        }

        return $query;
    }
}
