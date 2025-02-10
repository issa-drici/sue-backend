<?php


namespace App\Repositories\File;

use App\Entities\File;

interface FileRepositoryInterface
{
    public function create(File $file): File;
    public function findById(string $id): ?File;
    public function delete(string $id): void;
} 