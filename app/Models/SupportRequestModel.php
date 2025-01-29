<?php

namespace App\Models;

use App\Entities\SupportRequest;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportRequestModel extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'support_requests';
    
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'message',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function toEntity(): SupportRequest
    {
        return new SupportRequest(
            id: $this->id,
            userId: $this->user_id,
            message: $this->message,
        );
    }

    public static function fromEntity(SupportRequest $request): self
    {
        return new self([
            'user_id' => $request->getUserId(),
            'message' => $request->getMessage(),
        ]);
    }
} 