<?php

namespace App\UseCases\File;

use App\Entities\File;
use Illuminate\Support\Facades\Auth;
use App\Repositories\File\FileRepositoryInterface;
use Illuminate\Validation\ValidationException;

class FindFileByIdUsecase
{
    public function __construct(
        private FileRepositoryInterface $fileRepository
    ) {}

    public function execute(string $fileId): File
    {
        // 1. Auth
        $user = Auth::user();
        if (!$user) {
            throw ValidationException::withMessages([
                'user' => ['User not authenticated.']
            ]);
        }

        // 2. Retrouver le fichier
        $file = $this->fileRepository->findById($fileId);
        if (!$file) {
            throw ValidationException::withMessages([
                'file' => ['File not found.']
            ]);
        }

        // 3. Vérifier les permissions
        if ($file->getUserId() !== $user->id) {
            throw ValidationException::withMessages([
                'file' => ['You do not have permission to view this file.']
            ]);
        }

        return $file;
    }
} 