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

    public function isMagicSkill(): bool
    {
        if ($this->is_magic) {
            return true;
        }

        return in_array($this->required_weapon_type, ['wand', 'bell'], true);
    }

    public function getEffectiveWeaponType(Character $character): string
    {
        if (!empty($this->required_weapon_type) && $this->required_weapon_type !== 'all') {
            return $this->required_weapon_type;
        }

        if ($this->is_magic) {
            return 'wand';
        }

        return $character->getEquippedWeaponType();
    }

    /**
     * Zwraca zakres ataku bazowego [min, max, avg] pod symulację obrażeń tej konkretnej umiejętności
     * z uwzględnieniem prawidłowego przelicznika atrybutów i ataku broni (fizyczny vs magiczny).
     */
    public function getBaseDamageRange(Character $character): array
    {
        $weaponType = $this->getEffectiveWeaponType($character);
        $statBonus = $character->getAttributeAttackBonus($weaponType);
        $eqStats = $character->getEquipmentStats();
        $level = $character->level;

        if ($weaponType === 'wand') {
            $atkMin = (int) ($eqStats['magic_attack_min'] ?? 0);
            $atkMax = (int) max($atkMin, $eqStats['magic_attack_max'] ?? 0);
        } elseif ($weaponType === 'bell') {
            $atkMin = (int) (($eqStats['attack_min'] ?? 0) + ($eqStats['magic_attack_min'] ?? 0));
            $atkMax = (int) max($atkMin, ($eqStats['attack_max'] ?? 0) + ($eqStats['magic_attack_max'] ?? 0));
        } else {
            $atkMin = (int) ($eqStats['attack_min'] ?? 0);
            $atkMax = (int) max($atkMin, $eqStats['attack_max'] ?? 0);
        }

        $min = 10 + $statBonus + ($level * 1) + $atkMin;
        $max = 10 + $statBonus + ($level * 1) + $atkMax;

        return [
            'min' => $min,
            'max' => $max,
            'avg' => ($min + $max) / 2,
        ];
    }

    /**
     * Zwraca czytelny tekst opisujący główne statystyki wpływające na tę umiejętność.
     */
    public function getStatInfluenceText(Character $character): string
    {
        $weaponType = $this->getEffectiveWeaponType($character);

        return match ($weaponType) {
            'wand' => 'Inteligencja (INT) [+3 Atak/PKT], Poziom Postaci i Atak Magiczny Broni.',
            'bell' => 'Siła (STR) i Inteligencja (INT) [+1.5 Atak/PKT], Poziom Postaci i Broń.',
            'axe' => 'Siła (STR) [+3 Atak/PKT], Poziom Postaci i Broń.',
            'sword', 'bow', 'dagger' => 'Siła (STR) i Zręczność (AGI) [+1.5 Atak/PKT], Poziom Postaci i Broń.',
            default => 'Siła (STR) [+2 Atak/PKT], Poziom Postaci i Broń.',
        };
    }
}
