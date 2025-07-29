<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PushNotificationAction extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $userId = $request->input('userId');
        $notification = $request->input('notification');

        if (!$userId || !$notification) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'userId et notification sont requis'
                ]
            ], 400);
        }

        // Ici, vous pourriez implémenter la logique pour envoyer
        // une notification push (Firebase, Apple Push, etc.)
        // Pour l'instant, on simule juste le succès

        return response()->json([
            'success' => true,
            'data' => [
                'userId' => $userId,
                'notification' => $notification,
                'sent' => true
            ],
            'message' => 'Notification push envoyée'
        ], 201);
    }
}
