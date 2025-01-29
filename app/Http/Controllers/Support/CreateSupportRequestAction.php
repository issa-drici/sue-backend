<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreateSupportRequestAction extends Controller
{
    /**
     * Crée une nouvelle demande de support
     * 
     * @param Request $request La requête HTTP contenant les détails de la demande
     * 
     * TODO:
     * - Valider les paramètres :
     *   - subject (required, string, max:100)
     *   - message (required, string, max:1000)
     * - Récupérer l'utilisateur authentifié
     * - Créer un nouvel enregistrement dans support_requests
     * - Envoyer une notification par email à l'équipe de support
     * - Retourner la demande créée avec un statut 201
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Données temporaires de test
        return response()->json([
            'id' => 'support-1',
            'subject' => $request->input('subject'),
            'message' => $request->input('message'),
            'status' => 'pending',
            'created_at' => now()
        ], 201);
    }
} 