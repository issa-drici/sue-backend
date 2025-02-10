<?php

namespace App\UseCases\File;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\UploadedFile;
use App\Entities\File;
use App\Repositories\File\FileRepositoryInterface;
use App\Services\S3Service;
use Illuminate\Validation\ValidationException;

class CreateFileUsecase
{
    public function __construct(
        private FileRepositoryInterface $fileRepository,
        private S3Service $s3Service
    ) {}

    public function execute(UploadedFile $file, string $path = 'uploads'): File
    {
        // 1. Auth
        $user = Auth::user();
        if (!$user) {
            throw ValidationException::withMessages([
                'user' => ['User not authenticated.']
            ]);
        }

        // 2. Upload sur S3
        $fileData = $this->s3Service->uploadFile($file, $path);

        // 3. Créer l'entité
        $fileEntity = new File(
            id: Str::uuid()->toString(),
            userId: $user->id,
            path: $fileData['path'],
            url: $fileData['url'],
            mimeType: $fileData['mime_type'],
        );

        // 4. Persister
        return $this->fileRepository->create($fileEntity);
    }
} 