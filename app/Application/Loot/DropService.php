<?php

namespace App\Application\Loot;

use App\Infrastructure\Persistence\Encounter;
use App\Infrastructure\Persistence\CurrencyLedger;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemLedger;
use App\Application\Loot\DTOs\DropResult;
use App\Application\Shared\Result;
use App\Infrastructure\RNG\RandomProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DropService
{
    public function __construct(
        private WeightedPicker $picker,
        private RandomProvider $rng,
        private \App\Domain\Wizard\EnchantmentStrategy $enchantmentStrategy,
        private \App\Application\Items\ItemStatRoller $statRoller
    ) {}

    public function rollLoot(Encounter $encounter): Result
    {
        try {
            $isOverLevel = $encounter->combat_data['is_overlevel'] ?? false;
            $groupMonsters = $encounter->combat_data['monsters'] ?? [];

            // Walka grupowa (over-level, 3-4 potworów naraz): każdy pokonany potwór losuje
            // NIEZALEŻNIE ze swojej własnej tabeli zrzutów (z tą samą karą 66% szansy na
            // brak dropu per sztuka jak poniżej dla walki 1 na 1) - inaczej zabicie 3-4
            // potworów dawało dokładnie tyle samo łupu co zabicie jednego, patrz
            // docs/modules/loot.md.
            if ($isOverLevel && !empty($groupMonsters)) {
                return $this->rollGroupLoot($encounter, $groupMonsters);
            }

            $rolled = $this->rollForMonster($encounter, $encounter->monster);

            $dropResult = new DropResult($rolled['gold'], $rolled['gems'], $rolled['items'], $rolled['materials'], $rolled['hadDrops']);
            $this->updateEncounterResult($encounter, $dropResult);

            return Result::ok($dropResult);
        } catch (\Exception $e) {
            Log::error('Failed to roll loot', [
                'encounter_id' => $encounter->id,
                'error' => $e->getMessage()
            ]);
            return Result::error('DROP_FAILED', 'Nie udało się wylosować łupu');
        }
    }

    private function rollGroupLoot(Encounter $encounter, array $groupMonsters): Result
    {
        try {
            $monsterIds = collect($groupMonsters)->pluck('id')->unique()->all();
            $monsterModels = \App\Infrastructure\Persistence\Monster::whereIn('id', $monsterIds)->get()->keyBy('id');

            $totalGold = 0;
            $totalGems = 0;
            $allItems = [];
            $allMaterials = [];
            $hadDrops = false;

            foreach ($groupMonsters as $entry) {
                $monster = $monsterModels->get($entry['id'] ?? null);
                $rolled = $this->rollForMonster($encounter, $monster);

                $totalGold += $rolled['gold'];
                $totalGems += $rolled['gems'];
                $allItems = array_merge($allItems, $rolled['items']);
                $allMaterials = array_merge($allMaterials, $rolled['materials']);
                $hadDrops = $hadDrops || $rolled['hadDrops'];
            }

            $dropResult = new DropResult($totalGold, $totalGems, $allItems, $allMaterials, $hadDrops);
            $this->updateEncounterResult($encounter, $dropResult);

            return Result::ok($dropResult);
        } catch (\Exception $e) {
            Log::error('Failed to roll group loot', [
                'encounter_id' => $encounter->id,
                'error' => $e->getMessage()
            ]);
            return Result::error('DROP_FAILED', 'Nie udało się wylosować łupu grupowego');
        }
    }

    /**
     * @return array{gold: int, gems: int, items: array, materials: array, hadDrops: bool}
     */
    private function rollForMonster(Encounter $encounter, ?\App\Infrastructure\Persistence\Monster $monster): array
    {
        $empty = ['gold' => 0, 'gems' => 0, 'items' => [], 'materials' => [], 'hadDrops' => false];

        if (!$monster || !$monster->loot_table_id) {
            return $empty;
        }

        $isBoss = $monster->rank === 'boss';

        // Boss na mapie ma 50% szansy na łup po pokonaniu, zwykły potwór 33% szansy (66% braku łupu)
        $dropChance = $isBoss ? 50 : 33;
        if ($this->rng->int(1, 100) > $dropChance) {
            return $empty;
        }

        $lootTable = $monster->lootTable;
        if (!$lootTable) {
            return $empty;
        }

        $activeQuestIds = $encounter->character->activeQuests()->pluck('quest_id')->toArray();
        $entriesCollection = $lootTable->entries()->with('itemTemplate')->get();

        $filteredEntries = $entriesCollection->filter(function ($entry) use ($activeQuestIds) {
            if (in_array($entry->reward_type, ['item', 'material'])) {
                $template = $entry->itemTemplate;
                if ($template && $template->type === 'quest_item') {
                    if (!$template->quest_id || !in_array($template->quest_id, $activeQuestIds)) {
                        return false;
                    }
                }
            }
            return true;
        });

        $entries = array_values($filteredEntries->toArray());
        if (empty($entries)) {
            return $empty;
        }

        $gold = 0;
        $gems = 0;
        $items = [];
        $materials = [];

        $eventService = app(\App\Application\Events\WeekendEventService::class);
        $gemsMult = $eventService->getGemsMultiplier();
        $dropMult = $eventService->getDropMultiplier();

        $doubleDropChance = $encounter->character->getEquipmentStats()['double_drop_chance'] ?? 0;
        if ($doubleDropChance > 0 && $this->rng->int(1, 100) <= $doubleDropChance) {
            $dropMult *= 2;
        }

        if ($isBoss) {
            // Dla Bossa mapy (po trafieniu 50% szansy):
            // 1. Losujemy specjalny drop: Klucz LUB Skrzynię (ksiąg umiejętności / mapową)
            $specialEntries = array_filter($entries, function ($entry) {
                $tpl = $entry['item_template'] ?? null;
                if (!$tpl) return false;
                $subType = $tpl['sub_type'] ?? '';
                $name = $tpl['name'] ?? '';
                return $subType === 'key' || $subType === 'chest' || str_contains($name, 'Klucz') || str_contains($name, 'Skrzynia');
            });

            if (!empty($specialEntries)) {
                $pickedSpecial = $this->picker->pick(array_values($specialEntries));
                if ($pickedSpecial) {
                    $this->processEntry($encounter, $pickedSpecial, $gold, $gems, $items, $materials, $dropMult, $gemsMult);
                }
            }

            // 2. Dodatkowo losujemy normalny drop (materiały, złoto, ekwipunek)
            $normalEntries = array_filter($entries, function ($entry) {
                $tpl = $entry['item_template'] ?? null;
                if (!$tpl) return true;
                $subType = $tpl['sub_type'] ?? '';
                $name = $tpl['name'] ?? '';
                return !($subType === 'key' || $subType === 'chest' || str_contains($name, 'Klucz') || str_contains($name, 'Skrzynia'));
            });

            $pickedNormal = !empty($normalEntries) ? $this->picker->pick(array_values($normalEntries)) : $this->picker->pick($entries);
            if ($pickedNormal) {
                $this->processEntry($encounter, $pickedNormal, $gold, $gems, $items, $materials, $dropMult, $gemsMult);
            }
        } else {
            // Zwykły potwór: losujemy 1 pozycję z tabeli
            $selectedEntry = $this->picker->pick($entries);
            if ($selectedEntry) {
                $this->processEntry($encounter, $selectedEntry, $gold, $gems, $items, $materials, $dropMult, $gemsMult);
            }
        }

        return [
            'gold' => $gold,
            'gems' => $gems,
            'items' => $items,
            'materials' => $materials,
            'hadDrops' => (!empty($items) || !empty($materials) || $gold > 0 || $gems > 0),
        ];
    }

    private function processEntry(Encounter $encounter, array $selectedEntry, int &$gold, int &$gems, array &$items, array &$materials, float $dropMult, float $gemsMult): void
    {
        $quantity = $this->rng->int($selectedEntry['min_qty'], $selectedEntry['max_qty']);
        $templateUlid = $selectedEntry['ref_ulid'] ?? null;
        $template = $templateUlid ? \App\Infrastructure\Persistence\ItemTemplate::find($templateUlid) : null;
        $itemName = $template ? $template->name : "Przedmiot";

        switch ($selectedEntry['reward_type']) {
            case 'gold':
                $gold += $quantity;
                break;

            case 'gems':
                $gems += (int) round($quantity * $gemsMult);
                break;

            case 'item':
                $finalQty = (int) round($quantity * $dropMult);
                $isStackable = $template && in_array($template->type, ['material', 'consumable', 'currency', 'egg', 'key']);
                $hasExisting = $isStackable && ItemInstance::where('owner_character_id', $encounter->character_id)
                    ->where('template_id', $templateUlid)
                    ->where('location', 'inventory')
                    ->exists();

                if ($hasExisting || !$encounter->character->isBackpackFull()) {
                    $items[] = [
                        'template_id' => $templateUlid,
                        'name' => $itemName,
                        'rarity' => 'common',
                        'quantity' => $finalQty,
                    ];
                }
                break;

            case 'material':
                $finalQty = (int) round($quantity * $dropMult);
                $hasExisting = ItemInstance::where('owner_character_id', $encounter->character_id)
                    ->where('template_id', $templateUlid)
                    ->where('location', 'material_stash')
                    ->exists();

                if ($hasExisting || !$encounter->character->isMaterialStashFull()) {
                    $materials[] = [
                        'template_id' => $templateUlid,
                        'name' => $itemName,
                        'rarity' => 'common',
                        'quantity' => $finalQty,
                    ];
                }
                break;
        }
    }

    public function applyLoot(Encounter $encounter): Result
    {
        $idempotencyKey = "encounter:{$encounter->id}:drop";

        try {
            return DB::transaction(function () use ($encounter, $idempotencyKey) {
                if (CurrencyLedger::where('idempotency_key', $idempotencyKey . ":gold")->exists() ||
                    ItemLedger::where('idempotency_key', $idempotencyKey . ":item:0")->exists()) {
                    Log::info('Drops already applied for encounter', ['encounter_id' => $encounter->id]);
                    return Result::ok(new DropResult(0, 0, [], [], false));
                }

                $drops = $encounter->result['drops'] ?? null;
                if (!$drops) {
                    return Result::ok(new DropResult(0, 0, [], [], false));
                }

                $gold = $drops['gold'] ?? 0;
                $gems = $drops['gems'] ?? 0;
                $items = $drops['items'] ?? [];
                $materials = $drops['materials'] ?? [];

                if ($gold > 0) {
                    $this->applyCurrencyReward($encounter, 'gold', $gold, "{$idempotencyKey}:gold");
                }

                if ($gems > 0) {
                    $this->applyCurrencyReward($encounter, 'gems', $gems, "{$idempotencyKey}:gems");
                }

                $appliedItems = [];
                $appliedMaterials = [];

                foreach ($items as $item) {
                    if (!empty($item['template_id'])) {
                        $res = $this->applyItemReward($encounter, $item['template_id'], $item['quantity'] ?? 1, $idempotencyKey);
                        $appliedItems = array_merge($appliedItems, $res);
                    }
                }

                foreach ($materials as $mat) {
                    if (!empty($mat['template_id'])) {
                        $res = $this->applyItemReward($encounter, $mat['template_id'], $mat['quantity'] ?? 1, $idempotencyKey);
                        $appliedMaterials = array_merge($appliedMaterials, $res);
                    }
                }

                $dropResult = new DropResult($gold, $gems, $appliedItems, $appliedMaterials, true);
                return Result::ok($dropResult);
            });
        } catch (\Exception $e) {
            Log::error('Failed to apply loot drops', [
                'encounter_id' => $encounter->id,
                'error' => $e->getMessage()
            ]);
            return Result::error('DROP_FAILED', 'Nie udało się zastosować łupu');
        }
    }

    public function rollAndApplyRewards(Encounter $encounter): Result
    {
        $rollRes = $this->rollLoot($encounter);
        if ($rollRes->isError()) return $rollRes;

        return $this->applyLoot($encounter);
    }

    private function applyCurrencyReward(Encounter $encounter, string $currencyType, int $amount, string $idempotencyKey): void
    {
        $character = $encounter->character;
        
        if ($currencyType === 'gold') {
            $currentBalance = $character->gold;
            $newBalance = $currentBalance + $amount;
            $character->update(['gold' => $newBalance]);
        } else {
            $user = $character->user;
            $currentBalance = $user->gems;
            $newBalance = $currentBalance + $amount;
            $user->update(['gems' => $newBalance]);
        }

        // Create the ledger entry with balance_after
        CurrencyLedger::create([
            'id' => Str::ulid(), // Fixed: use Str::ulid() instead of $this->generateId()
            'character_id' => $encounter->character_id,
            'currency_type' => $currencyType,
            'amount' => $amount,
            'balance_after' => $newBalance,
            'idempotency_key' => $idempotencyKey,
            'source_type' => 'encounter',
            'source_id' => $encounter->id,
            'created_at' => now(),
        ]);
    }

    private function applyItemReward(Encounter $encounter, ?string $templateUlid, int $quantity, string $idempotencyKey): array
    {
        if (!$templateUlid || $quantity <= 0) {
            return [];
        }

        $character = $encounter->character;
        $items = [];
        
        $template = \App\Infrastructure\Persistence\ItemTemplate::find($templateUlid);
        $itemName = $template ? $template->name : "Item {$templateUlid}";
        
        $isStackable = $template && (in_array($template->type, ['material', 'consumable', 'currency', 'egg', 'key', 'quest_item']) || ($template->sub_type === 'key'));

        if ($isStackable) {
            $location = ($template->type === 'material') ? 'material_stash' : 'inventory';

            $existingItem = ItemInstance::where('owner_character_id', $character->id)
                ->where('template_id', $templateUlid)
                ->whereIn('location', ['material_stash', 'inventory'])
                ->first();

            if ($existingItem) {
                $existingItem->location = $location;
                $existingItem->stack_size += $quantity;
                $existingItem->save();

                ItemLedger::create([
                    'id' => Str::ulid(),
                    'character_id' => $character->id,
                    'item_instance_id' => $existingItem->id,
                    'action' => 'drop',
                    'ref_type' => 'encounter',
                    'ref_id' => $encounter->id,
                    'quantity_change' => $quantity,
                    'idempotency_key' => $idempotencyKey . ":item:0"
                ]);

                $character->consolidateStackableItems();

                $items[] = [
                    'id' => $existingItem->id,
                    'template_id' => $templateUlid,
                    'name' => $itemName,
                    'rarity' => $existingItem->rarity,
                    'quantity' => $quantity
                ];
                return $items;
            } else {
                $isFull = ($location === 'material_stash') ? $character->isMaterialStashFull() : $character->isBackpackFull();
                if ($isFull) {
                    Log::info("Storage full for character {$character->id}, cannot drop new item stack.", [
                        'template_id' => $templateUlid,
                        'location' => $location
                    ]);
                    return [];
                }

                $itemInstance = ItemInstance::create([
                    'id' => Str::ulid(),
                    'template_id' => $templateUlid,
                    'owner_character_id' => $character->id,
                    'location' => $location,
                    'stack_size' => $quantity,
                    'rarity' => 'common',
                    'roll_stats' => [],
                    'upgrade_level' => 0
                ]);

                $character->consolidateStackableItems();

                ItemLedger::create([
                    'id' => Str::ulid(),
                    'character_id' => $character->id,
                    'item_instance_id' => $itemInstance->id,
                    'action' => 'drop',
                    'ref_type' => 'encounter',
                    'ref_id' => $encounter->id,
                    'quantity_change' => $quantity,
                    'idempotency_key' => $idempotencyKey . ":item:0"
                ]);

                $items[] = [
                    'id' => $itemInstance->id,
                    'template_id' => $templateUlid,
                    'name' => $itemName,
                    'rarity' => $itemInstance->rarity,
                    'quantity' => $quantity
                ];
                return $items;
            }
        }

        if ($character->isBackpackFull()) {
            Log::info("Backpack full for character {$character->id}, cannot drop new unstackable item.", [
                'template_id' => $templateUlid
            ]);
            return [];
        }

        $isEquipment = $template && in_array($template->type, ['weapon', 'armor', 'accessory']);

        for ($i = 0; $i < $quantity; $i++) {
            $rolled = $isEquipment ? $this->statRoller->roll($template) : null;

            $itemInstance = ItemInstance::create([
                'id' => Str::ulid(),
                'template_id' => $templateUlid,
                'owner_character_id' => $character->id,
                'location' => 'inventory',
                'stack_size' => 1,
                'rarity' => $rolled['rarity'] ?? 'common',
                'roll_stats' => [],
                'rolled_stats' => $rolled['rolled_stats'] ?? null,
                'upgrade_level' => 0
            ]);

            // 5% chance to drop with enchantments
            if ($this->rng->int(1, 100) <= 5) {
                // 20% of enchanted drops have 2 enchants, 80% have 1
                $numEnchants = $this->rng->int(1, 100) <= 20 ? 2 : 1;
                $enchants = $this->enchantmentStrategy->generateMultipleRandomEnchantments($itemInstance, $numEnchants);
                foreach ($enchants as $type => $val) {
                    $itemInstance->addEnchantment($type, $val);
                }
                $itemInstance->save();
            }

            // Record in item ledger
            ItemLedger::create([
                'id' => Str::ulid(), // Ensure ItemLedger also gets a proper ULID
                'character_id' => $character->id,
                'item_instance_id' => $itemInstance->id,
                'action' => 'drop',
                'ref_type' => 'encounter',
                'ref_id' => $encounter->id,
                'quantity_change' => 1,
                'idempotency_key' => $idempotencyKey . ":item:{$i}"
            ]);

            $items[] = [
                'id' => $itemInstance->id,
                'template_id' => $templateUlid,
                'name' => $itemName,
                'rarity' => $itemInstance->rarity,
                'quantity' => 1
            ];
        }

        return $items;
    }

    private function updateEncounterResult(Encounter $encounter, DropResult $dropResult): void
    {
        $currentResult = $encounter->result ?? [];
        $currentResult['drops'] = $dropResult->toArray();

        $encounter->update(['result' => $currentResult]);
    }
}
