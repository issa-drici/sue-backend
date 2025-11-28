<?php

namespace App\Repositories\Notification;

use App\Entities\Notification;
use App\Models\NotificationModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function findById(string $id): ?Notification
    {
        $model = NotificationModel::find($id);

        if (!$model) {
            return null;
        }

        return $this->mapToEntity($model);
    }

    public function findByUser(string $userId, int $page = 1, int $limit = 20): LengthAwarePaginator
    {
        $paginator = NotificationModel::byUser($userId)
            ->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        $paginator->getCollection()->transform(function ($model) {
            return $this->mapToEntity($model);
        });

        return $paginator;
    }

    public function create(array $data): Notification
    {
        $model = NotificationModel::create([
            'id' => Str::uuid(),
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'title' => $data['title'],
            'message' => $data['message'],
            'session_id' => $data['session_id'] ?? null,
            'read' => false,
        ]);

        return $this->mapToEntity($model);
    }

    public function markAsRead(string $id): bool
    {
        $model = NotificationModel::find($id);

        if (!$model) {
            return false;
        }

        return $model->update(['read' => true]);
    }

    public function markAllAsRead(string $userId): bool
    {
        return NotificationModel::byUser($userId)
            ->unread()
            ->update(['read' => true]) > 0;
    }

    public function delete(string $id): bool
    {
        $model = NotificationModel::find($id);

        if (!$model) {
            return false;
        }

        return $model->delete();
    }

    public function getUnreadCount(string $userId): int
    {
        return NotificationModel::byUser($userId)->unread()->count();
    }

    public function findByType(string $userId, string $type): array
    {
        return NotificationModel::byUser($userId)
            ->byType($type)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($model) {
                return $this->mapToEntity($model);
            })
            ->toArray();
    }

    public function findBySession(string $sessionId): array
    {
        return NotificationModel::where('session_id', $sessionId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($model) {
                return $this->mapToEntity($model);
            })
            ->toArray();
    }

    /**
     * Vérifie si une notification d'invitation existe déjà pour un utilisateur et une session
     */
    public function hasInvitationNotification(string $userId, string $sessionId): bool
    {
        return NotificationModel::where('user_id', $userId)
            ->where('session_id', $sessionId)
            ->where('type', 'invitation')
            ->exists();
    }

    public function markAsPushSent(string $id, array $pushData = []): bool
    {
        $model = NotificationModel::find($id);

        if (!$model) {
            return false;
        }

        return $model->update([
            'push_sent' => true,
            'push_sent_at' => now(),
            'push_data' => $pushData
        ]);
    }

    private function mapToEntity(NotificationModel $model): Notification
    {
        return new Notification(
            $model->id,
            $model->user_id,
            $model->type,
            $model->title,
            $model->message,
            $model->session_id,
            $model->created_at,
            $model->read,
            $model->push_sent ?? false,
            $model->push_sent_at,
            $model->push_data
        );
    }
}
