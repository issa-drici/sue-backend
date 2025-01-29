<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateUserAvatarAction extends Controller
{
    /**
     * Met à jour l'avatar de l'utilisateur
     * 
     * @param Request $request La requête HTTP contenant le fichier avatar
     * 
     * TODO:
     * - Valider le fichier avatar (image, max:2048kb, types:jpg,png)
     * - Récupérer l'utilisateur authentifié
     * - Stocker le nouveau fichier dans le système de fichiers
     * - Créer un enregistrement dans la table files
     * - Mettre à jour avatar_file_id dans user_profiles
     * - Supprimer l'ancien avatar si existant
     * - Retourner l'URL du nouvel avatar
     * - Gérer les erreurs de téléchargement
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Données temporaires de test
        return response()->json([
            'avatar_url' => 'https://example.com/new-avatar.jpg'
        ]);
    }
} 