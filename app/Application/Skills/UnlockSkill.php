<?php

namespace App\Application\Skills;

use App\Application\Shared\Result;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CombatSkill;
use App\Infrastructure\Persistence\CharacterCombatSkill;
use Illuminate\Support\Facades\DB;

class UnlockSkill
{
    public function execute(Character $character, CombatSkill $skill): Result
    {
        try {
            return DB::transaction(function () use ($character, $skill) {
                // Check if already unlocked
                $existing = CharacterCombatSkill::where('character_id', $character->id)
                    ->where('combat_skill_id', $skill->id)
                    ->first();

                if ($existing) {
                    return Result::error('ALREADY_UNLOCKED', 'Ten skill został już odblokowany.');
                }

                // Check requirements
                if ($character->level < $skill->required_level) {
                    return Result::error('LEVEL_TOO_LOW', "Wymagany poziom: {$skill->required_level}");
                }

                if ($character->skill_points < $skill->unlock_cost) {
                    return Result::error('NOT_ENOUGH_POINTS', "Brak punktów umiejętności. Wymagane: {$skill->unlock_cost}");
                }

                // Deduct points
                $character->decrement('skill_points', $skill->unlock_cost);

                // Create
                CharacterCombatSkill::create([
                    'character_id' => $character->id,
                    'combat_skill_id' => $skill->id,
                    'level' => 1,
                    'is_equipped' => false,
                ]);

                return Result::ok(null);
            });
        } catch (\Exception $e) {
            return Result::error('UNLOCK_FAILED', 'Nie udało się odblokować umiejętności.');
        }
    }
}
