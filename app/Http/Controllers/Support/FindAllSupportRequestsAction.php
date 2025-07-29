<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\UseCases\Support\FindAllSupportRequestsUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FindAllSupportRequestsAction extends Controller
{
    public function __construct(
        private FindAllSupportRequestsUseCase $useCase
    ) {}

    /**
     * Récupère toutes les demandes de support de l'utilisateur
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $result = $this->useCase->execute();

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
