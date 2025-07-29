<?php

namespace App\Http\Controllers\Example;

use App\Http\Controllers\Controller;
use App\UseCases\Example\FindAllExamplesUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Find All Examples Action - Template for creating new controllers
 *
 * This controller demonstrates the Single Action Controller pattern
 * with proper error handling and response formatting.
 */
class FindAllExamplesAction extends Controller
{
    public function __construct(
        private FindAllExamplesUseCase $useCase
    ) {}

    /**
     * Handle the incoming request
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            // Validate request parameters
            $validated = $request->validate([
                'active' => 'nullable|string|in:true,false,1,0',
                'name' => 'nullable|string|max:255',
            ]);

            // Convert string boolean to actual boolean
            if (isset($validated['active'])) {
                $validated['active'] = in_array($validated['active'], ['true', '1']);
            }

            // Execute the use case
            $result = $this->useCase->execute($validated);

            return response()->json($result, 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Données de validation invalides',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Error in FindAllExamplesAction: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all()
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de la récupération des exemples',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
