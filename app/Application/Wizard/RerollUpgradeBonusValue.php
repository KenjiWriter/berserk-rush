<?php

namespace App\Application\Wizard;

use App\Application\Shared\Result;
use App\Domain\Wizard\EnchantmentStrategy;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CurrencyLedger;
use App\Infrastructure\Persistence\ItemInstance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RerollUpgradeBonusValue
{
    public const COST_GOLD = 200;
    public const COST_GEMS = 2;

    public function __construct(private EnchantmentStrategy $strategy)
    {
    }

    public function execute(ItemInstance $item, Character $character, string $currencyType): Result
    {
        if ($item->owner_character_id !== $character->id) {
            return Result::error('UNAUTHORIZED', 'Przedmiot nie należy do Twojej postaci.');
        }

        $upgradeBonuses = $item->getUpgradeBonuses();
        if (empty($upgradeBonuses)) {
            return Result::error('NO_UPGRADE_BONUS', 'Przedmiot nie posiada bonusu z Kuźni (+3).');
        }

        $cost = $currencyType === 'gold' ? self::COST_GOLD : self::COST_GEMS;
        $idempotencyKey = "reroll_upgrade_bonus:{$item->id}:" . Str::uuid();

        return DB::transaction(function () use ($item, $character, $currencyType, $cost, $idempotencyKey) {
            $currentBalance = $currencyType === 'gold' ? $character->gold : ($character->user->gems ?? 0);

            if ($currencyType === 'gold') {
                if ($character->gold < $cost) {
                    return Result::error('INSUFFICIENT_GOLD', "Brak wystarczającej ilości złota (wymagane {$cost} złota).");
                }
                $character->gold -= $cost;
                $character->save();
            } else {
                $user = $character->user;
                if (!$user || $user->gems < $cost) {
                    return Result::error('INSUFFICIENT_GEMS', "Brak wystarczającej ilości klejnotów (wymagane {$cost} gemów).");
                }
                $user->gems -= $cost;
                $user->save();
            }

            CurrencyLedger::create([
                'id' => Str::ulid(),
                'character_id' => $character->id,
                'currency_type' => $currencyType,
                'amount' => -$cost,
                'balance_after' => $currentBalance - $cost,
                'source_type' => 'wizard',
                'idempotency_key' => $idempotencyKey . ':pay',
                'created_at' => now(),
            ]);

            $rolled = $this->strategy->rerollUpgradeBonusValue($item);
            $item->save();

            $bonusLabel = EnchantmentStrategy::bonusLabel($rolled['type']);
            $valStr = ($rolled['value'] > 0 ? '+' : '') . $rolled['value'];

            return Result::ok([
                'success' => true,
                'message' => "Przelosowano wartość bonusu z Kuźni (+3): {$bonusLabel} ({$valStr}).",
                'bonus' => $rolled,
            ]);
        });
    }
}
