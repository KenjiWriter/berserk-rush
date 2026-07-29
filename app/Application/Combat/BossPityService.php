<?php

namespace App\Application\Combat;

use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CharacterMapBossPity;

/**
 * "Pity timer" na pojawienie się bossa na mapie (ustalone z użytkownikiem,
 * 2026-07-29): boss ma bazowo 5% szansy na wylosowanie jako przeciwnik w
 * zwykłym starciu 1 na 1, a każde zwycięstwo nad NIE-bossem na tej mapie
 * podnosi tę szansę o +0.5% (liniowo, capowane na 100%). Licznik resetuje się
 * do 0 (czyli z powrotem do 5%) w momencie, gdy boss faktycznie się pojawi
 * jako przeciwnik - niezależnie od wyniku tamtej walki. Stan trzymany per
 * postać+mapa (character_map_boss_pity) - patrz komentarz w EncounterService
 * przy miejscu użycia, dlaczego per-mapa a nie globalnie na postać.
 */
class BossPityService
{
    private const BASE_CHANCE_PERCENT = 5.0;
    private const INCREMENT_PERCENT_PER_KILL = 0.5;
    private const MAX_CHANCE_PERCENT = 100.0;

    public function currentChancePercent(Character $character, int $mapId): float
    {
        $counter = $this->getRecord($character, $mapId)->kills_since_boss;

        return min(self::MAX_CHANCE_PERCENT, self::BASE_CHANCE_PERCENT + $counter * self::INCREMENT_PERCENT_PER_KILL);
    }

    public function rollBoss(Character $character, int $mapId): bool
    {
        $chance = $this->currentChancePercent($character, $mapId);

        return (mt_rand(1, 1000000) / 1000000 * 100) <= $chance;
    }

    public function recordBossAppeared(Character $character, int $mapId): void
    {
        $this->getRecord($character, $mapId)->update(['kills_since_boss' => 0]);
    }

    public function recordNonBossVictory(Character $character, int $mapId): void
    {
        $this->getRecord($character, $mapId)->increment('kills_since_boss');
    }

    private function getRecord(Character $character, int $mapId): CharacterMapBossPity
    {
        return CharacterMapBossPity::firstOrCreate(
            ['character_id' => $character->id, 'map_id' => $mapId],
            ['kills_since_boss' => 0]
        );
    }
}
