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

        $currentEnchants = $item->getEnchantments();
        if (empty($currentEnchants)) {
            return Result::error('NO_ENCHANTMENTS', 'Przedmiot nie posiada żadnych bonusów do zresetowania.');
        }

        if (!in_array($currencyType, ['gold', 'gems'])) {
            return Result::error('INVALID_CURRENCY', 'Wybrano nieprawidłową walutę.');
        }

        // Zablokowane bonusy (patrz ItemInstance::toggleEnchantLock()) zostają
        // nietknięte - przelosowaniu podlegają wyłącznie te odblokowane, a koszt
        // skaluje się z ich liczbą, nie z całkowitą liczbą bonusów na przedmiocie.
        $lockedTypes = $item->getEnchantLocks();
        $unlockedTypes = array_values(array_diff(array_keys($currentEnchants), $lockedTypes));
        $rerollCount = count($unlockedTypes);

        if ($rerollCount === 0) {
            return Result::error('ALL_LOCKED', 'Wszystkie bonusy są zablokowane - odblokuj przynajmniej jeden, żeby móc przelosować.');
        }

        // Cost scales with amount of (unlocked) enchantments actually rerolled
        $cost = $currencyType === 'gold'
            ? self::COST_GOLD_PER_ENCHANT * $rerollCount
            : self::COST_GEMS_PER_ENCHANT * $rerollCount;

        $idempotencyKey = "reroll:{$item->id}:" . Str::uuid();

        return DB::transaction(function () use ($item, $character, $currencyType, $cost, $currentEnchants, $lockedTypes, $unlockedTypes, $rerollCount, $idempotencyKey) {
            $currentBalance = $currencyType === 'gold' ? $character->gold : $character->user->gems;

            if ($currentBalance < $cost) {
                return Result::error('INSUFFICIENT_FUNDS', "Nie masz wystarczającej ilości waluty ({$currencyType}). Koszt to {$cost}.");
            }

            if ($currencyType === 'gold') {
                $character->gold -= $cost;
                $character->save();
            } else {
                $character->user->gems -= $cost;
                $character->user->save();
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

            // Nowe rzuty tylko dla odblokowanych slotów - pomijając typy zablokowanych
            // bonusów, żeby nie wylosować duplikatu tego, co i tak zostaje.
            $newEnchants = $this->strategy->generateMultipleRandomEnchantments($item, $rerollCount, $lockedTypes);

            // Zablokowane bonusy przechodzą 1:1 (wartość i typ bez zmian), reszta
            // zastępowana świeżym losowaniem. Locki (roll_stats['enchant_locks'])
            // zostają nietknięte przez clearEnchantments()+addEnchantment() poniżej -
            // ItemInstance::getEnchantLocks() odfiltrowuje typy, które i tak przestały
            // istnieć, więc nie trzeba ich tu ręcznie czyścić.
            $preserved = array_intersect_key($currentEnchants, array_flip($lockedTypes));

            $item->clearEnchantments();
            foreach ($preserved as $type => $value) {
                $item->addEnchantment($type, $value);
            }
            foreach ($newEnchants as $type => $value) {
                $item->addEnchantment($type, $value);
            }

            // clearEnchantments() zeruje też enchant_locks - odtwarzamy blokady dla
            // zachowanych typów, żeby gracz nie musiał zaznaczać ich ponownie.
            foreach ($lockedTypes as $type) {
                if (isset($preserved[$type])) {
                    $item->toggleEnchantLock($type);
                }
            }

            $item->save();

            return Result::ok([
                'success' => true,
                'message' => $rerollCount < count($currentEnchants)
                    ? "Przelosowano {$rerollCount} odblokowanych bonusów, zablokowane pozostały bez zmian."
                    : 'Bonusy zostały pomyślnie zmienione.',
                'enchantments' => array_merge($preserved, $newEnchants),
            ]);
        });
    }
}
