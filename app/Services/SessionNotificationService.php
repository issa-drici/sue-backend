<?php

namespace App\Services;

use App\Repositories\Notification\NotificationRepositoryInterface;
use App\Repositories\PushToken\PushTokenRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * Notification in-app + push pour un utilisateur, à propos d'une session.
 * Un échec du push ne fait jamais échouer l'action qui notifie.
 */
class SessionNotificationService
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
        private PushTokenRepositoryInterface $pushTokenRepository,
        private ExpoPushNotificationService $expoService
    ) {}

    public function notify(string $userId, string $sessionId, string $title, string $message, string $pushType = 'session_update'): void
    {
        $notification = $this->notificationRepository->create([
            'user_id' => $userId,
            'type' => 'update',
            'title' => $title,
            'message' => $message,
            'session_id' => $sessionId,
        ]);

        $this->push($userId, $notification->getId(), $title, $message, [
            'type' => $pushType,
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Push pour une notification in-app déjà créée (notification_id ajouté aux données).
     */
    public function push(string $userId, string $notificationId, string $title, string $message, array $data): void
    {
        try {
            $tokens = $this->pushTokenRepository->getTokensForUser($userId);
            if (!empty($tokens)) {
                $result = $this->expoService->sendNotification($tokens, $title, $message, $data + [
                    'notification_id' => $notificationId,
                ]);
                $this->notificationRepository->markAsPushSent($notificationId, $result);
            }
        } catch (\Throwable $e) {
            Log::error('Error sending session push', [
                'sessionId' => $data['session_id'] ?? null,
                'userId' => $userId,
                'pushType' => $data['type'] ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
