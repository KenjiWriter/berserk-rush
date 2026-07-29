<?php

namespace App\Application\Characters;

use App\Infrastructure\Persistence\Character;
use App\Application\Shared\Result;
use App\Application\Characters\DTOs\LevelUpResult;
use App\Domain\Characters\Events\CharacterLeveledUp;
use Illuminate\Support\Facades\DB;

class LevelUpService
{
    public const MAX_LEVEL = 99;

    public function checkAndApply(Character $character): Result
    {
        try {
            return DB::transaction(function () use ($character) {
                $levelUps = [];
                $originalLevel = $character->level;
                $currentLevel = $character->level;
                $currentXp = $character->xp;

                $maxXpAtMaxLevel = max(0, $this->xpToNext(self::MAX_LEVEL) - 1);

                if ($currentLevel >= self::MAX_LEVEL) {
                    $currentLevel = self::MAX_LEVEL;
                    $currentXp = min($currentXp, $maxXpAtMaxLevel);

                    if ($character->level !== self::MAX_LEVEL || $character->xp !== $currentXp) {
                        $character->update([
                            'level' => self::MAX_LEVEL,
                            'xp' => $currentXp,
                        ]);
                    }

                    if ($character->user) {
                        $character->user->checkAndRepairTutorialStage($character);
                    }

                    return Result::ok(new LevelUpResult(
                        levelUps: [],
                        newLevel: self::MAX_LEVEL,
                        pointsGained: 0,
                        hadLevelUp: false
                    ));
                }

                // Check for level ups
                while ($currentLevel < self::MAX_LEVEL && $currentXp >= $this->xpToNext($currentLevel)) {
                    $currentXp -= $this->xpToNext($currentLevel);
                    $currentLevel++;

                    $levelUps[] = [
                        'from' => $currentLevel - 1,
                        'to' => $currentLevel
                    ];
                }

                if ($currentLevel >= self::MAX_LEVEL) {
                    $currentLevel = self::MAX_LEVEL;
                    $currentXp = min($currentXp, $maxXpAtMaxLevel);
                }

                // Automatyczna donacja EXP do gildii: gdy pasek doświadczenia
                // przekroczy 50%, nadwyżka ponad ten próg trafia do skarbca
                // gildii, a graczowi zostaje dokładnie połowa paska.
                $autoDonatedXp = 0;
                if ($currentLevel < self::MAX_LEVEL && $character->auto_donate_exp_guild && $character->guild_id) {
                    $donateThreshold = (int) floor($this->xpToNext($currentLevel) * 0.5);
                    if ($currentXp > $donateThreshold) {
                        $autoDonatedXp = $currentXp - $donateThreshold;
                        $currentXp = $donateThreshold;
                    }
                }

                if (!empty($levelUps) || $currentLevel !== $character->level || $currentXp !== $character->xp) {
                    $pointsGained = count($levelUps) * 3;

                    $character->update([
                        'level' => $currentLevel,
                        'character_points' => ($character->character_points ?? 0) + $pointsGained,
                        'skill_points' => ($character->skill_points ?? 0) + (count($levelUps) * 3),
                        'xp' => $currentXp,
                    ]);

                    $character->syncMissingPoints();

                    // Fire events for each level up
                    foreach ($levelUps as $levelUp) {
                        event(new CharacterLeveledUp(
                            $character->fresh(),
                            $levelUp['from'],
                            $levelUp['to']
                        ));
                    }
                }

                if ($autoDonatedXp > 0) {
                    $guild = \App\Models\Guild::find($character->guild_id);
                    if ($guild) {
                        $guild->addXp($autoDonatedXp);
                        \App\Models\GuildLog::create([
                            'guild_id' => $guild->id,
                            'character_id' => $character->id,
                            'action' => 'donate_exp_auto',
                            'amount' => $autoDonatedXp,
                        ]);
                    }
                }

                // Check and repair tutorial stage if character is level >= 5
                if ($character->user) {
                    $character->user->checkAndRepairTutorialStage($character);
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
        $base = 15 * pow($level, 2) + 50 * $level + 0.15 * pow($level, 4.1);
        if ($level > 85) {
            $base += 0.025 * pow($level - 85, 5.5);
        }
        return (int) round($base);
    }
}
