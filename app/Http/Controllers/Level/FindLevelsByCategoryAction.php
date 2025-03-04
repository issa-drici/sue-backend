<?php

namespace App\Http\Controllers\Level;

use App\Http\Controllers\Controller;
use App\UseCases\Level\FindLevelsByCategoryUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FindLevelsByCategoryAction extends Controller
{
    public function __construct(
        private FindLevelsByCategoryUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $category): JsonResponse
    {
        try {
            $result = $this->useCase->execute($category);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
