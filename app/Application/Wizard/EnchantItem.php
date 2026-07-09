<?php

namespace App\Application\Wizard;

use App\Domain\Wizard\EnchantmentStrategy;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\CurrencyLedger;
use App\Infrastructure\Persistence\Character;
use App\Application\Shared\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnchantItem
{
    private const COST_GOLD = 500;
    private const COST_GEMS = 5;

    public function __construct(private EnchantmentStrategy $strategy)
    {}

    public function execute(ItemInstance $item, Character $character, string $currencyType): Result
    {
        if ($item->owner_character_id !== $character->id) {
            return Result::error('ITEM_NOT_OWNED', 'Ten przedmiot nie należy do Ciebie.');
        }

        if (!$this->strategy->canEnchant($item)) {
            return Result::error('MAX_ENCHANTS_REACHED', 'Przedmiot posiada już maksymalną ilość bonusów.');
        }
        
        if (!in_array($currencyType, ['gold', 'gems'])) {
            return Result::error('INVALID_CURRENCY', 'Wybrano nieprawidłową walutę.');
        }

        $cost = $currencyType === 'gold' ? self::COST_GOLD : self::COST_GEMS;
        $idempotencyKey = "enchant:{$item->id}:" . Str::uuid();

        return DB::transaction(function () use ($item, $character, $currencyType, $cost, $idempotencyKey) {
            // Check currency
            $currentBalance = $currencyType === 'gold' ? $character->gold : $character->gems;

            if ($currentBalance < $cost) {
                return Result::error('INSUFFICIENT_FUNDS', "Nie masz wystarczającej ilości waluty ({$currencyType}). Koszt to {$cost}.");
            }

            // Deduct currency
            if ($currencyType === 'gold') {
                $character->gold -= $cost;
            } else {
                $character->gems -= $cost;
            }
            $character->save();

            CurrencyLedger::create([
                'id' => Str::ulid(),
                'character_id' => $character->id,
                'currency_type' => $currencyType,
                'amount' => -$cost,
                'balance_after' => $currentBalance - $cost,
                'idempotency_key' => $idempotencyKey . ':pay',
                'created_at' => now(),
            ]);

            // Try enchant
            if ($this->strategy->tryEnchant($item)) {
                $enchantment = $this->strategy->generateRandomEnchantment($item);
                $item->addEnchantment($enchantment['type'], $enchantment['value']);
                $item->save();
                
                return Result::ok([
                    'success' => true,
                    'message' => 'Udało się zakląć przedmiot!',
                    'enchantment' => $enchantment
                ]);
            }

            return Result::ok([
                'success' => false,
                'message' => 'Zaklinanie nie powiodło się, ale opłata została pobrana.',
            ]);
        });
    }
}
