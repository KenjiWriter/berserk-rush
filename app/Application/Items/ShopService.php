<?php

namespace App\Application\Items;

use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use Illuminate\Support\Str;
use App\Infrastructure\Persistence\ItemLedger;

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
        if (in_array($template->type, ['material', 'consumable', 'currency'])) {
            $existingItem = ItemInstance::where('owner_character_id', $character->id)
                ->where('template_id', $template->id)
                ->where('location', 'inventory')
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
                'location' => 'inventory',
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

        if ($item->location !== 'inventory') {
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
}
