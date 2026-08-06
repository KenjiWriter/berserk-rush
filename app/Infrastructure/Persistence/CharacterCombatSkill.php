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
        $baseCost = $this->skill ? $this->skill->getManaCost($this->getEffectiveLevel()) : 0;
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

    public function getTier(): string
    {
        if ($this->level >= 38) {
            return 'perfect';
        }
        if ($this->level >= 28) {
            return 'grand_master';
        }
        if ($this->level >= 18) {
            return 'master';
        }

        return 'normal';
    }

    public function getDisplayLevel(): string
    {
        $tier = $this->getTier();

        return match ($tier) {
            'perfect' => 'P',
            'grand_master' => 'G' . ($this->level - 27),
            'master' => 'M' . ($this->level - 17),
            default => 'Lv. ' . $this->level,
        };
    }

    public function getEffectiveLevel(): int
    {
        return min(38, max(1, $this->level));
    }

    public function getCooldown(): int
    {
        if (!$this->skill) {
            return 1;
        }

        $baseCd = max(1, $this->skill->base_cooldown);
        if ($baseCd <= 1) {
            return $baseCd;
        }

        $tierBonus = match ($this->getTier()) {
            'perfect' => 3,
            'grand_master' => 2,
            'master' => 1,
            default => 0,
        };

        // Minimalny limit CD to 2 tury, aby zapobiec używaniu skilli co turę
        return max(2, $baseCd - $tierBonus);
    }

    /**
     * Skille zadające bezpośrednie obrażenia (direct_dmg/aoe_dmg/freeze/stun) używają
     * tej wartości jako PEŁNEGO mnożnika obrażeń (`damage * bonus`, patrz
     * EncounterService/PvPEncounterService/DungeonService/LocationEventService) -
     * bez ograniczenia rosła liniowo z poziomem skilla aż do ~x31 na pełnej maestrii
     * (Perfect, lvl 38), co w PvP prowadziło do dosłownych one-shotów graczy
     * mających 5-10k HP na 99 poziomie (zgłoszenie: "Promień Zagłady" zadał 43.4k
     * jednym skillem). Heal/buff (procentowe) nie są objęte tym pułapem.
     */
    private const MAX_DAMAGE_SKILL_MULTIPLIER = 4.0;

    private const DAMAGE_EFFECT_TYPES = ['direct_dmg', 'direct', 'damage', 'aoe_dmg', 'freeze', 'stun'];

    public function getEffectiveValue(): float
    {
        if (!$this->skill) {
            return 0.0;
        }

        $effLvl = $this->getEffectiveLevel();
        $baseVal = (float) $this->skill->base_value;
        $scalingVal = (float) $this->skill->scaling_value;

        $tierMultiplier = match ($this->getTier()) {
            'perfect' => 1.65,
            'grand_master' => 1.35,
            'master' => 1.15,
            default => 1.0,
        };

        $value = ($baseVal + ($scalingVal * ($effLvl - 1))) * $tierMultiplier;

        if (in_array($this->skill->effect_type, self::DAMAGE_EFFECT_TYPES, true)) {
            $value = min($value, self::MAX_DAMAGE_SKILL_MULTIPLIER);
        }

        return $value;
    }
}
