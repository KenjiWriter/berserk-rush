<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Pets\PetGrowthStage;
use App\Domain\Pets\PetLevelCurve;
use App\Domain\Pets\PetStatCalculator;
use App\Domain\Pets\PetTier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pet extends Model
{
    protected $fillable = [
        'character_id',
        'name',
        'rarity',
        'tier',
        'stats',
        'stat_profile',
        'level',
        'exp',
        'growth_stage',
        'fusion_count',
        'is_equipped',
        'icon',
        'collar_item_instance_id',
        'charm_item_instance_id',
    ];

    protected $casts = [
        'stats' => 'array',
        'stat_profile' => 'array',
        'is_equipped' => 'boolean',
        'tier' => 'integer',
        'growth_stage' => 'integer',
        'fusion_count' => 'integer',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function collarItem(): BelongsTo
    {
        return $this->belongsTo(ItemInstance::class, 'collar_item_instance_id');
    }

    public function charmItem(): BelongsTo
    {
        return $this->belongsTo(ItemInstance::class, 'charm_item_instance_id');
    }

    public function tierName(): string
    {
        return PetTier::name($this->tier);
    }

    public function tierSlug(): string
    {
        return PetTier::slug($this->tier);
    }

    public function growthStageLabel(): string
    {
        return PetGrowthStage::label($this->growth_stage);
    }

    public function spriteVariant(): string
    {
        return PetGrowthStage::spriteVariant($this->growth_stage);
    }

    /**
     * Przelicza `growth_stage` z aktualnego poziomu oraz `stats` z normy
     * poziomowej tieru (patrz PetStatCalculator) i zapisuje wynik. Jedyne
     * miejsce, które powinno modyfikować `stats`/`growth_stage` - wołane po
     * karmieniu, fuzji oraz wykluciu.
     */
    public function recalculateStats(): void
    {
        $this->growth_stage = PetGrowthStage::forLevel($this->level);

        $pool = PetStatCalculator::totalPool($this->tier, $this->level, $this->fusion_count);
        $this->stats = PetStatCalculator::distribute($pool, $this->stat_profile);
    }

    /**
     * Bonus płaski z ekwipunku peta (obroża + charm), NIE tłumiony
     * mnożnikiem poziomu - to sprzęt, nie "moc" samego peta.
     */
    public function getGearBonusStats(): array
    {
        $bonus = ['str' => 0, 'agi' => 0, 'int' => 0, 'vit' => 0];

        foreach ([$this->collarItem, $this->charmItem] as $gear) {
            if (!$gear || !$gear->template) {
                continue;
            }

            $gearStats = $gear->template->base_stats ?? [];
            foreach (['str', 'agi', 'int', 'vit'] as $stat) {
                $bonus[$stat] += (int) ($gearStats[$stat . '_bonus'] ?? 0);
            }
        }

        return $bonus;
    }

    /**
     * Tłumienie mocy: jeśli pet ma wyższy poziom niż postać, jego wkład do
     * statystyk postaci jest proporcjonalnie zmniejszany
     * (min(1, poziom_postaci / poziom_peta)). Ekwipunek peta (obroża/charm)
     * NIE jest tłumiony - dolicza się w pełni.
     */
    public function getEffectiveStatsFor(Character $character): array
    {
        $ratio = min(1.0, $character->level / max(1, $this->level));
        $gearBonus = $this->getGearBonusStats();

        $effective = [];
        foreach (['str', 'agi', 'int', 'vit'] as $stat) {
            $base = $this->stats[$stat] ?? 0;
            $effective[$stat] = (int) round($base * $ratio) + $gearBonus[$stat];
        }

        return $effective;
    }

    public function getCombatPowerFor(Character $character): int
    {
        $stats = $this->getEffectiveStatsFor($character);
        return array_sum($stats) * max(1, $this->level);
    }

    /**
     * Wymagany EXP na następny poziom (rośnie z `fusion_count`).
     */
    public function getRequiredExp(): int
    {
        return PetLevelCurve::requiredExp($this->level, $this->fusion_count);
    }

    /**
     * Procentowy postęp EXP do następnego poziomu.
     */
    public function getExpProgressPercent(): float
    {
        $req = $this->getRequiredExp();
        if ($req <= 0) {
            return 0.0;
        }
        return min(100.0, round(($this->exp / $req) * 100, 1));
    }
}
