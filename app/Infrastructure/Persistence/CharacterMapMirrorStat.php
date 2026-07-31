<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterMapMirrorStat extends Model
{
    protected $table = 'character_map_mirror_stats';

    protected $fillable = [
        'character_id',
        'map_id',
        'max_exp_per_minute',
        'max_gold_per_minute',
    ];

    protected $casts = [
        'max_exp_per_minute' => 'integer',
        'max_gold_per_minute' => 'integer',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function map(): BelongsTo
    {
        return $this->belongsTo(Map::class);
    }
}
