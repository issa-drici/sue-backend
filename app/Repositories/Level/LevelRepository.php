<?php

namespace App\Repositories\Level;

use App\Entities\Level;
use App\Models\LevelModel;

class LevelRepository implements LevelRepositoryInterface
{
    public function findAll(): array
    {
        return LevelModel::orderBy('level_number')
            ->get()
            ->map(function ($level) {
                return $level->toEntity()->toArray();
            })
            ->toArray();
    }

    public function findById(string $id): ?Level
    {
        $model = LevelModel::find($id);
        return $model ? $model->toEntity() : null;
    }

    public function findByCategory(string $category): array
    {
        return LevelModel::where('category', $category)
            ->orderBy('level_number')
            ->get()
            ->map(function ($level) {
                return $level->toEntity()->toArray();
            })
            ->toArray();
    }

    public function create(array $data): Level
    {
        $level = Level::fromArray($data);
        $model = LevelModel::fromEntity($level);
        $model->save();

        return $model->toEntity();
    }

    public function update(string $id, array $data): ?Level
    {
        $model = LevelModel::find($id);
        if (!$model) {
            return null;
        }

        $model->fill($data);
        $model->save();

        return $model->toEntity();
    }

    public function delete(string $id): bool
    {
        $model = LevelModel::find($id);
        if (!$model) {
            return false;
        }

        return $model->delete();
    }
}
