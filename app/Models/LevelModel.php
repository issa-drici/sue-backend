<?php

namespace App\Models;

use App\Entities\Level;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LevelModel extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'levels';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'category',
        'level_number',
        'description',
        'banner_url',
    ];

    public function exercises(): HasMany
    {
        return $this->hasMany(ExerciseModel::class, 'level_id');
    }

    public function toEntity(): Level
    {
        return new Level(
            id: $this->id,
            name: $this->name,
            category: $this->category,
            levelNumber: $this->level_number,
            description: $this->description,
            bannerUrl: $this->banner_url,
        );
    }

    public static function fromEntity(Level $level): self
    {
        return new self([
            'name' => $level->getName(),
            'category' => $level->getCategory(),
            'level_number' => $level->getLevelNumber(),
            'description' => $level->getDescription(),
            'banner_url' => $level->getBannerUrl(),
        ]);
    }
}
