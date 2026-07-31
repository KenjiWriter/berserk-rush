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
        'base_mana_cost',
        'scaling_mana_cost',
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
        'base_mana_cost' => 'integer',
        'scaling_mana_cost' => 'integer',
        'required_level' => 'integer',
        'unlock_cost' => 'integer',
    ];

    public function getManaCost(int $level = 1): int
    {
        $lvl = max(1, $level);
        return max(0, (int) round($this->base_mana_cost + (($lvl - 1) * $this->scaling_mana_cost)));
    }
}
