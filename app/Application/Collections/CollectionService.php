<?php

namespace App\Application\Collections;

use App\Infrastructure\Persistence\CharacterBestiary;
use App\Infrastructure\Persistence\CharacterPokedex;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Monster;

class CollectionService
{
    /**
     * Rejestruje pokonanie potwora w bestiariuszu.
     */
    public function recordMonsterKill(Character $character, Monster $monster, string $mapId): void
    {
        $bestiary = CharacterBestiary::firstOrCreate(
            ['character_id' => $character->id, 'monster_id' => $monster->id],
            ['kills' => 0]
        );

        $bestiary->increment('kills');
        
        $context = [
            'monster_id' => $monster->id,
            'map_id' => $mapId,
            'monster_type' => $monster->type?->value,
            'monster_rank' => $monster->rank?->value
        ];

        // Dodatkowo wywołujemy progress achievementu dla zabijania potworów
        event(new \App\Domain\Achievements\Events\AchievementProgressed($character, 'monsters_killed', 1, $context));
        event(new \App\Domain\Achievements\Events\AchievementProgressed($character, 'monster_killed_' . $monster->id, 1, $context));
    }

    /**
     * Rejestruje zdobycie nowego przedmiotu w Pokedexie.
     */
    public function recordItemDiscovered(Character $character, string $itemTemplateId): void
    {
        $pokedex = CharacterPokedex::firstOrCreate(
            ['character_id' => $character->id, 'item_template_id' => $itemTemplateId]
        );

        if ($pokedex->wasRecentlyCreated) {
            // Pierwsze zdobycie tego przedmiotu
            event(new \App\Domain\Achievements\Events\AchievementProgressed($character, 'items_discovered'));
        }
    }
}
