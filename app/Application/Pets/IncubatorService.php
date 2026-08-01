<?php

namespace App\Application\Pets;

use App\Application\Shared\Result;
use App\Domain\Pets\PetStatCalculator;
use App\Domain\Pets\PetTier;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CharacterIncubator;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\Pet;
use App\Infrastructure\RNG\RandomProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IncubatorService
{
    public function __construct(
        private RandomProvider $rng,
        private PetStatCalculator $statCalculator,
        private PetSpeciesPicker $speciesPicker,
    ) {
    }

    /**
     * Umieść jajko w inkubatorze.
     */
    public function placeEgg(Character $character, string $eggItemInstanceId): Result
    {
        return DB::transaction(function () use ($character, $eggItemInstanceId) {
            $egg = ItemInstance::where('id', $eggItemInstanceId)
                ->where('owner_character_id', $character->id)
                ->where('location', 'inventory')
                ->first();

            if (!$egg) {
                return Result::error('NO_EGG', 'Nie posiadasz tego jajka.');
            }

            $template = $egg->template;
            if (!$template || $template->type !== 'egg') {
                return Result::error('NOT_EGG', 'Ten przedmiot nie jest jajkiem.');
            }

            $tier = $egg->getEggTier();
            if (!$tier) {
                return Result::error('NO_EGG_TIER', 'To jajko nie ma przypisanego tieru chowańca.');
            }

            // Sprawdź czy inkubator jest wolny
            $incubator = CharacterIncubator::where('character_id', $character->id)->first();

            if ($incubator && !$incubator->is_hatched && $incubator->egg_item_instance_id) {
                return Result::error('INCUBATOR_BUSY', 'Inkubator jest już zajęty.');
            }

            $hours = PetTier::hatchHours($tier);

            // Handle stacked eggs: split off 1 egg for incubator if stack_size > 1
            if (($egg->stack_size ?? 1) > 1) {
                $egg->decrement('stack_size');
                $incubatorEgg = ItemInstance::create([
                    'id' => (string) Str::ulid(),
                    'owner_character_id' => $character->id,
                    'template_id' => $egg->template_id,
                    'location' => 'incubator',
                    'stack_size' => 1,
                    'rarity' => $egg->rarity,
                    'upgrade_level' => $egg->upgrade_level ?? 0,
                    'roll_stats' => $egg->roll_stats ?? [],
                ]);
                $targetEggId = $incubatorEgg->id;
            } else {
                $egg->update(['location' => 'incubator']);
                $targetEggId = $egg->id;
            }

            $data = [
                'egg_item_instance_id' => $targetEggId,
                'egg_tier' => $tier,
                'egg_rarity' => PetTier::slug($tier),
                'started_at' => now(),
                'hatches_at' => now()->addMinutes((int) round($hours * 60)),
                'is_hatched' => false,
            ];

            if ($incubator) {
                $incubator->update($data);
            } else {
                $incubator = CharacterIncubator::create($data + ['character_id' => $character->id]);
            }

            return Result::ok($incubator);
        });
    }

    /**
     * Wykluj peta z jajka. Wynikowy tier peta losowany jest z macierzy szans
     * `config('pets.hatch_matrix')` w zależności od tieru wyklutego jajka -
     * NIE jest to już proste kopiowanie tieru jajka 1:1.
     */
    public function hatchEgg(Character $character): Result
    {
        return DB::transaction(function () use ($character) {
            $incubator = CharacterIncubator::where('character_id', $character->id)->first();

            if (!$incubator) {
                return Result::error('NO_INCUBATOR', 'Nie masz inkubatora.');
            }

            if (!$incubator->isReady()) {
                return Result::error('NOT_READY', 'Jajko nie jest jeszcze gotowe.');
            }

            $eggTier = $incubator->egg_tier ?? 1;
            $eggItem = null;
            if ($incubator->egg_item_instance_id) {
                $eggItem = ItemInstance::with('template')->find($incubator->egg_item_instance_id);
                if ($eggItem && $eggItem->getEggTier()) {
                    $eggTier = $eggItem->getEggTier();
                }
            }

            $resultTier = $this->rollHatchTier($eggTier);
            $statProfile = $this->statCalculator->rollStatProfile();
            [$name, $icon] = $this->speciesPicker->pick($resultTier);

            $pet = new Pet([
                'character_id' => $character->id,
                'name' => $name,
                'tier' => $resultTier,
                'stat_profile' => $statProfile,
                'level' => 1,
                'exp' => 0,
                'growth_stage' => 0,
                'fusion_count' => 0,
                'is_equipped' => false,
                'icon' => $icon,
            ]);
            $pet->recalculateStats();
            $pet->save();

            // Usuń jajko z inkubatora
            if ($eggItem) {
                $eggItem->delete();
            }

            $incubator->update([
                'is_hatched' => true,
                'egg_item_instance_id' => null,
            ]);

            return Result::ok($pet);
        });
    }

    /**
     * Losuje wynikowy tier peta z macierzy szans dla danego tieru jajka.
     */
    private function rollHatchTier(int $eggTier): int
    {
        $distribution = config("pets.hatch_matrix.{$eggTier}", [$eggTier => 100]);

        $roll = $this->rng->int(1, 100);
        $cumulative = 0;

        foreach ($distribution as $tier => $chance) {
            $cumulative += $chance;
            if ($roll <= $cumulative) {
                return (int) $tier;
            }
        }

        // Fallback (zaokrąglenia w configu) - ostatni tier z rozkładu.
        return (int) array_key_last($distribution);
    }
}
