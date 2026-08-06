<?php

namespace App\Application\Items;

use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use Illuminate\Support\Facades\DB;

class DismantleService
{
    /**
     * Oblicza ile Runicznych Odłamków otrzyma gracz za przetopienie przedmiotu.
     */
    public function calculateShardYield(ItemInstance $item): int
    {
        $level = max(1, (int) ($item->template->level_requirement ?? 1));
        $baseShards = max(1, (int) ceil($level / 10));

        $rarityMult = match ($item->rarity ?? 'common') {
            'uncommon'  => 2.0,
            'rare'      => 4.0,
            'epic'      => 8.0,
            'legendary' => 15.0,
            default     => 1.0,
        };

        $upgradeMult = match ((int) ($item->upgrade_level ?? 0)) {
            1       => 1.1,
            2       => 1.25,
            3       => 1.5,
            4       => 1.8,
            5       => 2.2,
            6       => 2.8,
            7       => 3.6,
            8       => 4.6,
            9       => 6.0,
            default => 1.0,
        };

        $enchantCount = count($item->getEnchantments());
        $enchantMult = 1.0 + ($enchantCount * 0.15);

        return max(1, (int) round($baseShards * $rarityMult * $upgradeMult * $enchantMult));
    }

    /**
     * Przetapia przedmiot i przyznaje Runiczne Odłamki do schowka na materiały.
     */
    public function dismantleItem(Character $character, ItemInstance $item): array
    {
        if ($item->owner_character_id !== $character->id) {
            return ['success' => false, 'message' => 'Nie jesteś właścicielem tego przedmiotu.'];
        }

        if ($item->isEquipped()) {
            return ['success' => false, 'message' => 'Zdejmij przedmiot przed przetopieniem.'];
        }

        $type = $item->template->type ?? '';
        if (!in_array($type, ['weapon', 'armor', 'accessory'], true)) {
            return ['success' => false, 'message' => 'Możesz przetapiać tylko broń, zbroję oraz akcesoria.'];
        }

        $runicTemplate = ItemTemplate::where('name', 'Runiczny Odłamek')->first();
        if (!$runicTemplate) {
            return ['success' => false, 'message' => 'Błąd systemu: Szablon Runiczny Odłamek nie istnieje w bazie.'];
        }

        $yield = $this->calculateShardYield($item);
        $itemName = $item->template->name . ($item->upgrade_level > 0 ? " +{$item->upgrade_level}" : "");

        try {
            DB::transaction(function () use ($character, $item, $runicTemplate, $yield) {
                $item->delete();

                $stashStack = ItemInstance::where('owner_character_id', $character->id)
                    ->where('template_id', $runicTemplate->id)
                    ->whereIn('location', ['material_stash', 'inventory'])
                    ->first();

                if ($stashStack) {
                    $stashStack->stack_size += $yield;
                    $stashStack->save();
                } else {
                    ItemInstance::create([
                        'template_id'        => $runicTemplate->id,
                        'owner_character_id' => $character->id,
                        'user_id'            => $character->user_id,
                        'location'           => 'material_stash',
                        'stack_size'         => $yield,
                        'rarity'             => 'common',
                    ]);
                }
            });

            return [
                'success' => true,
                'yield'   => $yield,
                'message' => "Przetopiono {$itemName} i uzyskano {$yield} Runicznych Odłamków.",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Nie udało się przetopić przedmiotu: ' . $e->getMessage(),
            ];
        }
    }
}
