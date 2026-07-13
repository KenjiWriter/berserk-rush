<?php

namespace App\Application\Combat;

use App\Infrastructure\Persistence\Character;

class RewardMultiplierService
{
    /**
     * Zwraca całkowity mnożnik zdobywanego EXP.
     * Przykładowo 1.25 oznacza +25% EXP.
     */
    public function getExpMultiplier(Character $character): float
    {
        $multiplier = 1.0;

        // 1. Aktywne Buffy Postaci (z mikstur, zwojów)
        $buffBonus = 0;
        foreach ($character->getActiveBuffs() as $buff) {
            $effects = $buff->effects ?? [];
            if (isset($effects['exp_bonus'])) {
                // e.g. exp_bonus = 20 -> 0.20
                $buffBonus += ($effects['exp_bonus'] / 100);
            }
        }
        $multiplier += $buffBonus;

        // 2. Pasywny Bonus Gildii
        if ($character->guild_id && $character->guild) {
            $guildBonus = $character->guild->bonus_xp_level * 0.01; // 1% per level
            $multiplier += $guildBonus;
        }

        // 3. Status Premium
        if ($character->user && $character->user->hasPremium()) {
            $multiplier += 0.20; // +20% za premium
        }

        // 4. Globalny Event (Placeholder na przyszłość)
        /*
        if (config('game.events.global_exp_multiplier') > 1.0) {
            // Mnożnik eventowy może mnożyć całość
            $multiplier *= config('game.events.global_exp_multiplier');
        }
        */

        return $multiplier;
    }

    /**
     * Zwraca całkowity mnożnik zdobywanego Złota.
     * Przykładowo 1.10 oznacza +10% Złota.
     */
    public function getGoldMultiplier(Character $character): float
    {
        $multiplier = 1.0;

        // 1. Pasywny Bonus Gildii
        if ($character->guild_id && $character->guild) {
            $guildBonus = $character->guild->bonus_gold_level * 0.01; // 1% per level
            $multiplier += $guildBonus;
        }

        // 2. Buffy Postaci (jeśli w przyszłości dodamy mikstury złota)
        $buffBonus = 0;
        foreach ($character->getActiveBuffs() as $buff) {
            $effects = $buff->effects ?? [];
            if (isset($effects['gold_bonus'])) {
                $buffBonus += ($effects['gold_bonus'] / 100);
            }
        }
        $multiplier += $buffBonus;

        // 3. Status Premium
        if ($character->user && $character->user->hasPremium()) {
            $multiplier += 0.20; // +20% złota za premium
        }

        // 4. Globalny Event
        /*
        if (config('game.events.global_gold_multiplier') > 1.0) {
            $multiplier *= config('game.events.global_gold_multiplier');
        }
        */

        return $multiplier;
    }
}
