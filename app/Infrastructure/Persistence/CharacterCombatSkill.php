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

    /**
     * Rebalans 2026-08-06 (zgłoszenie gracza): próg M1 obniżony z poziomu 18 na 7 -
     * przy 3 PKT/poziom postaci osiąganie starego progu (16 zakupów za same punkty)
     * wymagało budżetu niemożliwego do zebrania nawet do 99 poziomu (patrz
     * UpgradeSkill::execute() - etapy Mistrza/Arcymistrza bez zmian, tylko etap
     * podstawowy skrócony z 16 do 5 zakupów za PKT).
     */
    public function getTier(): string
    {
        if ($this->level >= 27) {
            return 'perfect';
        }
        if ($this->level >= 17) {
            return 'grand_master';
        }
        if ($this->level >= 7) {
            return 'master';
        }

        return 'normal';
    }

    public function getDisplayLevel(): string
    {
        $tier = $this->getTier();

        return match ($tier) {
            'perfect' => 'P',
            'grand_master' => 'G' . ($this->level - 16),
            'master' => 'M' . ($this->level - 6),
            default => 'Lv. ' . $this->level,
        };
    }

    public function getEffectiveLevel(): int
    {
        return min(27, max(1, $this->level));
    }

    /**
     * Rebalans 2026-08-06 (zgłoszenie gracza): poprzednia wersja (baseCd - tierBonus,
     * tierBonus stałe -1/-2/-3 niezależnie od bazowego CD) dawała bezsensowne skrajności -
     * szybkie skille (CD 1-2) stawały się spammowalne bez żadnej rekompensaty za rosnącą
     * moc z tierMultiplier (getEffectiveValue()), a długie/ultimate skille (CD 8-10) przez
     * całą fazę Normal/Master (levele 1-16, większość progresji) pozostawały praktycznie
     * bezużyteczne. Zastąpione 3 kategoriami szybkości wg base_cooldown (Normal/Lv.1) -
     * patrz docs/modules/skills.md pkt 1 - z krzywymi konwergującymi do okna 3-5 tur na
     * wysokim mistrzostwie (Arcymistrz/Perfect), zamiast płaskiego globalnego floora.
     */
    public function getCooldown(): int
    {
        if (!$this->skill) {
            return 1;
        }

        $baseCd = max(1, (int) $this->skill->base_cooldown);
        $tier = $this->getTier();

        if ($baseCd <= 2) {
            // Szybkie: CD ROŚNIE z mistrzostwem (floor 3-4 od Arcymistrza) - inaczej
            // skill stawałby się jednocześnie mocniejszy (tierMultiplier) i częstszy.
            return match ($tier) {
                'perfect', 'grand_master' => min($baseCd + 2, 4),
                'master' => min($baseCd + 1, 3),
                default => $baseCd,
            };
        }

        if ($baseCd <= 5) {
            // Średnie: łagodny spadek do floora 3 od Arcymistrza wzwyż.
            return match ($tier) {
                'perfect', 'grand_master' => 3,
                'master' => max(3, $baseCd - 1),
                default => $baseCd,
            };
        }

        // Długie: floor 5 od Arcymistrza wzwyż, BEZ dalszego skracania na Perfect -
        // nagrodą za Perfect jest +65% mocy (tierMultiplier), nie krótszy CD.
        return match ($tier) {
            'perfect', 'grand_master' => 5,
            'master' => max(6, $baseCd - 2),
            default => $baseCd,
        };
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
