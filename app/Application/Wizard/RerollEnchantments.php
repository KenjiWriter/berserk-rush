<?php

namespace App\Application\Wizard;

use App\Domain\Wizard\EnchantmentStrategy;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\CurrencyLedger;
use App\Infrastructure\Persistence\Character;
use App\Application\Shared\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RerollEnchantments
{
    private const COST_GOLD_PER_ENCHANT = 200;
    private const COST_GEMS_PER_ENCHANT = 2;

    public function __construct(private EnchantmentStrategy $strategy)
    {}

    public function execute(ItemInstance $item, Character $character, string $currencyType): Result
    {
        if ($item->owner_character_id !== $character->id) {
            return Result::error('ITEM_NOT_OWNED', 'Ten przedmiot nie należy do Ciebie.');
        }

        $currentEnchants = count($item->getEnchantments());
        if ($currentEnchants === 0) {
            return Result::error('NO_ENCHANTMENTS', 'Przedmiot nie posiada żadnych bonusów do zresetowania.');
        }
        
        if (!in_array($currencyType, ['gold', 'gems'])) {
            return Result::error('INVALID_CURRENCY', 'Wybrano nieprawidłową walutę.');
        }

        // Cost scales with amount of enchantments rerolled
        $cost = $currencyType === 'gold' 
            ? self::COST_GOLD_PER_ENCHANT * $currentEnchants 
            : self::COST_GEMS_PER_ENCHANT * $currentEnchants;

        $idempotencyKey = "reroll:{$item->id}:" . Str::uuid();

        return DB::transaction(function () use ($item, $character, $currencyType, $cost, $currentEnchants, $idempotencyKey) {
            $currentBalance = CurrencyLedger::where('character_id', $character->id)
                ->where('currency_type', $currencyType)
                ->orderBy('created_at', 'desc')
                ->lockForUpdate()
                ->value('balance_after') ?? 0;

            if ($currentBalance < $cost) {
                return Result::error('INSUFFICIENT_FUNDS', "Nie masz wystarczającej ilości waluty ({$currencyType}). Koszt to {$cost}.");
            }

            CurrencyLedger::create([
                'id' => Str::ulid(),
                'character_id' => $character->id,
                'currency_type' => $currencyType,
                'amount' => -$cost,
                'balance_after' => $currentBalance - $cost,
                'idempotency_key' => $idempotencyKey . ':pay',
                'created_at' => now(),
            ]);

            // Clear old enchantments
            $item->clearEnchantments();
            
            // Generate new ones (100% chance for existing slots)
            $newEnchants = $this->strategy->generateMultipleRandomEnchantments($item, $currentEnchants);
            
            foreach ($newEnchants as $type => $value) {
                $item->addEnchantment($type, $value);
            }
            
            $item->save();
            
            return Result::ok([
                'success' => true,
                'message' => 'Bonusy zostały pomyślnie zmienione.',
                'enchantments' => $newEnchants
            ]);
        });
    }
}
