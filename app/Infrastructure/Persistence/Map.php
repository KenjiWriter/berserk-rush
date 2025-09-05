<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class Map extends Model
{
    protected $fillable = [
        'name',
        'level_min',
        'level_max',
        'tier',
        'image_path',
    ];

    protected $casts = [
        'level_min' => 'integer',
        'level_max' => 'integer',
        'tier' => 'integer',
    ];

    public function isAccessibleBy($character): bool
    {
        return $character->level >= $this->level_min && $character->level <= $this->level_max;
    }

    public function getLevelRangeAttribute(): string
    {
        return "{$this->level_min}–{$this->level_max}";
    }

    public function monsters()
    {
        return $this->hasMany(Monster::class);
    }
}
