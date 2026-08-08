<?php

return [

    // Minimalny odstęp czasu (ms) między kolejnymi walkami tej samej postaci.
    // Wymuszany server-side w EncounterService::start(). Patrz docs/modules/combat.md §9.
    //
    // UWAGA: legalny auto-chain (MapStub) czeka min. ~700ms między walkami po
    // wygranej (Livewire.on('auto-chain-next-battle'), map-stub.blade.php) -
    // prędkość x5, przy której to okno schodziło do ~300ms, została usunięta
    // (2026-08-08, zostały tylko x1/x2). Od 2026-08-08 karta w tle faktycznie
    // od razu kończy walkę (component.call('finishAllTurns'), patrz
    // docs/modules/combat.md §5) zamiast tylko pomijać animację - więc realny
    // odstęp między startami walk w tle to praktycznie tylko ten sam
    // ~700-3000ms auto-chain delay, wyraźnie nad progiem 350ms.
    'min_encounter_interval_ms' => env('ANTICHEAT_MIN_ENCOUNTER_INTERVAL_MS', 350),

    'kill_rate' => [
        'window_minutes'   => 3,
        // ~30/min - warte przejrzenia
        'medium_threshold' => 90,
        // ~43/min - praktycznie ciągłe uderzanie w min_encounter_interval_ms
        'high_threshold'   => 130,
        // nie twórz nowej flagi tego samego typu dla tej postaci częściej niż co tyle minut
        'dedup_minutes'    => 15,
    ],

];
