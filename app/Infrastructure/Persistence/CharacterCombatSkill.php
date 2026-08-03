<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterCombatSkill extends Model
{
    use HasUlids;

    protected $table = 'character_combat_skills';

    protected $fillable = [
        'character_id',
        'combat_skill_id',
        'level',
        'is_equipped',
        'equip_slot',
    ];

    protected $casts = [
        'is_equipped' => 'boolean',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(CombatSkill::class, 'combat_skill_id');
    }

    public function getManaCost(): int
    {
        $baseCost = $this->skill ? $this->skill->getManaCost($this->level) : 0;
        if ($baseCost <= 0 || !$this->character) {
            return $baseCost;
        }

        // Pasywka Rodzaju Wspomagającego peta - obniża koszt many (patrz
        // Character::getEquipmentStats(), docs/modules/pets.md). Ograniczone do 90%,
        // czysto zabezpieczająco - realny bonus (fusion_count*tier) nigdy się tam nie zbliża.
        $reductionPct = min(90.0, (float) ($this->character->getEquipmentStats()['mana_cost_reduction_pct'] ?? 0));
        if ($reductionPct <= 0) {
            return $baseCost;
        }

        return max(0, (int) round($baseCost * (1 - $reductionPct / 100)));
    }
}
