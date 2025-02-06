<?php

namespace App\Http\Controllers\Favorite;

use App\Http\Controllers\Controller;
use App\UseCases\Favorite\DeleteFavoriteUseCase;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class DeleteFavoriteAction extends Controller
{
    public function __construct(
        private DeleteFavoriteUseCase $useCase
    ) {}

    /**
     * Supprime un favori
     * 
     * @param string $exerciseId L'ID de l'exercice à supprimer
     * 
     * TODO:
     * - Vérifier si le favori existe
     * - Vérifier si l'utilisateur est le propriétaire du favori
     * - Supprimer l'enregistrement
     * - Retourner une réponse 204 No Content
     * - Gérer le cas où le favori n'existe pas (404)
     * - Gérer le cas où l'utilisateur n'est pas autorisé (403)
     */
    public function __invoke(string $exerciseId): Response
    {
        try {
            $this->useCase->execute($exerciseId);
            return response()->noContent();
        } catch (ValidationException $e) {
            if (isset($e->errors()['favorite'])) {
                return response('', 404);
            }
            return response('', 422);
        } catch (\Exception $e) {
            return response('', 500);
        }
    }
} 