<?php

return [

    // Minimalny odstęp czasu (ms) między kolejnymi walkami tej samej postaci.
    // Wymuszany server-side w EncounterService::start(). Patrz docs/modules/combat.md §9.
    //
    // UWAGA: legalny auto-chain (MapStub) potrafi zejść do ~300ms między walkami
    // przy prędkości x5 po wygranej (capped w Livewire.on('auto-chain-next-battle'),
    // map-stub.blade.php). Od 2026-08-08 karta w tle faktycznie od razu kończy
    // walkę (component.call('finishAllTurns'), patrz docs/modules/combat.md §5)
    // zamiast tylko pomijać animację - więc realny odstęp między startami walk w
    // tle to praktycznie tylko ten sam ~300-700ms auto-chain delay. Próg musi być
    // wyraźnie NIŻSZY niż to, inaczej blokuje normalną, szybką grę - stąd 350ms,
    // a nie dokumentowane 1300ms. Jeśli auto-chain kiedyś przyspieszy poniżej
    // 300ms, próg trzeba będzie zrewidować - obecnie jest tuż nad tą wartością.
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
