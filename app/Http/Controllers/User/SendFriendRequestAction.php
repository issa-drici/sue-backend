<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\UseCases\User\SendFriendRequestUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SendFriendRequestAction extends Controller
{
    public function __construct(
        private SendFriendRequestUseCase $sendFriendRequestUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $senderId = $request->user()->id;
            $receiverId = $request->input('userId');

            if (!$receiverId) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'userId est requis'
                    ]
                ], 400);
            }

            $result = $this->sendFriendRequestUseCase->execute($senderId, $receiverId);

            if (!$result['success']) {
                $statusCode = match($result['error']['code']) {
                    'USER_NOT_FOUND' => 404,
                    'FRIEND_REQUEST_EXISTS' => 409,
                    'DATABASE_ERROR' => 500,
                    'UNEXPECTED_ERROR' => 500,
                    default => 400
                };

                return response()->json($result, $statusCode);
            }

            return response()->json($result, 201);

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in SendFriendRequestAction', [
                'senderId' => $senderId ?? 'unknown',
                'receiverId' => $receiverId ?? 'unknown',
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'DATABASE_ERROR',
                    'message' => 'Erreur de base de données lors de l\'envoi de la demande d\'ami'
                ]
            ], 500);

        } catch (\Exception $e) {
            Log::error('Unexpected error in SendFriendRequestAction', [
                'senderId' => $senderId ?? 'unknown',
                'receiverId' => $receiverId ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Une erreur interne s\'est produite lors de l\'envoi de la demande d\'ami'
                ]
            ], 500);
        }
    }
}
