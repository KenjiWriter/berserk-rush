<?php

namespace App\Application\Pets;

use App\Application\Shared\Result;
use App\Domain\Pets\PetFusionRules;
use App\Domain\Pets\PetStatCalculator;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CurrencyLedger;
use App\Infrastructure\Persistence\MarketListing;
use App\Infrastructure\Persistence\Pet;
use App\Infrastructure\RNG\RandomProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PetFusionService
{
    public function __construct(
        private RandomProvider $rng,
        private PetStatCalculator $statCalculator,
        private PetSpeciesPicker $speciesPicker,
    ) {
    }

    /**
     * Fuzja: dokładnie 2 pety tego samego tieru -> 1 pet o tier wyższy.
     * Koszt (złoto) pobierany zawsze, niezależnie od wyniku. Szansa sukcesu
     * rośnie z "dojrzałością" (growth_stage) obu petów - patrz
     * PetFusionRules::successChance(). Przy PORAŻCE konsekwencje dla obu
     * petów są losowane (gracz nie wybiera wariantu) wg
     * `config('pets.fusion_failure_outcomes')` - od utraty obu petów po
     * całkowity brak strat poza kosztem próby.
     */
    public function fusePets(Character $character, array $petIds): Result
    {
        return DB::transaction(function () use ($character, $petIds) {
            if (count($petIds) !== 2) {
                return Result::error('INVALID_COUNT', 'Do fuzji wymagane są dokładnie 2 pety.');
            }

            $pets = Pet::whereIn('id', $petIds)
                ->where('character_id', $character->id)
                ->where('is_equipped', false)
                ->lockForUpdate()
                ->get();

            if ($pets->count() !== 2) {
                return Result::error('INVALID_PETS', 'Wybierz 2 niezałożone pety należące do Twojej postaci.');
            }

            if (MarketListing::active()->whereIn('pet_id', $pets->pluck('id'))->exists()) {
                return Result::error('PET_LISTED', 'Nie można łączyć peta, który jest aktualnie wystawiony na Rynku.');
            }

            $tiers = $pets->pluck('tier')->unique();
            if ($tiers->count() !== 1) {
                return Result::error('TIER_MISMATCH', 'Oba wybrane pety muszą posiadać ten sam tier!');
            }

            $tier = (int) $tiers->first();
            if (!PetFusionRules::canFuse($tier)) {
                return Result::error('MAX_TIER', 'Pety najwyższego tieru (Legendarny) nie mogą być już dalej łączone!');
            }

            $cost = (int) config("pets.fusion_cost_gold.{$tier}", 0);
            if ($character->gold < $cost) {
                return Result::error('INSUFFICIENT_GOLD', "Potrzebujesz {$cost} złota, aby spróbować tej fuzji.");
            }

            // Koszt próby pobierany ZAWSZE, niezależnie od wyniku.
            if ($cost > 0) {
                $character->decrement('gold', $cost);

                CurrencyLedger::create([
                    'id' => Str::ulid(),
                    'idempotency_key' => 'pet_fusion_cost:' . $pets->first()->id . ':' . $pets->last()->id . ':' . Str::ulid(),
                    'character_id' => $character->id,
                    'currency_type' => 'gold',
                    'amount' => -$cost,
                    'balance_after' => $character->fresh()->gold,
                    'source_type' => 'pet_fusion_cost',
                    'description' => "Opłata za próbę fuzji chowańców (tier {$tier})",
                    'created_at' => now(),
                ]);
            }

            [$petA, $petB] = [$pets[0], $pets[1]];
            $chance = PetFusionRules::successChance($tier, $petA->growth_stage, $petB->growth_stage);
            $isSuccess = $this->rng->float(0, 100) <= $chance;

            if ($isSuccess) {
                return $this->handleSuccess($character, $petA, $petB, $tier, $chance, $cost);
            }

            return $this->handleFailure($petA, $petB, $tier, $chance, $cost);
        });
    }

    private function handleSuccess(Character $character, Pet $petA, Pet $petB, int $tier, float $chance, int $cost): Result
    {
        $resultTier = PetFusionRules::resultTier($tier);
        $fusionCount = max($petA->fusion_count, $petB->fusion_count) + 1;

        Pet::whereIn('id', [$petA->id, $petB->id])->delete();

        $statProfile = $this->statCalculator->rollStatProfile();
        [$name, $icon, $archetype] = $this->speciesPicker->pick($resultTier);
        $newPet = new Pet([
            'character_id' => $character->id,
            'name' => $name,
            'tier' => $resultTier,
            'archetype' => $archetype,
            'stat_profile' => $statProfile,
            'level' => 1,
            'exp' => 0,
            'growth_stage' => 0,
            'fusion_count' => $fusionCount,
            'is_equipped' => false,
            'icon' => $icon,
        ]);
        $newPet->recalculateStats();
        $newPet->save();

        return Result::ok([
            'success' => true,
            'outcome' => 'success',
            'pet' => $newPet,
            'message' => "Fuzja udana! Powstał chowaniec wyższego tieru: {$newPet->name}!",
            'tier' => $tier,
            'resultTier' => $resultTier,
            'chance' => $chance,
            'cost' => $cost,
        ]);
    }

    /**
     * Losuje konsekwencję nieudanej fuzji spośród 5 wariantów
     * (config('pets.fusion_failure_outcomes')) i ją wykonuje.
     */
    private function handleFailure(Pet $petA, Pet $petB, int $tier, float $chance, int $cost): Result
    {
        $outcome = $this->rollFailureOutcome();
        $luckyPet = $this->rng->int(0, 1) === 0 ? $petA : $petB;
        $unluckyPet = $luckyPet->id === $petA->id ? $petB : $petA;

        $message = match ($outcome) {
            'lose_both' => 'Fuzja nie powiodła się! Oba chowańce uległy rozproszeniu...',
            'lose_one' => "Fuzja nie powiodła się! Chowaniec {$unluckyPet->name} uległ rozproszeniu, ale {$luckyPet->name} przetrwał.",
            'devolve_both' => 'Fuzja nie powiodła się! Oba chowańce cofnęły się w rozwoju, ale przetrwały.',
            'devolve_one' => "Fuzja nie powiodła się! {$unluckyPet->name} cofnął się w rozwoju, ale oba chowańce przetrwały.",
            default => 'Fuzja nie powiodła się, ale Twoje chowańce wyszły z tego bez szwanku!',
        };

        switch ($outcome) {
            case 'lose_both':
                Pet::whereIn('id', [$petA->id, $petB->id])->delete();
                break;
            case 'lose_one':
                $unluckyPet->delete();
                break;
            case 'devolve_both':
                $petA->demoteGrowthStage();
                $petA->save();
                $petB->demoteGrowthStage();
                $petB->save();
                break;
            case 'devolve_one':
                $unluckyPet->demoteGrowthStage();
                $unluckyPet->save();
                break;
            case 'no_loss':
            default:
                break;
        }

        return Result::ok([
            'success' => false,
            'outcome' => $outcome,
            'message' => $message,
            'tier' => $tier,
            'chance' => $chance,
            'cost' => $cost,
        ]);
    }

    /**
     * Losuje wariant konsekwencji nieudanej fuzji wg wag z configu - gracz
     * NIE wybiera wariantu, to czysty rzut kością na podstawie
     * prawdopodobieństwa (patrz config('pets.fusion_failure_outcomes')).
     */
    private function rollFailureOutcome(): string
    {
        $distribution = config('pets.fusion_failure_outcomes', ['lose_both' => 100]);

        $roll = $this->rng->int(1, 100);
        $cumulative = 0;

        foreach ($distribution as $outcome => $chance) {
            $cumulative += $chance;
            if ($roll <= $cumulative) {
                return (string) $outcome;
            }
        }

        return (string) array_key_last($distribution);
    }
}
