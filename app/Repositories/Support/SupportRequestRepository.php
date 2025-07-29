<?php

namespace App\Repositories\Support;

use App\Entities\SupportRequest;
use App\Models\SupportRequestModel;

/**
 * Support Request Repository Implementation
 */
class SupportRequestRepository implements SupportRequestRepositoryInterface
{
    public function findAllByUserId(string $userId): array
    {
        return SupportRequestModel::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($model) => $model->toEntity())
            ->toArray();
    }

    public function findById(string $id): ?SupportRequest
    {
        $model = SupportRequestModel::find($id);
        return $model ? $model->toEntity() : null;
    }

    public function create(array $data): SupportRequest
    {
        $model = SupportRequestModel::create($data);
        return $model->toEntity();
    }

    public function update(string $id, array $data): bool
    {
        $model = SupportRequestModel::find($id);

        if (!$model) {
            return false;
        }

        return $model->update($data);
    }

    public function delete(string $id): bool
    {
        $model = SupportRequestModel::find($id);

        if (!$model) {
            return false;
        }

        return $model->delete();
    }
}
