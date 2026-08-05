<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class LocationEventUpgradeLevel extends Model
{
    protected $fillable = [
        'level',
        'roll_chance_pct',
        'monster_count_delta_min',
        'monster_count_delta_max',
        'attack_multiplier',
        'hp_multiplier',
        'exp_multiplier',
        'drop_multiplier',
        'bonus_group_chance_pct',
        'chest_bonus_min',
        'chest_bonus_max',
    ];

    protected $casts = [
        'level' => 'integer',
        'roll_chance_pct' => 'integer',
        'monster_count_delta_min' => 'integer',
        'monster_count_delta_max' => 'integer',
        'attack_multiplier' => 'float',
        'hp_multiplier' => 'float',
        'exp_multiplier' => 'float',
        'drop_multiplier' => 'float',
        'bonus_group_chance_pct' => 'integer',
        'chest_bonus_min' => 'integer',
        'chest_bonus_max' => 'integer',
    ];
}
