<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\FriendRequestModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class GetFriendRequestsCountAction extends Controller
{
    /**
     * Compter les demandes d'amis non traitées reçues par l'utilisateur connecté
     *
     * @return JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => 'Utilisateur non authentifié'
                    ]
                ], 401);
            }

            $userId = $user->id;

            // Compter uniquement les demandes d'amis reçues avec le statut 'pending'
            $count = FriendRequestModel::byReceiver($userId)
                ->pending()
                ->notCancelled()
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'count' => $count
                ]
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error getting friend requests count', [
                'userId' => Auth::user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_SERVER_ERROR',
                    'message' => 'Erreur interne du serveur'
                ]
            ], 500);
        }
    }
}
