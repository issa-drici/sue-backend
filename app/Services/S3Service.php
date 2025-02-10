<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class S3Service
{
    public function uploadFile(UploadedFile $file, string $path): array
    {
        $filename = uniqid() . '_' . $file->getClientOriginalName();
        $fullPath = $path . '/' . $filename;
        
        // Upload sur S3
        Storage::disk('s3')->put($fullPath, file_get_contents($file));
        
        // Générer l'URL publique
        $url = Storage::disk('s3')->url($fullPath);
        
        return [
            'path' => $fullPath,
            'url' => $url,
            'filename' => $filename,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize()
        ];
    }

    public function deleteFile(string $path): void
    {
        if (Storage::disk('s3')->exists($path)) {
            Storage::disk('s3')->delete($path);
        }
    }
} 