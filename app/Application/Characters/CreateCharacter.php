<?php

namespace App\Application\Characters;

use App\Infrastructure\Persistence\Character;
use App\Models\User;
use App\Application\Shared\Result;
use Illuminate\Support\Facades\DB;

class CreateCharacter
{
    public function handle(
        User $user,
        string $name,
        int $str,
        int $int,
        int $vit,
        int $agi,
        ?string $avatar = null,
        ?string $idempotencyKey = null
    ): Result {
        // Check if user already has max characters
        if ($user->hasMaxCharacters()) {
            return Result::error('MAX_CHARACTERS', 'Osiągnięto limit 4 postaci na konto.');
        }

        // Validate stat allocation
        $totalPoints = $str + $int + $vit + $agi;
        if ($totalPoints !== 10) {
            return Result::error('INVALID_STATS', 'Suma atrybutów musi wynosić dokładnie 10 punktów.');
        }

        // Validate individual stats
        if ($str < 0 || $int < 0 || $vit < 0 || $agi < 0) {
            return Result::error('NEGATIVE_STATS', 'Atrybuty nie mogą być ujemne.');
        }

        if ($str > 10 || $int > 10 || $vit > 10 || $agi > 10) {
            return Result::error('STATS_TOO_HIGH', 'Pojedynczy atrybut nie może przekroczyć 10 punktów.');
        }

        // Check name uniqueness
        if (Character::where('name', $name)->exists()) {
            return Result::error('NAME_TAKEN', 'Ta nazwa postaci jest już zajęta.');
        }

        try {
            return DB::transaction(function () use ($user, $name, $str, $int, $vit, $agi, $avatar) {
                // Double-check character limit inside transaction
                if ($user->characters()->count() >= 4) {
                    return Result::error('MAX_CHARACTERS', 'Osiągnięto limit 4 postaci na konto.');
                }

                $character = Character::create([
                    'user_id' => $user->id,
                    'name' => $name,
                    'level' => 1,
                    'xp' => 0,
                    'gold' => 0,
                    'gems' => 0,
                    'attributes' => [
                        'str' => $str,
                        'int' => $int,
                        'vit' => $vit,
                        'agi' => $agi,
                    ],
                    'proficiencies' => [],
                    'avatar' => $avatar,
                    'version' => 1,
                ]);

                return Result::ok($character);
            });
        } catch (\Exception $e) {
            return Result::error('DATABASE_ERROR', 'Wystąpił błąd podczas tworzenia postaci.');
        }
    }
}
