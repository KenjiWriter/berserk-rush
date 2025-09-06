<?php

namespace App\Application\Characters;

use App\Infrastructure\Persistence\Character;
use App\Application\Shared\Result;
use App\Application\Characters\DTOs\LevelUpResult;
use App\Domain\Characters\Events\CharacterLeveledUp;
use Illuminate\Support\Facades\DB;

class LevelUpService
{
    public function checkAndApply(Character $character): Result
    {
        try {
            return DB::transaction(function () use ($character) {
                $levelUps = [];
                $originalLevel = $character->level;
                $currentLevel = $character->level;
                $currentXp = $character->xp;

                // Check for level ups
                while ($currentXp >= $this->xpToNext($currentLevel)) {
                    $currentXp -= $this->xpToNext($currentLevel);
                    $currentLevel++;

                    $levelUps[] = [
                        'from' => $currentLevel - 1,
                        'to' => $currentLevel
                    ];
                }

                if (!empty($levelUps)) {
                    $pointsGained = count($levelUps) * 3;

                    $character->update([
                        'level' => $currentLevel,
                        'character_points' => $character->character_points + $pointsGained,
                    ]);

                    // Fire events for each level up
                    foreach ($levelUps as $levelUp) {
                        event(new CharacterLeveledUp(
                            $character->fresh(),
                            $levelUp['from'],
                            $levelUp['to']
                        ));
                    }
                }

                $result = new LevelUpResult(
                    levelUps: $levelUps,
                    newLevel: $currentLevel,
                    pointsGained: empty($levelUps) ? 0 : count($levelUps) * 3,
                    hadLevelUp: !empty($levelUps)
                );

                return Result::ok($result);
            });
        } catch (\Exception $e) {
            return Result::error('LEVEL_UP_FAILED', 'Sprawdzanie awansu nie powiodło się', [
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function xpToNext(int $level): int
    {
        return (int) round(50 * pow(1.25, $level - 1));
    }
}
