<?php

return [

    // Minimalny odstęp czasu (ms) między kolejnymi walkami tej samej postaci.
    // Wymuszany server-side w EncounterService::start(). Patrz docs/modules/combat.md §9.
    'min_encounter_interval_ms' => env('ANTICHEAT_MIN_ENCOUNTER_INTERVAL_MS', 1300),

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
