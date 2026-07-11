<?php

namespace App\Application\PvP;

class CalculateEloAction
{
    private const K_FACTOR = 32;

    public function execute(int $attackerElo, int $defenderElo, bool $attackerWon): array
    {
        $expectedAttacker = 1 / (1 + pow(10, ($defenderElo - $attackerElo) / 400));
        $expectedDefender = 1 / (1 + pow(10, ($attackerElo - $defenderElo) / 400));

        $actualAttacker = $attackerWon ? 1 : 0;
        $actualDefender = $attackerWon ? 0 : 1;

        $attackerChange = (int) round(self::K_FACTOR * ($actualAttacker - $expectedAttacker));
        $defenderChange = (int) round(self::K_FACTOR * ($actualDefender - $expectedDefender));

        return [
            'attacker_change' => $attackerChange,
            'defender_change' => $defenderChange,
        ];
    }
}
