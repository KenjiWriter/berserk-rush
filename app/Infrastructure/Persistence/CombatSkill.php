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
        // poison, fire, buff_phys_dmg, direct_dmg, aoe_dmg, heal, freeze, stun,
        // passive_aura_dmg, passive_extra_attack
        'effect_type',
        'is_magic', // szkoła magiczna (Różdżka/Dzwon) - dmg liczony/pokazywany jako magicDamage
        'is_aoe', // uderza wszystkich wrogów w starciach grupowych (over-level)
        'base_cooldown',
        'base_duration',
        'base_value',
        'scaling_value',
        'required_level',
        'unlock_cost',
        'icon',
    ];

    protected $casts = [
        'is_magic' => 'boolean',
        'is_aoe' => 'boolean',
        'base_cooldown' => 'integer',
        'base_duration' => 'integer',
        'base_value' => 'float',
        'scaling_value' => 'float',
        'required_level' => 'integer',
        'unlock_cost' => 'integer',
    ];
}
