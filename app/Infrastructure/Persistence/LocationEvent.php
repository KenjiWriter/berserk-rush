<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class LocationEvent extends Model
{
    protected $fillable = [
        'rank',
        'name',
        'spawn_chance_pct',
        'monster_count_min',
        'monster_count_max',
        'attack_multiplier',
        'reward_multiplier',
        'group_chance_pct',
        'group_max_size',
        'chest_min',
        'chest_max',
    ];

    protected $casts = [
        'rank' => 'integer',
        'spawn_chance_pct' => 'integer',
        'monster_count_min' => 'integer',
        'monster_count_max' => 'integer',
        'attack_multiplier' => 'float',
        'reward_multiplier' => 'float',
        'group_chance_pct' => 'integer',
        'group_max_size' => 'integer',
        'chest_min' => 'integer',
        'chest_max' => 'integer',
    ];
}
