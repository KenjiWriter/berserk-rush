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
        private RandomProvider $rng
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

                $entries = $lootTable->entries->toArray();
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
                            $this->applyCurrencyReward($encounter, 'gold', $gold, $idempotencyKey);
                            break;

                        case 'gems':
                            $gems = $quantity;
                            $this->applyCurrencyReward($encounter, 'gems', $gems, $idempotencyKey);
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
        // Get current balance for this character and currency type
        $currentBalance = CurrencyLedger::where('character_id', $encounter->character_id)
            ->where('currency_type', $currencyType)
            ->orderBy('created_at', 'desc')
            ->value('balance_after') ?? 0;

        // Calculate new balance
        $newBalance = $currentBalance + $amount;

        // Create the ledger entry with balance_after
        CurrencyLedger::create([
            'character_id' => $encounter->character_id,
            'currency_type' => $currencyType,
            'amount' => $amount,
            'balance_after' => $newBalance, // This was missing!
            'idempotency_key' => $idempotencyKey,
            'id' => $this->generateId(), // assuming you have this method
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

            // Record in item ledger
            ItemLedger::create([
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
                'name' => "Item {$templateUlid}", // Would be replaced with actual template name
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
