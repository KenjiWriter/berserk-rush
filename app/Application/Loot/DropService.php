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
        private \App\Domain\Wizard\EnchantmentStrategy $enchantmentStrategy
    ) {}

    public function rollAndApplyRewards(Encounter $encounter): Result
    {
        $idempotencyKey = "encounter:{$encounter->id}:drop";

        try {
            return DB::transaction(function () use ($encounter, $idempotencyKey) {
                // Check idempotency - don't apply drops twice
                if (CurrencyLedger::where('idempotency_key', $idempotencyKey)->exists()) {
                    Log::info('Drops already applied for encounter', ['encounter_id' => $encounter->id]);
                    return Result::ok(new DropResult(0, 0, [], [], false));
                }

                $monster = $encounter->monster;
                if (!$monster || !$monster->loot_table_id) {
                    Log::info('No loot table for monster', [
                        'monster_id' => $monster?->id,
                        'encounter_id' => $encounter->id
                    ]);
                    return Result::ok(new DropResult(0, 0, [], [], false));
                }

                $lootTable = $monster->lootTable;
                if (!$lootTable) {
                    return Result::ok(new DropResult(0, 0, [], [], false));
                }

                $activeQuestIds = $encounter->character->activeQuests()->pluck('quest_id')->toArray();

                $entriesCollection = $lootTable->entries()->with('itemTemplate')->get();
                
                $filteredEntries = $entriesCollection->filter(function($entry) use ($activeQuestIds) {
                    if (in_array($entry->reward_type, ['item', 'material'])) {
                        $template = $entry->itemTemplate;
                        if ($template && $template->type === 'quest_item') {
                            if (!$template->quest_id || !in_array($template->quest_id, $activeQuestIds)) {
                                return false; // Ukryj, jeśli nie ma tego questa aktywnego
                            }
                        }
                    }
                    return true;
                });

                $entries = array_values($filteredEntries->toArray());
                if (empty($entries)) {
                    return Result::ok(new DropResult(0, 0, [], [], false));
                }

                // Roll one loot entry
                $selectedEntry = $this->picker->pick($entries);

                $gold = 0;
                $gems = 0;
                $items = [];
                $materials = [];

                if ($selectedEntry) {
                    $quantity = $this->rng->int($selectedEntry['min_qty'], $selectedEntry['max_qty']);

                    switch ($selectedEntry['reward_type']) {
                        case 'gold':
                            $gold = $quantity;
                            $this->applyCurrencyReward($encounter, 'gold', $gold, "{$idempotencyKey}:gold");
                            break;

                        case 'gems':
                            $gems = $quantity;
                            $this->applyCurrencyReward($encounter, 'gems', $gems, "{$idempotencyKey}:gems");
                            break;

                        case 'item':
                        case 'material':
                            $itemResult = $this->applyItemReward(
                                $encounter,
                                $selectedEntry['ref_ulid'],
                                $quantity,
                                $idempotencyKey
                            );

                            if ($selectedEntry['reward_type'] === 'item') {
                                $items = $itemResult;
                            } else {
                                $materials = $itemResult;
                            }
                            break;
                    }

                    Log::info('Loot rolled and applied', [
                        'encounter_id' => $encounter->id,
                        'reward_type' => $selectedEntry['reward_type'],
                        'quantity' => $quantity,
                        'gold' => $gold,
                        'gems' => $gems,
                        'items_count' => count($items),
                        'materials_count' => count($materials)
                    ]);
                }

                $dropResult = new DropResult($gold, $gems, $items, $materials, true);

                // Update encounter result with drop summary
                $this->updateEncounterResult($encounter, $dropResult);

                return Result::ok($dropResult);
            });
        } catch (\Exception $e) {
            Log::error('Failed to apply loot drops', [
                'encounter_id' => $encounter->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Result::error('DROP_FAILED', 'Nie udało się zastosować łupu', [
                'exception' => $e->getMessage()
            ]);
        }
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
        
        // Stackable items logic
        if ($template && in_array($template->type, ['material', 'consumable', 'currency'])) {
            $existingItem = ItemInstance::where('owner_character_id', $character->id)
                ->where('template_id', $templateUlid)
                ->where('location', 'inventory')
                ->first();

            if ($existingItem) {
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

                $items[] = [
                    'id' => $existingItem->id,
                    'template_id' => $templateUlid,
                    'name' => $itemName,
                    'rarity' => $existingItem->rarity,
                    'quantity' => $quantity
                ];
                return $items;
            } else {
                $itemInstance = ItemInstance::create([
                    'id' => Str::ulid(),
                    'template_id' => $templateUlid,
                    'owner_character_id' => $character->id,
                    'location' => 'inventory',
                    'stack_size' => $quantity,
                    'rarity' => 'common',
                    'roll_stats' => [],
                    'upgrade_level' => 0
                ]);

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

        for ($i = 0; $i < $quantity; $i++) {
            $itemInstance = ItemInstance::create([
                'id' => Str::ulid(),
                'template_id' => $templateUlid,
                'owner_character_id' => $character->id,
                'location' => 'inventory',
                'stack_size' => 1,
                'rarity' => 'common', // Default rarity, could be enhanced later
                'roll_stats' => [],
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
