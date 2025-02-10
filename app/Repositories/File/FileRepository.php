<?php

namespace App\Repositories\File;

use App\Entities\File;
use App\Models\FileModel;
use App\Repositories\File\FileRepositoryInterface;

class FileRepository implements FileRepositoryInterface
{
    public function create(File $file): File
    {
        $fileModel = FileModel::fromEntity($file);
        $fileModel->save();
        return $fileModel->toEntity();
    }

    public function findById(string $id): ?File
    {
        $fileModel = FileModel::find($id);
        return $fileModel ? $fileModel->toEntity() : null;
    }

    public function delete(string $id): void
    {
        FileModel::destroy($id);
    }
}