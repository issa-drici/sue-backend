<?php

namespace App\Http\Controllers\Level;

use App\Http\Controllers\Controller;
use App\UseCases\Level\FindAllLevelsUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FindAllLevelsAction extends Controller
{
    public function __construct(
        private FindAllLevelsUseCase $useCase
    ) {}

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
