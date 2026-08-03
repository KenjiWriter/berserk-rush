<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyRankingEntry extends Model
{
    use HasUlids;

    protected $fillable = [
        'character_id',
        'week_start',
        'category',
        'score',
    ];

    protected $casts = [
        'week_start' => 'date:Y-m-d',
        'score'      => 'integer',
    ];

    // Dostępne kategorie
    public const CATEGORIES = [
        'monsters_killed',
        'dungeons_completed',
        'world_boss_damage',
        'levels_gained',
        'map_bosses_killed',
        'arena_wins',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
