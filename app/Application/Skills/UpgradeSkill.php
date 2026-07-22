<?php

namespace App\Application\Skills;

use App\Application\Shared\Result;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CharacterCombatSkill;
use Illuminate\Support\Facades\DB;

class UpgradeSkill
{
    public function execute(Character $character, CharacterCombatSkill $charSkill): Result
    {
        try {
            return DB::transaction(function () use ($character, $charSkill) {
                if ($charSkill->character_id !== $character->id) {
                    return Result::error('UNAUTHORIZED', 'Nie posiadasz tej umiejętności.');
                }

                // Koszt upgrade'u: np. 1 punkt za każdy poziom (lub bazowy koszt)
                // Ustalmy że upgrade kosztuje zawsze 1 punkt, albo rośnie z levelem.
                // Propozycja: koszt = level + 1, albo stałe 2 punkty? Zróbmy stałe 1 punkt dla prostoty.
                $cost = 1;

                if ($character->skill_points < $cost) {
                    return Result::error('NOT_ENOUGH_POINTS', "Brak punktów umiejętności. Wymagane: {$cost}");
                }

                $character->decrement('skill_points', $cost);
                $charSkill->increment('level');

                return Result::ok(null);
            });
        } catch (\Exception $e) {
            return Result::error('UPGRADE_FAILED', 'Nie udało się ulepszyć umiejętności.');
        }
    }
}
