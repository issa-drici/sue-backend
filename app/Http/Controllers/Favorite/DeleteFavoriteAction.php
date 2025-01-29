<?php

namespace App\Http\Controllers\Favorite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class DeleteFavoriteAction extends Controller
{
    /**
     * Supprime un favori
     * 
     * @param string $favoriteId L'ID du favori à supprimer
     * 
     * TODO:
     * - Vérifier si le favori existe
     * - Vérifier si l'utilisateur est le propriétaire du favori
     * - Supprimer l'enregistrement
     * - Retourner une réponse 204 No Content
     * - Gérer le cas où le favori n'existe pas (404)
     * - Gérer le cas où l'utilisateur n'est pas autorisé (403)
     */
    public function __invoke(string $favoriteId): Response
    {
        return response()->noContent();
    }
} 