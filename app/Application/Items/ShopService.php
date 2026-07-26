<?php

namespace App\Application\Items;

use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use Illuminate\Support\Str;
use App\Infrastructure\Persistence\ItemLedger;
use Illuminate\Support\Facades\DB;

class ShopService
{
    public function getBuyPrice(?ItemTemplate $template): int
    {
        if (!$template) {
            return 0;
        }
        if (in_array($template->type, ['material', 'consumable'])) {
            return $template->level_requirement * 20 + 10;
        }
        return $template->level_requirement * 100 + 50;
    }


    public function getSellPrice(ItemInstance $item): int
    {
        $baseValue = $this->getBuyPrice($item->template);
        $upgradeValue = $item->upgrade_level * 50;
        
        // Sell for 25% of value
        return (int) floor(($baseValue + $upgradeValue) * 0.25);
    }

    public function buyItem(Character $character, $source, int $quantity = 1): array
    {
        $isMerchantItem = $source instanceof \App\Infrastructure\Persistence\MerchantItem;
        $template = $isMerchantItem ? $source->template : $source;

        $price = $this->getBuyPrice($template) * $quantity;

        if ($character->gold < $price) {
            return ['success' => false, 'message' => 'Nie masz wystarczająco złota.'];
        }

        $character->gold -= $price;
        $character->save();

        // Stack logic
        // Location & Stack logic
        $targetLocation = ($template->type === 'material') ? 'material_stash' : 'inventory';

        if (in_array($template->type, ['material', 'consumable', 'currency'])) {
            $existingItem = ItemInstance::where('owner_character_id', $character->id)
                ->where('template_id', $template->id)
                ->where('location', $targetLocation)
                ->first();

            if ($existingItem) {
                $existingItem->stack_size += $quantity;
                $existingItem->save();

                ItemLedger::create([
                    'id' => Str::ulid(),
                    'character_id' => $character->id,
                    'item_instance_id' => $existingItem->id,
                    'action' => 'buy',
                    'ref_type' => 'shop',
                    'quantity_change' => $quantity,
                    'idempotency_key' => Str::ulid(),
                ]);

                return ['success' => true, 'message' => "Kupiono {$quantity}x {$template->name}."];
            }

            // If not existing, check capacity
            $isFull = ($targetLocation === 'material_stash') ? $character->isMaterialStashFull() : $character->isBackpackFull();
            if ($isFull) {
                return ['success' => false, 'message' => ($targetLocation === 'material_stash') ? 'Magazyn materiałów jest pełny (100/100).' : 'Plecak jest pełny.'];
            }
        } else {
            if ($character->isBackpackFull()) {
                return ['success' => false, 'message' => 'Plecak jest pełny.'];
            }
        }

        for ($i = 0; $i < $quantity; $i++) {
            $rollStats = [];
            
            if ($isMerchantItem && $source->is_limited) {
                $source->increment('sold_quantity');
                $rollStats['mint'] = $source->sold_quantity;
                $rollStats['max_mint'] = $source->max_quantity;
            }

            $itemInstance = ItemInstance::create([
                'id' => Str::ulid(),
                'template_id' => $template->id,
                'owner_character_id' => $character->id,
                'location' => $targetLocation,
                'stack_size' => 1,
                'rarity' => 'common',
                'upgrade_level' => 0,
                'roll_stats' => empty($rollStats) ? null : $rollStats
            ]);

            ItemLedger::create([
                'id' => Str::ulid(),
                'character_id' => $character->id,
                'item_instance_id' => $itemInstance->id,
                'action' => 'buy',
                'ref_type' => 'shop',
                'quantity_change' => 1,
                'idempotency_key' => Str::ulid(),
            ]);
        }

        return ['success' => true, 'message' => "Kupiono {$quantity}x {$template->name}."];
    }

    public function sellItem(Character $character, ItemInstance $item, int|string $quantity = 1): array
    {
        if ($item->owner_character_id !== $character->id) {
            return ['success' => false, 'message' => 'Nie jesteś właścicielem tego przedmiotu.'];
        }

        if ($item->location === 'equipped') {
            return ['success' => false, 'message' => 'Musisz zdjąć ten przedmiot przed sprzedażą.'];
        }

        if (!in_array($item->location, ['inventory', 'material_stash'])) {
            return ['success' => false, 'message' => 'Nie możesz sprzedać tego przedmiotu.'];
        }

        $availableStack = max(1, (int)($item->stack_size ?? 1));
        if ($quantity === 'all' || (is_numeric($quantity) && (int)$quantity >= $availableStack)) {
            $toSell = $availableStack;
        } else {
            $toSell = max(1, min((int)$quantity, $availableStack));
        }

        $unitPrice = $this->getSellPrice($item);
        $totalPrice = $unitPrice * $toSell;

        $character->gold += $totalPrice;
        $character->save();

        ItemLedger::create([
            'id' => Str::ulid(),
            'character_id' => $character->id,
            'item_instance_id' => $item->id,
            'action' => 'sell',
            'ref_type' => 'shop',
            'quantity_change' => -$toSell,
            'idempotency_key' => Str::ulid(),
        ]);

        $itemName = $item->template->name ?? 'Przedmiot';

        if ($item->stack_size > $toSell) {
            $item->stack_size -= $toSell;
            $item->save();
        } else {
            $item->delete();
        }

        return [
            'success' => true, 
            'message' => "Sprzedano {$toSell}x {$itemName} za {$totalPrice} złota.", 
            'goldAdded' => $totalPrice
        ];
    }

    public function sellMultipleItems(Character $character, array $itemInstanceIds): array
    {
        if (empty($itemInstanceIds)) {
            return ['success' => false, 'message' => 'Nie wybrano żadnych przedmiotów do sprzedaży.'];
        }

        return DB::transaction(function () use ($character, $itemInstanceIds) {
            $items = ItemInstance::whereIn('id', $itemInstanceIds)
                ->where('owner_character_id', $character->id)
                ->whereIn('location', ['inventory', 'material_stash'])
                ->with('template')
                ->get();

            if ($items->isEmpty()) {
                return ['success' => false, 'message' => 'Nie znaleziono odpowiednich przedmiotów w ekwipunku.'];
            }

            $totalPrice = 0;
            $totalCount = 0;

            foreach ($items as $item) {
                $availableStack = max(1, (int)($item->stack_size ?? 1));
                $unitPrice = $this->getSellPrice($item);
                $itemTotalPrice = $unitPrice * $availableStack;

                $totalPrice += $itemTotalPrice;
                $totalCount += $availableStack;

                ItemLedger::create([
                    'id' => Str::ulid(),
                    'character_id' => $character->id,
                    'item_instance_id' => $item->id,
                    'action' => 'sell',
                    'ref_type' => 'shop',
                    'quantity_change' => -$availableStack,
                    'idempotency_key' => Str::ulid(),
                ]);

                $item->delete();
            }

            $character->gold += $totalPrice;
            $character->save();

            return [
                'success' => true,
                'message' => "Sprzedano masowo {$totalCount} szt. przedmiotów za {$totalPrice} złota.",
                'goldAdded' => $totalPrice,
                'soldCount' => $totalCount,
            ];
        });
    }
}
