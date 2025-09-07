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

    public function isAccessibleBy(Character $character): bool
    {
        // Check if the map has min_level and max_level defined
        if (isset($this->min_level) && isset($this->max_level)) {
            // Check if character level is within range
            return $character->level >= $this->min_level &&
                $character->level <= $this->max_level;
        }

        // If no level limits defined, map is accessible by anyone
        return true;
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
