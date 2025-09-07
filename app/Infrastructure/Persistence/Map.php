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
        // Check if the map has level_min and level_max defined
        if (!is_null($this->level_min) && !is_null($this->level_max)) {
            // Check if character level is within range
            return $character->level >= $this->level_min &&
                $character->level <= $this->level_max;
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
