<?php

namespace App\Domain\Pets;

use App\Infrastructure\RNG\RandomProvider;

/**
 * Wylicza "normę poziomową" peta - bazową pulę punktów statystyk na dany
 * poziom, przemnożoną przez normę tieru, bonus za licznik fuzji
 * (`fusion_count`, analogicznie do ulepszeń +0..+9 u Kowala: +10%/poziom) oraz
 * mnożnik etapu wzrostu (`PetGrowthStage::statMultiplier()` - realny "skok"
 * atrybutów przy przekroczeniu progu poziomowego etapu, nie tylko kosmetyka).
 * Pula jest następnie rozdzielana na str/agi/int/vit wg wag `stat_profile`
 * wylosowanych raz przy wykluciu/fuzji peta.
 */
class PetStatCalculator
{
    public const STATS = ['str', 'agi', 'int', 'vit'];

    public function __construct(private RandomProvider $rng)
    {
    }

    /**
     * Losuje profil wag (sumujących się do 1.0) używany do rozdziału puli staty.
     */
    public function rollStatProfile(): array
    {
        $raw = [];
        foreach (self::STATS as $stat) {
            $raw[$stat] = $this->rng->int(1, 100);
        }

        $sum = array_sum($raw);
        $profile = [];
        foreach ($raw as $stat => $value) {
            $profile[$stat] = round($value / $sum, 4);
        }

        return $profile;
    }

    public static function baseStatTotal(int $level): float
    {
        return $level * (float) config('pets.base_stat_per_level', 2.35);
    }

    public static function fusionMultiplier(int $fusionCount): float
    {
        return 1 + ($fusionCount * (float) config('pets.fusion_count_bonus_percent', 0) / 100);
    }

    public static function totalPool(int $tier, int $level, int $fusionCount): float
    {
        $stage = PetGrowthStage::forLevel($level);

        return self::baseStatTotal($level)
            * PetTier::levelNorm($tier)
            * self::fusionMultiplier($fusionCount)
            * PetGrowthStage::statMultiplier($stage);
    }

    /**
     * Rozdziela pulę na str/agi/int/vit wg wag z `$statProfile`, zaokrąglając
     * każdą wartość do pełnej liczby punktów.
     */
    public static function distribute(float $pool, ?array $statProfile): array
    {
        $stats = [];
        $evenWeight = 1 / count(self::STATS);

        foreach (self::STATS as $stat) {
            $weight = $statProfile[$stat] ?? $evenWeight;
            $stats[$stat] = (int) round($pool * $weight);
        }

        return $stats;
    }
}
