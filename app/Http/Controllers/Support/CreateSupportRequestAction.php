<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\UseCases\Support\CreateSupportRequestUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreateSupportRequestAction extends Controller
{
    public function __construct(
        private CreateSupportRequestUseCase $useCase
    ) {}

    /**
     * Crée une nouvelle demande de support
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'message' => 'required|string|max:1000'
            ]);

            $result = $this->useCase->execute($validated);

            return response()->json($result, 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
