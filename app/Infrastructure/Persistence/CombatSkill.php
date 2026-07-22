<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class CombatSkill extends Model
{
    use HasUlids;

    protected $table = 'combat_skills';

    protected $fillable = [
        'name',
        'description',
        'type', // active, passive
        'required_weapon_type',
        'effect_type', // poison, fire, buff_phys_dmg, direct_dmg
        'base_cooldown',
        'base_duration',
        'base_value',
        'scaling_value',
        'required_level',
        'unlock_cost',
        'icon',
    ];
}
