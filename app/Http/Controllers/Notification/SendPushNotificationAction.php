<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Repositories\PushToken\PushTokenRepositoryInterface;
use App\Services\ExpoPushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SendPushNotificationAction extends Controller
{
    public function __construct(
        private PushTokenRepositoryInterface $pushTokenRepository,
        private ExpoPushNotificationService $expoPushService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'recipientId' => 'required|string|uuid',
                'title' => 'required|string|max:255',
                'body' => 'required|string|max:1000',
                'data' => 'nullable|array'
            ]);

            $recipientId = $data['recipientId'];
            $title = $data['title'];
            $body = $data['body'];
            $data = $data['data'] ?? [];

            // Récupérer les tokens push de l'utilisateur
            $pushTokens = $this->pushTokenRepository->getTokensForUser($recipientId);

            if (empty($pushTokens)) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'NO_TOKENS_FOUND',
                        'message' => 'Aucun token push trouvé pour cet utilisateur'
                    ]
                ], 404);
            }

            // Envoyer la notification push
            $result = $this->expoPushService->sendNotification(
                $pushTokens,
                $title,
                $body,
                $data
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'recipientId' => $recipientId,
                        'tokensCount' => count($pushTokens),
                        'title' => $title,
                        'body' => $body,
                        'data' => $data,
                        'result' => $result
                    ],
                    'message' => 'Notification push envoyée avec succès'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'PUSH_SEND_ERROR',
                        'message' => 'Erreur lors de l\'envoi de la notification push',
                        'details' => $result
                    ]
                ], 500);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Données invalides',
                    'details' => $e->errors(),
                ],
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }
}
