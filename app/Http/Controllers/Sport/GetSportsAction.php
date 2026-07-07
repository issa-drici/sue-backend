<?php

namespace App\Http\Controllers\Sport;

use App\Http\Controllers\Controller;
use App\Services\SportService;
use Illuminate\Http\JsonResponse;

/**
 * Liste complète des sports supportés (source de vérité côté serveur).
 * Utilisée par l'app pour la liste déroulante "+" (tous les sports).
 */
class GetSportsAction extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => SportService::getSupportedSports(),
        ]);
    }
}
