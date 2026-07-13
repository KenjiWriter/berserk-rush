<?php

namespace App\Listeners;

use App\Domain\Collections\Events\ItemDiscovered;
use App\Application\Collections\CollectionService;

class ItemDiscoveredListener
{
    protected CollectionService $collectionService;

    public function __construct(CollectionService $collectionService)
    {
        $this->collectionService = $collectionService;
    }

    public function handle(ItemDiscovered $event): void
    {
        $this->collectionService->recordItemDiscovered($event->character, $event->itemTemplateId);
    }
}
