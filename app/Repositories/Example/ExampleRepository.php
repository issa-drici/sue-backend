<?php

namespace App\Repositories\Example;

use App\Entities\ExampleEntity;
use App\Models\ExampleModel;

/**
 * Example Repository Implementation - Template for creating new repositories
 *
 * This repository implements the ExampleRepositoryInterface and provides
 * data access logic for the Example entity.
 */
class ExampleRepository implements ExampleRepositoryInterface
{
    public function findAll(): array
    {
        return ExampleModel::all()
            ->map(fn($model) => $model->toEntity())
            ->toArray();
    }

    public function findAllActive(): array
    {
        return ExampleModel::active()
            ->get()
            ->map(fn($model) => $model->toEntity())
            ->toArray();
    }

    public function findById(string $id): ?ExampleEntity
    {
        $model = ExampleModel::find($id);
        return $model ? $model->toEntity() : null;
    }

    public function findByName(string $name): array
    {
        return ExampleModel::byName($name)
            ->get()
            ->map(fn($model) => $model->toEntity())
            ->toArray();
    }

    public function create(array $data): ExampleEntity
    {
        $model = ExampleModel::create($data);
        return $model->toEntity();
    }

    public function update(string $id, array $data): bool
    {
        $model = ExampleModel::find($id);

        if (!$model) {
            return false;
        }

        return $model->update($data);
    }

    public function delete(string $id): bool
    {
        $model = ExampleModel::find($id);

        if (!$model) {
            return false;
        }

        return $model->delete();
    }

    public function activate(string $id): bool
    {
        $model = ExampleModel::find($id);

        if (!$model) {
            return false;
        }

        $model->activate();
        return true;
    }

    public function deactivate(string $id): bool
    {
        $model = ExampleModel::find($id);

        if (!$model) {
            return false;
        }

        $model->deactivate();
        return true;
    }

    public function exists(string $id): bool
    {
        return ExampleModel::where('id', $id)->exists();
    }
}
