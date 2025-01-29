<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FindAllSupportRequestsAction extends Controller
{
    /**
     * Récupère toutes les demandes de support de l'utilisateur
     * 
     * @param Request $request La requête HTTP
     * 
     * TODO:
     * - Récupérer l'utilisateur authentifié
     * - Récupérer toutes ses demandes de support
     * - Paginer les résultats (10 par page)
     * - Trier par date de création décroissante
     * - Retourner la liste paginée avec métadonnées
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Données temporaires de test
        return response()->json([
            'data' => [
                [
                    'id' => 'support-1',
                    'subject' => 'Problème technique',
                    'message' => 'Je n\'arrive pas à accéder à la vidéo',
                    'status' => 'pending',
                    'created_at' => '2024-01-10T10:00:00Z'
                ],
                [
                    'id' => 'support-2',
                    'subject' => 'Question sur l\'exercice',
                    'message' => 'Je ne comprends pas l\'exercice 3',
                    'status' => 'resolved',
                    'created_at' => '2024-01-09T15:30:00Z'
                ]
            ],
            'meta' => [
                'current_page' => 1,
                'per_page' => 10,
                'total' => 2
            ]
        ]);
    }
} 