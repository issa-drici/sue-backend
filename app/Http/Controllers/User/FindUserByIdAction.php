<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class FindUserByIdAction extends Controller
{
    /**
     * Récupère les informations d'un utilisateur par son ID
     * 
     * @param string $userId L'ID de l'utilisateur à récupérer
     * 
     * TODO:
     * - Vérifier si l'utilisateur existe
     * - Récupérer les données de la table users
     * - Joindre les données de user_profiles
     * - Retourner une réponse JSON avec les données consolidées
     * - Gérer le cas où l'utilisateur n'existe pas (404)
     * - Vérifier les permissions (un utilisateur ne peut voir que son propre profil)
     */
    public function __invoke(string $userId): JsonResponse
    {
        // Données temporaires de test
        return response()->json([
            'id' => $userId,
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'profile' => [
                'total_xp' => 1000,
                'total_training_time' => 3600,
                'completed_videos' => 10
            ]
        ]);
    }
} 