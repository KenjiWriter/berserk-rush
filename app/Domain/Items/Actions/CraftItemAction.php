<?php

namespace App\Domain\Items\Actions;

use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemRecipe;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CraftItemAction
{
    public function execute(Character $character, ItemRecipe $recipe): array
    {
        return DB::transaction(function () use ($character, $recipe) {
            // Check gold
            if ($character->gold < $recipe->gold_cost) {
                return ['success' => false, 'message' => 'Nie masz wystarczająco złota do warzenia tej mikstury.'];
            }

            // Check ingredients
            $inventory = $character->inventoryItems()->get();
            foreach ($recipe->ingredients as $ingredient) {
                $templateId = $ingredient['template_id'];
                $requiredQuantity = $ingredient['quantity'];

                $ownedQuantity = $inventory->where('template_id', $templateId)->sum('stack_size');
                if ($ownedQuantity < $requiredQuantity) {
                    return ['success' => false, 'message' => 'Brakuje materiałów do uwarzenia tej mikstury.'];
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

            // Create result item
            $newItem = ItemInstance::create([
                'id' => (string) Str::ulid(),
                'template_id' => $recipe->result_item_template_id,
                'owner_character_id' => $character->id,
                'location' => 'inventory',
                'rarity' => 'common',
                'stack_size' => 1,
            ]);

            // Ledger
            ItemLedger::create([
                'id' => (string) Str::ulid(),
                'character_id' => $character->id,
                'item_instance_id' => $newItem->id,
                'action' => 'crafting',
                'ref_type' => 'witch_cauldron',
                'quantity_change' => 1,
                'idempotency_key' => 'craft_' . Str::ulid(),
            ]);

            return ['success' => true, 'message' => 'Pomyślnie uwarzono miksturę!'];
        });
    }
}
