<?php

namespace App\Application\Skills;

use App\Application\Shared\Result;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CharacterCombatSkill;
use Illuminate\Support\Facades\DB;

class EquipSkill
{
    public function execute(Character $character, CharacterCombatSkill $charSkill, int $slot): Result
    {
        try {
            return DB::transaction(function () use ($character, $charSkill, $slot) {
                if ($charSkill->character_id !== $character->id) {
                    return Result::error('UNAUTHORIZED', 'Nie posiadasz tej umiejętności.');
                }

                if ($slot < 1 || $slot > 3) {
                    return Result::error('INVALID_SLOT', 'Nieprawidłowy slot.');
                }

                // Odłączamy skill, który obecnie zajmuje ten slot
                CharacterCombatSkill::where('character_id', $character->id)
                    ->where('equip_slot', $slot)
                    ->update(['is_equipped' => false, 'equip_slot' => null]);

                // Przypisujemy nasz skill do slota
                $charSkill->update([
                    'is_equipped' => true,
                    'equip_slot' => $slot
                ]);

                return Result::ok(null);
            });
        } catch (\Exception $e) {
            return Result::error('EQUIP_FAILED', 'Nie udało się wyposażyć umiejętności.');
        }
    }

    public function unequip(Character $character, CharacterCombatSkill $charSkill): Result
    {
        try {
            if ($charSkill->character_id !== $character->id) {
                return Result::error('UNAUTHORIZED', 'Nie posiadasz tej umiejętności.');
            }

            $charSkill->update([
                'is_equipped' => false,
                'equip_slot' => null
            ]);

            return Result::ok(null);
        } catch (\Exception $e) {
            return Result::error('UNEQUIP_FAILED', 'Nie udało się zdjąć umiejętności.');
        }
    }
}
