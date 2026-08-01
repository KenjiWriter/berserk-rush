<?php

namespace App\Application\Pets;

use App\Application\Shared\Result;
use App\Domain\Pets\PetFusionRules;
use App\Domain\Pets\PetStatCalculator;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\MarketListing;
use App\Infrastructure\Persistence\Pet;
use App\Infrastructure\RNG\RandomProvider;
use Illuminate\Support\Facades\DB;

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
     * Oba pety wejściowe są zawsze usuwane, niezależnie od wyniku. Szansa
     * sukcesu rośnie z "dojrzałością" (growth_stage) obu petów - patrz
     * PetFusionRules::successChance().
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

            [$petA, $petB] = [$pets[0], $pets[1]];
            $chance = PetFusionRules::successChance($tier, $petA->growth_stage, $petB->growth_stage);
            $isSuccess = $this->rng->float(0, 100) <= $chance;

            $resultTier = PetFusionRules::resultTier($tier);
            $fusionCount = max($petA->fusion_count, $petB->fusion_count) + 1;

            // Pety wejściowe są zużywane niezależnie od wyniku.
            Pet::whereIn('id', $pets->pluck('id'))->delete();

            if (!$isSuccess) {
                return Result::ok([
                    'success' => false,
                    'message' => 'Fuzja nie powiodła się! Oba chowańce uległy rozproszeniu...',
                    'tier' => $tier,
                    'chance' => $chance,
                ]);
            }

            $statProfile = $this->statCalculator->rollStatProfile();
            [$name, $icon] = $this->speciesPicker->pick($resultTier);
            $newPet = new Pet([
                'character_id' => $character->id,
                'name' => $name,
                'tier' => $resultTier,
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
                'pet' => $newPet,
                'message' => "Fuzja udana! Powstał chowaniec wyższego tieru: {$newPet->name}!",
                'tier' => $tier,
                'resultTier' => $resultTier,
                'chance' => $chance,
            ]);
        });
    }
}
