<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\UseCases\User\CancelFriendRequestUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CancelFriendRequestAction extends Controller
{
    public function __construct(
        private CancelFriendRequestUseCase $cancelFriendRequestUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $currentUserId = $request->user()->id;
        $targetUserId = $request->input('target_user_id');

        if (!$targetUserId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MISSING_TARGET_USER_ID',
                    'message' => 'L\'ID de l\'utilisateur cible est requis'
                ]
            ], 400);
        }

        try {
            $result = $this->cancelFriendRequestUseCase->execute($currentUserId, $targetUserId);

            return response()->json([
                'success' => true,
                'data' => [
                    'requestId' => $result['requestId'],
                    'senderId' => $result['senderId'],
                    'receiverId' => $result['receiverId'],
                    'status' => 'cancelled',
                    'cancelledAt' => $result['cancelledAt']
                ],
                'message' => 'Demande d\'ami annulée avec succès'
            ], 200);

        } catch (\InvalidArgumentException $e) {
            $errorCode = 'FRIEND_REQUEST_NOT_FOUND';
            $statusCode = 404;

            // Si c'est une erreur de validation d'UUID
            if (str_contains($e->getMessage(), 'invalide')) {
                $errorCode = 'INVALID_USER_ID';
                $statusCode = 400;
            }

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $errorCode,
                    'message' => $e->getMessage()
                ]
            ], $statusCode);

        } catch (\Exception $e) {
            // Log l'erreur pour le debugging
            Log::error('Erreur lors de l\'annulation de demande d\'ami: ' . $e->getMessage(), [
                'senderId' => $currentUserId,
                'targetUserId' => $targetUserId,
                'trace' => $e->getTraceAsString()
            ]);

            if (str_contains($e->getMessage(), 'UNAUTHORIZED')) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'UNAUTHORIZED_CANCELLATION',
                        'message' => 'Vous ne pouvez annuler que vos propres demandes d\'ami'
                    ]
                ], 403);
            }

            if (str_contains($e->getMessage(), 'ALREADY_PROCESSED')) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'REQUEST_ALREADY_PROCESSED',
                        'message' => 'Cette demande d\'ami a déjà été acceptée ou refusée'
                    ]
                ], 409);
            }

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Une erreur interne s\'est produite: ' . $e->getMessage()
                ]
            ], 500);
        }
    }
}
