<?php

namespace App\Http\Controllers\Exercise;

use App\Http\Controllers\Controller;
use App\Repositories\Exercise\ExerciseRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FindExercisesByLevelIdAction extends Controller
{
    public function __construct(
        private ExerciseRepositoryInterface $exerciseRepository
    ) {}

    public function __invoke(Request $request, string $levelId): JsonResponse
    {
        try {
            $exercises = $this->exerciseRepository->findByLevelId($levelId);
            return response()->json([
                'exercises' => $exercises
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
