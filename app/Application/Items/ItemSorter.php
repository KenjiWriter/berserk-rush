<?php

namespace App\Application\Items;

use App\Infrastructure\Persistence\ItemInstance;
use Illuminate\Support\Collection;

class ItemSorter
{
    /**
     * Category and slot priority weight.
     * Lower number = displayed higher up in the inventory grid.
     */
    public static function getCategoryWeight(ItemInstance $item): int
    {
        $template = $item->template;
        if (!$template) {
            return 9999;
        }

        $type = strtolower($template->type ?? '');
        $subType = strtolower($template->sub_type ?? '');
        $slot = strtolower($template->slot ?? '');

        if ($type === 'weapon') {
            return match ($subType) {
                'sword' => 100,
                'axe' => 101,
                'bow' => 102,
                'dagger' => 103,
                'wand' => 104,
                'bell' => 105,
                default => 109,
            };
        }

        if ($type === 'armor') {
            return match ($slot) {
                'head' => 200,      // Helmets
                'chest' => 300,     // Body armor
                'feet' => 400,      // Boots
                default => 500,     // Other armor (shields, etc.)
            };
        }

        if ($type === 'accessory') {
            return match ($slot) {
                'neck' => 600,      // Amulets / Necklaces
                'ring' => 700,      // Rings
                default => 800,     // Other accessories
            };
        }

        return match ($type) {
            'consumable' => 900,
            'egg' => 1000,
            'chest', 'key' => 1100,
            'skill_book', 'scroll' => 1200,
            'material', 'upgrade_material' => 1300,
            default => 2000,
        };
    }

    /**
     * Rarity rank (higher = more rare / valuable).
     */
    public static function getRarityScore(ItemInstance $item): int
    {
        return match (strtolower($item->rarity ?? 'common')) {
            'legendary' => 5,
            'epic' => 4,
            'rare' => 3,
            'uncommon' => 2,
            default => 1,
        };
    }

    /**
     * Sorts a collection of ItemInstance models:
     * 1. Category / Subtype / Slot Weight (ASCENDING: weapons -> helmets -> chest -> boots -> neck -> ring -> consumables -> eggs -> chests -> materials)
     * 2. Combat Power / Item Strength (DESCENDING: highest combat power on top)
     * 3. Level Requirement (DESCENDING: higher level req on top)
     * 4. Upgrade Level (DESCENDING: +9 on top)
     * 5. Rarity (DESCENDING: Legendary on top)
     * 6. Stack size (DESCENDING)
     * 7. Name (ASCENDING)
     */
    public static function sort(Collection $items, bool $equippedFirst = false): Collection
    {
        return $items->sort(function (ItemInstance $a, ItemInstance $b) use ($equippedFirst) {
            // 0. Equipped status (EQUIPPED FIRST if requested)
            if ($equippedFirst) {
                $eqA = ($a->location === 'equipped') ? 0 : 1;
                $eqB = ($b->location === 'equipped') ? 0 : 1;
                if ($eqA !== $eqB) {
                    return $eqA <=> $eqB;
                }
            }

            // 1. Category weight (ASCENDING)
            $catA = self::getCategoryWeight($a);
            $catB = self::getCategoryWeight($b);
            if ($catA !== $catB) {
                return $catA <=> $catB;
            }

            // 2. Combat power / Item Strength (DESCENDING)
            $powerA = $a->getCombatPower();
            $powerB = $b->getCombatPower();
            if ($powerA !== $powerB) {
                return $powerB <=> $powerA;
            }

            // 3. Level requirement (DESCENDING)
            $lvlA = $a->template->level_requirement ?? 0;
            $lvlB = $b->template->level_requirement ?? 0;
            if ($lvlA !== $lvlB) {
                return $lvlB <=> $lvlA;
            }

            // 4. Upgrade level (DESCENDING)
            $upA = $a->upgrade_level ?? 0;
            $upB = $b->upgrade_level ?? 0;
            if ($upA !== $upB) {
                return $upB <=> $upA;
            }

            // 5. Rarity score (DESCENDING)
            $rarA = self::getRarityScore($a);
            $rarB = self::getRarityScore($b);
            if ($rarA !== $rarB) {
                return $rarB <=> $rarA;
            }

            // 6. Stack size (DESCENDING)
            $stackA = $a->stack_size ?? 1;
            $stackB = $b->stack_size ?? 1;
            if ($stackA !== $stackB) {
                return $stackB <=> $stackA;
            }

            // 7. Name (ASCENDING)
            $nameA = $a->template->name ?? '';
            $nameB = $b->template->name ?? '';
            return strcasecmp($nameA, $nameB);
        })->values();
    }
}
