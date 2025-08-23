<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\UseCases\User\FindUserByIdUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FindUserByIdAction extends Controller
{
    public function __construct(
        private FindUserByIdUseCase $findUserByIdUseCase
    ) {}

    /**
     * Récupère les informations d'un utilisateur par son ID
     *
     * @param string $userId L'ID de l'utilisateur à récupérer
     */
    public function __invoke(string $userId, Request $request): JsonResponse
    {
        $currentUserId = $request->user()->id;
        $userData = $this->findUserByIdUseCase->execute($userId, $currentUserId);

        if (!$userData) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $userData
        ]);
    }
}
