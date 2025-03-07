<?php

namespace App\Http\Controllers\Exercise;

use App\Http\Controllers\Controller;
use App\UseCases\Exercise\FindAllExercisesByLevelIdAndUserIdUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FindAllExercisesByLevelIdAndUserIdAction extends Controller
{
    private FindAllExercisesByLevelIdAndUserIdUseCase $useCase;

    public function __construct(FindAllExercisesByLevelIdAndUserIdUseCase $useCase)
    {
        $this->useCase = $useCase;
    }

    public function __invoke(Request $request, string $levelId, string $userId): JsonResponse
    {
        $result = $this->useCase->execute($levelId, $userId);

        return response()->json([
            'status' => 'success',
            'data' => $result
        ]);
    }
}
