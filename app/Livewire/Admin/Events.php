<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Application\Events\WeekendEventService;
use App\Infrastructure\Persistence\GameEvent;

class Events extends Component
{
    public string $selectedEventKey = GameEvent::EVENT_GOLD_2X;
    public int $durationHours = 48;

    public function render(WeekendEventService $eventService)
    {
        $activeEvent = $eventService->getActiveEvent();
        $availableEvents = GameEvent::$availableEvents;

        $globalConfig = GameEvent::where('event_key', 'GLOBAL_CONFIG')->first();
        $currentMode = $activeEvent['mode'] ?? ($globalConfig ? $globalConfig->mode : 'auto');

        return view('livewire.admin.events', [
            'activeEvent' => $activeEvent,
            'availableEvents' => $availableEvents,
            'currentMode' => $currentMode,
            'isWeekend' => $eventService->isWeekend(),
        ]);
    }

    public function activateManualEvent(string $key, WeekendEventService $eventService)
    {
        if (!isset(GameEvent::$availableEvents[$key])) {
            session()->flash('error', 'Niepoprawny klucz eventu.');
            return;
        }

        $eventService->setManualEvent($key, $this->durationHours);
        session()->flash('message', "Event został pomyślnie aktywowany ręcznie na czas: {$this->durationHours} godz.!");
    }

    public function activateAutoMode(WeekendEventService $eventService)
    {
        $eventService->setAutoMode();
        session()->flash('message', 'Włączono automatyczny tryb weekendowy. System wylosuje event w każdy weekend!');
    }

    public function activateForceAutoMode(WeekendEventService $eventService)
    {
        $eventService->setForceAutoMode();
        session()->flash('message', 'Wymuszono automatyczny event (tryb testowy/nagły event).');
    }

    public function disableAllEvents(WeekendEventService $eventService)
    {
        $eventService->disableEvents();
        session()->flash('message', 'Wszystkie eventy globalne zostały wyłączone.');
    }
}
