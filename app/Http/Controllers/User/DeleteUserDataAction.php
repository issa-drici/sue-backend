<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\UseCases\User\DeleteUserDataUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeleteUserDataAction extends Controller
{
    private DeleteUserDataUseCase $deleteUserDataUseCase;

    public function __construct(DeleteUserDataUseCase $deleteUserDataUseCase)
    {
        $this->deleteUserDataUseCase = $deleteUserDataUseCase;
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $result = $this->deleteUserDataUseCase->execute($request->user()->id);

            if (!$result['success']) {
                return response()->json(['message' => $result['message']], 400);
            }

            return response()->json(['message' => $result['message']], 200);
        } catch (\Exception $e) {
            return response()->json(
                ['message' => 'Une erreur est survenue lors de la suppression des données'],
                500
            );
        }
    }
}