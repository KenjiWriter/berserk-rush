<?php

namespace App\Livewire\Global;

use Livewire\Component;
use App\Application\Events\WeekendEventService;

class WeekendEventBanner extends Component
{
    public function render(WeekendEventService $eventService)
    {
        $activeEvent = $eventService->getActiveEvent();

        return view('livewire.global.weekend-event-banner', [
            'activeEvent' => $activeEvent,
        ]);
    }
}
