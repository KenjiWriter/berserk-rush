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
        'archetype',
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
        $this->growth_stage = PetGrowthStage::forLevel((int) $this->level);

        // Domyślne 0, gdyby peta stworzono (np. z przyszłego kodu) bez jawnego
        // ustawienia tych pól - patrz historia buga w GlobalChatComponent::handleGiveCommand().
        $fusionCount = (int) ($this->fusion_count ?? 0);

        $pool = PetStatCalculator::totalPool((int) $this->tier, (int) $this->level, $fusionCount);
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
     * statystyk/CP/pasywki jest proporcjonalnie zmniejszany
     * (min(1, poziom_postaci / poziom_peta)).
     */
    public function getPowerRatioFor(Character $character): float
    {
        return min(1.0, $character->level / max(1, $this->level));
    }

    /**
     * Tłumienie mocy: jeśli pet ma wyższy poziom niż postać, jego wkład do
     * statystyk postaci jest proporcjonalnie zmniejszany. Ekwipunek peta
     * (obroża/charm) NIE jest tłumiony - dolicza się w pełni.
     */
    public function getEffectiveStatsFor(Character $character): array
    {
        $ratio = $this->getPowerRatioFor($character);
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
     * Pasywka Rodzaju (Atakujący/Obrony/Wspomagający): % bonus = fusion_count
     * * tier, tłumiony tym samym mnożnikiem poziomu co reszta wkładu peta.
     * Pet, który nigdy nie przeszedł fuzji (fusion_count=0 - zawsze prawda
     * dla T1, bo fuzja zawsze podnosi tier), nie daje żadnej pasywki - patrz
     * docs/modules/pets.md.
     */
    public function getArchetypeBonusPercentFor(Character $character): float
    {
        if (!$this->archetype || $this->fusion_count <= 0) {
            return 0.0;
        }

        $raw = $this->fusion_count * (float) config('pets.fusion_count_archetype_bonus_percent', 1) * $this->tier;

        return $raw * $this->getPowerRatioFor($character);
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
