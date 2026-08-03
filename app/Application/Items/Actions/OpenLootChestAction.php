<?php

namespace App\Application\Items\Actions;

use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\ItemLedger;
use App\Infrastructure\Persistence\LootTable;
use App\Application\Loot\WeightedPicker;
use App\Application\Shared\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OpenLootChestAction
{
    public function __construct(
        private WeightedPicker $picker
    ) {}

    public function execute(Character $character, string $itemInstanceId, int $count = 1): Result
    {
        $count = max(1, min(3, $count)); // Clamp between 1 and 3

        return DB::transaction(function () use ($character, $itemInstanceId, $count) {
            /** @var ItemInstance|null $chestInstance */
            $chestInstance = ItemInstance::where('id', $itemInstanceId)
                ->whereIn('location', ['inventory', 'material_stash'])
                ->lockForUpdate()
                ->first();

            if (!$chestInstance || !$chestInstance->template) {
                return Result::error('CHEST_NOT_FOUND', 'Nie znaleziono skrzyni w ekwipunku.');
            }

            if ($chestInstance->owner_character_id !== $character->id) {
                $ownerChar = Character::find($chestInstance->owner_character_id);
                if ($ownerChar && $ownerChar->user_id === $character->user_id) {
                    $character = $ownerChar;
                } else {
                    return Result::error('CHEST_NOT_FOUND', 'Nie znaleziono skrzyni w ekwipunku.');
                }
            }

            if ($chestInstance->stack_size < $count) {
                return Result::error('NOT_ENOUGH_CHESTS', "Nie posiadasz {$count} sztuk tej skrzyni (posiadasz: {$chestInstance->stack_size}).");
            }

            $chestTemplate = $chestInstance->template;

            if ($chestTemplate->type !== 'consumable' || $chestTemplate->sub_type !== 'chest') {
                return Result::error('INVALID_ITEM', 'Ten przedmiot nie jest skrzynią do otwarcia.');
            }

            if ($chestTemplate->level_requirement && $character->level < $chestTemplate->level_requirement) {
                return Result::error('LEVEL_TOO_LOW', "Wymagany poziom {$chestTemplate->level_requirement} do otwarcia tej skrzyni.");
            }

            $lootTableName = $chestTemplate->base_stats['loot_table'] ?? null;
            if (!$lootTableName) {
                return Result::error('NO_LOOT_TABLE', 'Skrzynia nie posiada zdefiniowanej puli nagród.');
            }

            $lootTable = LootTable::where('name', $lootTableName)->first();
            if (!$lootTable) {
                return Result::error('LOOT_TABLE_NOT_FOUND', 'Nie odnaleziono tabeli nagród dla skrzyni.');
            }

            $entries = $lootTable->entries()->with('itemTemplate')->get();
            if ($entries->isEmpty()) {
                return Result::error('EMPTY_LOOT_TABLE', 'Tabela nagród tej skrzyni jest pusta.');
            }

            $entriesArray = $entries->toArray();
            $poolTemplates = $entries->pluck('itemTemplate')->filter()->values();

            $spins = [];

            for ($spin = 0; $spin < $count; $spin++) {
                // 1. Pick winning entry using WeightedPicker
                $winningEntryArray = $this->picker->pick($entriesArray);
                if (!$winningEntryArray) {
                    $winningEntryArray = $entriesArray[0];
                }

                /** @var LootTableEntry $winningEntry */
                $winningEntry = $entries->firstWhere('id', $winningEntryArray['id']);
                $winningTemplate = $winningEntry->itemTemplate;

                if (!$winningTemplate) {
                    continue;
                }

                $winningQty = mt_rand($winningEntry->min_qty, $winningEntry->max_qty);

                // 2. Log consumption in ItemLedger
                ItemLedger::create([
                    'id' => (string) Str::ulid(),
                    'character_id' => $character->id,
                    'item_instance_id' => $itemInstanceId,
                    'action' => 'open_chest',
                    'ref_type' => 'manual',
                    'quantity_change' => -1,
                    'idempotency_key' => 'open_chest_' . Str::ulid(),
                ]);

                // 3. Decrement chest item instance stack size
                if ($chestInstance->stack_size > 1) {
                    $chestInstance->decrement('stack_size');
                } else {
                    $chestInstance->delete();
                }

                // 4. Grant winning material to character
                $targetLocation = in_array($winningTemplate->type, ['material', 'currency']) ? 'material_stash' : 'inventory';

                $existingItem = ItemInstance::where('owner_character_id', $character->id)
                    ->where('template_id', $winningTemplate->id)
                    ->where('location', $targetLocation)
                    ->first();

                if ($existingItem && in_array($winningTemplate->type, ['material', 'consumable', 'currency', 'egg', 'key', 'quest_item'])) {
                    $existingItem->increment('stack_size', $winningQty);
                } else {
                    ItemInstance::create([
                        'id' => (string) Str::ulid(),
                        'owner_character_id' => $character->id,
                        'template_id' => $winningTemplate->id,
                        'location' => $targetLocation,
                        'stack_size' => $winningQty,
                    ]);
                }

                // 5. Build CS-style Roulette Reel items list (40 items total)
                $rouletteItems = [];
                $totalReelCount = 40;
                $winningIndex = 28;

                for ($i = 0; $i < $totalReelCount; $i++) {
                    if ($i === $winningIndex) {
                        $rouletteItems[] = [
                            'id' => $winningTemplate->id,
                            'name' => $winningTemplate->name,
                            'icon' => $winningTemplate->icon,
                            'type' => $winningTemplate->type,
                            'rarity' => $this->resolveRarity($winningEntry->weight),
                            'quantity' => $winningQty,
                            'is_winner' => true,
                        ];
                    } else {
                        $randomTpl = $poolTemplates->random();
                        $randomEntry = $entries->firstWhere('ref_ulid', $randomTpl->id);
                        $randQty = $randomEntry ? mt_rand($randomEntry->min_qty, $randomEntry->max_qty) : 1;
                        $weight = $randomEntry ? $randomEntry->weight : 20;

                        $rouletteItems[] = [
                            'id' => $randomTpl->id,
                            'name' => $randomTpl->name,
                            'icon' => $randomTpl->icon,
                            'type' => $randomTpl->type,
                            'rarity' => $this->resolveRarity($weight),
                            'quantity' => $randQty,
                            'is_winner' => false,
                        ];
                    }
                }

                $spins[] = [
                    'spin_index' => $spin,
                    'winning_item' => [
                        'id' => $winningTemplate->id,
                        'name' => $winningTemplate->name,
                        'icon' => $winningTemplate->icon,
                        'quantity' => $winningQty,
                        'rarity' => $this->resolveRarity($winningEntry->weight),
                    ],
                    'winning_index' => $winningIndex,
                    'roulette_items' => $rouletteItems,
                ];
            }

            $remainingChests = ItemInstance::where('owner_character_id', $character->id)
                ->where('template_id', $chestTemplate->id)
                ->whereIn('location', ['inventory', 'material_stash'])
                ->sum('stack_size');

            return Result::ok([
                'chest_template' => [
                    'id' => $chestTemplate->id,
                    'name' => $chestTemplate->name,
                    'icon' => $chestTemplate->icon,
                ],
                'count' => count($spins),
                'spins' => $spins,
                'winning_item' => $spins[0]['winning_item'] ?? null,
                'winning_index' => $spins[0]['winning_index'] ?? 28,
                'roulette_items' => $spins[0]['roulette_items'] ?? [],
                'remaining_chests' => $remainingChests,
            ]);
        });
    }

    private function resolveRarity(int $weight): string
    {
        if ($weight <= 10) return 'epic';
        if ($weight <= 15) return 'rare';
        if ($weight <= 20) return 'uncommon';
        return 'common';
    }
}
