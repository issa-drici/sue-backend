<?php

namespace App\Models;

use App\Entities\File;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileModel extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'files';
    
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'path',
        'url',
        'mime_type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function toEntity(): File
    {
        return new File(
            id: $this->id,
            userId: $this->user_id,
            path: $this->path,
            url: $this->url,
            mimeType: $this->mime_type,
        );
    }

    public static function fromEntity(File $file): self
    {
        return new self([
            'user_id' => $file->getUserId(),
            'path' => $file->getPath(),
            'url' => $file->getUrl(),
            'mime_type' => $file->getMimeType(),
        ]);
    }
} 