<?php

namespace App\Application\Pets;

use App\Application\Shared\Result;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\Pet;
use Illuminate\Support\Facades\DB;

class PetService
{
    /**
     * Oblicza punkty doświadczenia (EXP) przyznawane przez dany przedmiot dla peta.
     */
    public function calculateItemExp(ItemInstance $item): int
    {
        $baseExp = max(1, $item->template->level_requirement ?? 1);
        $rarityMultiplier = match ($item->rarity ?? 'common') {
            'legendary' => 3.0,
            'epic' => 2.0,
            'rare' => 1.5,
            'uncommon' => 1.25,
            default => 1.0,
        };

        return (int) round($baseExp * $rarityMultiplier);
    }

    /**
     * Karmienie peta przedmiotami z plecaka.
     */
    public function feedPet(Character $character, int $petId, array $itemInstanceIds): Result
    {
        return DB::transaction(function () use ($character, $petId, $itemInstanceIds) {
            $pet = Pet::where('id', $petId)->where('character_id', $character->id)->first();
            if (!$pet) {
                return Result::error('NO_PET', 'Nie znaleziono wskazanego chowańca.');
            }

            if (empty($itemInstanceIds)) {
                return Result::error('NO_ITEMS', 'Wybierz co najmniej jeden przedmiot z plecaka.');
            }

            $items = ItemInstance::with('template')
                ->whereIn('id', $itemInstanceIds)
                ->where('owner_character_id', $character->id)
                ->where('location', 'inventory')
                ->get();

            if ($items->isEmpty()) {
                return Result::error('NO_ITEMS_FOUND', 'Nie znaleziono wybranych przedmiotów w Twoim plecaku.');
            }

            $totalGainedExp = 0;
            $consumedCount = 0;

            foreach ($items as $item) {
                $expVal = $this->calculateItemExp($item);

                if (($item->stack_size ?? 1) > 1) {
                    $item->decrement('stack_size');
                } else {
                    $item->delete();
                }

                $totalGainedExp += $expVal;
                $consumedCount++;
            }

            $currentLevel = $pet->level;
            $currentExp = $pet->exp + $totalGainedExp;

            while (true) {
                $requiredExp = $currentLevel * 100;
                if ($currentExp >= $requiredExp) {
                    $currentExp -= $requiredExp;
                    $currentLevel++;
                } else {
                    break;
                }
            }

            $oldLevel = $pet->level;
            $leveledUp = $currentLevel > $oldLevel;

            $pet->update([
                'level' => $currentLevel,
                'exp' => $currentExp,
            ]);

            if ($pet->is_equipped) {
                $character->clearStatsCache();
            }

            return Result::ok([
                'pet' => $pet->fresh(),
                'gainedExp' => $totalGainedExp,
                'leveledUp' => $leveledUp,
                'oldLevel' => $oldLevel,
                'newLevel' => $currentLevel,
                'consumedCount' => $consumedCount,
            ]);
        });
    }

    /**
     * Alchemiczny Syntezator Dusz (Sokowirówka Dusz).
     * Pobiera 3 chowańce tej samej rzadkości -> 75% szansy na chowańca wyższej rzadkości.
     */
    public function synthesizePets(Character $character, array $petIds): Result
    {
        return DB::transaction(function () use ($character, $petIds) {
            if (count($petIds) !== 3) {
                return Result::error('INVALID_COUNT', 'Do transmutacji w Alchemicznym Syntezatorze Dusz wymagane są dokładnie 3 chowańce.');
            }

            $pets = Pet::whereIn('id', $petIds)
                ->where('character_id', $character->id)
                ->where('is_equipped', false)
                ->get();

            if ($pets->count() !== 3) {
                return Result::error('INVALID_PETS', 'Wybierz 3 niezałożone chowańce należące do Twojej postaci.');
            }

            $rarities = $pets->pluck('rarity')->unique();
            if ($rarities->count() !== 1) {
                return Result::error('RARITY_MISMATCH', 'Wszystkie 3 wybrane chowańce muszą posiadać ten sam stopień rzadkości!');
            }

            $baseRarity = $rarities->first();
            $nextRarity = match ($baseRarity) {
                'common' => 'uncommon',
                'uncommon' => 'rare',
                'rare' => 'epic',
                'epic' => 'legendary',
                default => null,
            };

            if (!$nextRarity) {
                return Result::error('MAX_RARITY', 'Chowańce rangi Legendarnej osiągnęły już maksymalny poziom rzadkości!');
            }

            // Usunięcie 3 użytych petów
            Pet::whereIn('id', $pets->pluck('id'))->delete();

            // Losowanie: 75% szans na sukces
            $isSuccess = mt_rand(1, 100) <= 75;

            if (!$isSuccess) {
                return Result::ok([
                    'success' => false,
                    'message' => 'Rytuał Transmutacji nie powiódł się! Esencje 3 chowańców uległy rozproszeniu...',
                    'baseRarity' => $baseRarity,
                ]);
            }

            // Tworzenie nowego peta wyższego stopnia
            $stats = $this->generatePetStats($nextRarity);
            $name = $this->generatePetName($nextRarity);
            $icon = $this->getRandomPetIcon($nextRarity);

            $newPet = Pet::create([
                'character_id' => $character->id,
                'name' => $name,
                'rarity' => $nextRarity,
                'stats' => $stats,
                'level' => 1,
                'exp' => 0,
                'is_equipped' => false,
                'icon' => $icon,
            ]);

            return Result::ok([
                'success' => true,
                'pet' => $newPet,
                'message' => "Transmutacja udana! Powstał chowaniec wyższej ragi: {$newPet->name}!",
                'baseRarity' => $baseRarity,
                'nextRarity' => $nextRarity,
            ]);
        });
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
