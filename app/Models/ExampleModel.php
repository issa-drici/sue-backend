<?php

namespace App\Models;

use App\Entities\ExampleEntity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Example Model - Template for creating new Eloquent models
 *
 * This is an example model that demonstrates the proper structure
 * for Eloquent models in the Clean Architecture pattern.
 */
class ExampleModel extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'examples';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        // Add any fields you want to hide from JSON serialization
    ];

    protected $appends = [
        // Add any computed attributes you want to include in JSON
    ];

    // Scopes for common queries
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeByName($query, string $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    // Accessors and mutators
    public function getFormattedNameAttribute(): string
    {
        return ucfirst($this->name);
    }

    public function setDescriptionAttribute($value): void
    {
        $this->attributes['description'] = $value ? trim($value) : null;
    }

    // Transformation methods
    public function toEntity(): ExampleEntity
    {
        return new ExampleEntity(
            id: $this->id,
            name: $this->name,
            description: $this->description,
            isActive: $this->is_active,
            createdAt: $this->created_at,
            updatedAt: $this->updated_at
        );
    }

    public static function fromEntity(ExampleEntity $entity): self
    {
        return new self([
            'name' => $entity->getName(),
            'description' => $entity->getDescription(),
            'is_active' => $entity->isActive(),
        ]);
    }

    // Business logic methods
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }
}
