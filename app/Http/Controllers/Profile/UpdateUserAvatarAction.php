<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\UseCases\Profile\UpdateUserAvatarUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UpdateUserAvatarAction extends Controller
{
    public function __construct(
        private UpdateUserAvatarUseCase $useCase
    ) {}

    /**
     * Met à jour l'avatar de l'utilisateur
     * 
     * @param Request $request La requête HTTP contenant le fichier avatar
     * 
     * TODO:
     * - Valider le fichier avatar (image, max:50mb, types:image/*)
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
        try {
            $request->validate([
                'avatar' => 'required|file|image|max:51200|mimes:jpeg,png,jpg,gif'
            ]);

            $result = $this->useCase->execute($request->file('avatar'));
            
            return response()->json($result);
            
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
} 