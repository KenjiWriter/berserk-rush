<?php

namespace App\Application\Pets;

use App\Infrastructure\Persistence\PetTemplate;

/**
 * Wybiera nazwę, ikonę i archetyp (Atakujący/Obrony/Wspomagający) dla nowego
 * peta (wyklutego lub powstałego z fuzji) z puli gatunków zdefiniowanych
 * przez admina (`PetTemplate`) dla danego tieru - to co admin skonfiguruje w
 * panelu "Zarządzanie Zwierzakami" faktycznie pojawia się w grze. Jeśli dla
 * tieru nie zdefiniowano żadnego gatunku, spada na wbudowany, sparowany
 * fallback (nigdy losowane osobno - stąd brak niedopasowań typu "Golem" z
 * ikoną smoka).
 */
class PetSpeciesPicker
{
    /**
     * @return array{0: string, 1: string, 2: ?string} [nazwa, ikona, archetyp]
     */
    public function pick(int $tier): array
    {
        $template = PetTemplate::where('tier', $tier)->inRandomOrder()->first();
        if ($template) {
            return [$template->name, $template->icon ?: 'paw', $template->archetype];
        }

        return match ($tier) {
            6 => ['Złoty Smok', 'pet_dragon', 'attacker'],
            5 => ['Mroczny Feniks', 'pet_phoenix', 'support'],
            4 => ['Dziki Tygrys', 'pet_tiger', 'attacker'],
            3 => ['Tajemniczy Golem', 'pet_golem', 'defense'],
            2 => ['Szybki Wilk', 'pet_wolf', 'attacker'],
            default => ['Mały Duch', 'pet_spirit', 'support'],
        };
    }
}
