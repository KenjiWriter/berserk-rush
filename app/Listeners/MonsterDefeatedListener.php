<?php

namespace App\Listeners;

use App\Domain\Collections\Events\MonsterDefeated;
use App\Application\Collections\CollectionService;

class MonsterDefeatedListener
{
    protected CollectionService $collectionService;

    public function __construct(CollectionService $collectionService)
    {
        $this->collectionService = $collectionService;
    }

    public function handle(MonsterDefeated $event): void
    {
        $this->collectionService->recordMonsterKill($event->character, $event->monster, $event->mapId);
    }
}
