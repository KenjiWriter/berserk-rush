<?php

namespace App\Application\Pets;

use App\Application\Shared\Result;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CharacterIncubator;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\Pet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IncubatorService
{
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

            // Sprawdź czy inkubator jest wolny
            $incubator = CharacterIncubator::where('character_id', $character->id)->first();

            if ($incubator && !$incubator->is_hatched && $incubator->egg_item_instance_id) {
                return Result::error('INCUBATOR_BUSY', 'Inkubator jest już zajęty.');
            }

            $rarity = $egg->rarity ?? 'common';
            $hours = CharacterIncubator::getIncubationHours($rarity);

            if ($incubator) {
                $incubator->update([
                    'egg_item_instance_id' => $egg->id,
                    'egg_rarity' => $rarity,
                    'started_at' => now(),
                    'hatches_at' => now()->addHours($hours),
                    'is_hatched' => false,
                ]);
            } else {
                $incubator = CharacterIncubator::create([
                    'character_id' => $character->id,
                    'egg_item_instance_id' => $egg->id,
                    'egg_rarity' => $rarity,
                    'started_at' => now(),
                    'hatches_at' => now()->addHours($hours),
                    'is_hatched' => false,
                ]);
            }

            // Przenieś jajko z inventory
            $egg->update(['location' => 'incubator']);

            return Result::ok($incubator);
        });
    }

    /**
     * Wykluj peta z jajka.
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

            $rarity = $incubator->egg_rarity;
            $stats = $this->generatePetStats($rarity);
            $name = $this->generatePetName($rarity);

            $pet = Pet::create([
                'character_id' => $character->id,
                'name' => $name,
                'rarity' => $rarity,
                'stats' => $stats,
                'level' => 1,
                'exp' => 0,
                'is_equipped' => false,
                'icon' => $this->getRandomPetIcon($rarity),
            ]);

            // Usuń jajko z inkubatora
            if ($incubator->egg_item_instance_id) {
                $eggItem = ItemInstance::find($incubator->egg_item_instance_id);
                if ($eggItem) {
                    $eggItem->delete();
                }
            }

            $incubator->update([
                'is_hatched' => true,
                'egg_item_instance_id' => null,
            ]);

            return Result::ok($pet);
        });
    }

    /**
     * Załóż/zdejmij peta.
     */
    public function toggleEquipPet(Character $character, int $petId): Result
    {
        $pet = Pet::where('id', $petId)
            ->where('character_id', $character->id)
            ->first();

        if (!$pet) {
            return Result::error('NO_PET', 'Nie posiadasz tego peta.');
        }

        if ($pet->is_equipped) {
            $pet->update(['is_equipped' => false]);
            return Result::ok(['action' => 'unequipped', 'pet' => $pet]);
        }

        // Zdejmij aktualnego peta
        Pet::where('character_id', $character->id)
            ->where('is_equipped', true)
            ->update(['is_equipped' => false]);

        $pet->update(['is_equipped' => true]);

        return Result::ok(['action' => 'equipped', 'pet' => $pet]);
    }

    private function generatePetStats(string $rarity): array
    {
        $multiplier = match ($rarity) {
            'common' => 1,
            'uncommon' => 2,
            'rare' => 3,
            'epic' => 5,
            'legendary' => 8,
            default => 1,
        };

        return [
            'str' => mt_rand(1, 3) * $multiplier,
            'agi' => mt_rand(1, 3) * $multiplier,
            'int' => mt_rand(1, 3) * $multiplier,
            'vit' => mt_rand(1, 3) * $multiplier,
        ];
    }

    private function generatePetName(string $rarity): string
    {
        $prefixes = match ($rarity) {
            'legendary' => ['Złoty', 'Mistyczny', 'Starożytny', 'Boski'],
            'epic' => ['Mroczny', 'Ognisty', 'Lodowy', 'Błyskawiczny'],
            'rare' => ['Magiczny', 'Dziki', 'Zwinny', 'Nieustraszony'],
            'uncommon' => ['Mały', 'Szybki', 'Silny', 'Sprytny'],
            default => ['Przyjaciel', 'Towarzysz', 'Pomocnik', 'Stróż'],
        };

        $types = ['Smok', 'Feniks', 'Wilk', 'Orzeł', 'Niedźwiedź', 'Tygrys', 'Golem', 'Duch'];

        return $prefixes[array_rand($prefixes)] . ' ' . $types[array_rand($types)];
    }

    private function getRandomPetIcon(string $rarity): string
    {
        $icons = ['pet_dragon', 'pet_phoenix', 'pet_wolf', 'pet_eagle', 'pet_bear', 'pet_tiger', 'pet_golem', 'pet_spirit'];
        return $icons[array_rand($icons)];
    }
}
