<?php

return [

    // Minimalny odstęp czasu (ms) między kolejnymi walkami tej samej postaci.
    // Wymuszany server-side w EncounterService::start(). Patrz docs/modules/combat.md §9.
    //
    // UWAGA: legalny auto-chain (MapStub) potrafi zejść do ~400ms między walkami
    // przy prędkości x5 (autoChainTimeout capped do 400ms po wygranej - patrz
    // map-stub.blade.php ~linia 1681), a przy zminimalizowanej/tła karcie animacja
    // turów jest pomijana (finishAllTurns) więc realny odstęp to praktycznie tylko
    // ten sam 400-700ms auto-chain delay. Próg musi być wyraźnie NIŻSZY niż to,
    // inaczej blokuje normalną, szybką grę - stąd 350ms, a nie dokumentowane 1300ms.
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
