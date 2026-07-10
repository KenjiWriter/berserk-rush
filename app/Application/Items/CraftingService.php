<?php

namespace App\Application\Items;

use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemRecipe;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CraftingService
{
    public function craftItem(Character $character, ItemRecipe $recipe): array
    {
        return DB::transaction(function () use ($character, $recipe) {
            // Check gold
            if ($character->gold < $recipe->gold_cost) {
                return ['success' => false, 'message' => 'Nie masz wystarczająco złota, by to wytworzyć.'];
            }

            // Check ingredients
            $inventory = $character->inventoryItems()->get();
            foreach ($recipe->ingredients as $ingredient) {
                $templateId = $ingredient['template_id'];
                $requiredQuantity = $ingredient['quantity'];

                $ownedQuantity = $inventory->where('template_id', $templateId)->sum('stack_size');
                if ($ownedQuantity < $requiredQuantity) {
                    return ['success' => false, 'message' => 'Brakuje materiałów do wytworzenia tego przedmiotu.'];
                }
            }

            // Take gold
            if ($recipe->gold_cost > 0) {
                $character->decrement('gold', $recipe->gold_cost);
            }

            // Take ingredients
            foreach ($recipe->ingredients as $ingredient) {
                $templateId = $ingredient['template_id'];
                $requiredQuantity = $ingredient['quantity'];
                
                $items = $inventory->where('template_id', $templateId)->sortBy('stack_size'); // Consume smaller stacks first
                $remainingToTake = $requiredQuantity;

                foreach ($items as $item) {
                    if ($remainingToTake <= 0) break;

                    if ($item->stack_size <= $remainingToTake) {
                        $remainingToTake -= $item->stack_size;
                        $item->delete();
                    } else {
                        $item->decrement('stack_size', $remainingToTake);
                        $remainingToTake = 0;
                    }
                }
            }

            $template = $recipe->resultItemTemplate;
            $rarity = 'common';
            $rollStats = [];

            // If it's a weapon or armor, determine rarity and stats
            if (in_array($template->type, ['weapon', 'armor'])) {
                $rarity = $this->rollRarity();
                $rollStats = $this->generateBonusStats($template, $rarity);
            }

            // Create result item
            $newItem = ItemInstance::create([
                'id' => (string) Str::ulid(),
                'template_id' => $recipe->result_item_template_id,
                'owner_character_id' => $character->id,
                'location' => 'inventory',
                'rarity' => $rarity,
                'stack_size' => 1,
                'roll_stats' => $rollStats,
            ]);

            // Ledger
            ItemLedger::create([
                'id' => (string) Str::ulid(),
                'character_id' => $character->id,
                'item_instance_id' => $newItem->id,
                'action' => 'crafting',
                'ref_type' => 'crafting_service',
                'quantity_change' => 1,
                'idempotency_key' => 'craft_' . Str::ulid(),
            ]);

            return [
                'success' => true, 
                'message' => 'Pomyślnie wytworzono przedmiot: ' . $template->name . ' (' . ucfirst($rarity) . ')!',
                'item' => $newItem,
            ];
        });
    }

    private function rollRarity(): string
    {
        $roll = mt_rand(1, 1000);
        // Common 70% (1-700)
        // Uncommon 20% (701-900)
        // Rare 8% (901-980)
        // Epic 1.9% (981-999)
        // Legendary 0.1% (1000)

        if ($roll <= 700) {
            return 'common';
        } elseif ($roll <= 900) {
            return 'uncommon';
        } elseif ($roll <= 980) {
            return 'rare';
        } elseif ($roll <= 999) {
            return 'epic';
        } else {
            return 'legendary';
        }
    }

    private function generateBonusStats($template, string $rarity): array
    {
        $rollsCount = match ($rarity) {
            'uncommon' => 1,
            'rare' => 2,
            'epic' => 3,
            'legendary' => 4,
            default => 0,
        };

        if ($rollsCount === 0) {
            return [];
        }

        $possibleStats = [
            'attack_min' => [1, 5],
            'attack_max' => [2, 8],
            'defense' => [1, 5],
            'hp_bonus' => [10, 50],
            'str_bonus' => [1, 3],
            'agi_bonus' => [1, 3],
            'int_bonus' => [1, 3],
            'vit_bonus' => [1, 3],
        ];

        // Based on item type, filter what can roll
        if ($template->type === 'weapon') {
            unset($possibleStats['defense']);
            unset($possibleStats['hp_bonus']);
        } elseif ($template->type === 'armor') {
            unset($possibleStats['attack_min']);
            unset($possibleStats['attack_max']);
        }

        $rolledStats = [];
        $keys = array_keys($possibleStats);

        for ($i = 0; $i < $rollsCount; $i++) {
            $statKey = $keys[array_rand($keys)];
            $range = $possibleStats[$statKey];
            $value = mt_rand($range[0], $range[1]);

            if (isset($rolledStats[$statKey])) {
                $rolledStats[$statKey] += $value;
            } else {
                $rolledStats[$statKey] = $value;
            }
        }

        return $rolledStats;
    }
}
