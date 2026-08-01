<?php

namespace App\Application\Pets;

use App\Infrastructure\Persistence\PetTemplate;

/**
 * Wybiera nazwę i ikonę dla nowego peta (wyklutego lub powstałego z fuzji) z
 * puli gatunków zdefiniowanych przez admina (`PetTemplate`) dla danego tieru
 * - to co admin skonfiguruje w panelu "Zarządzanie Zwierzakami" faktycznie
 * pojawia się w grze. Jeśli dla tieru nie zdefiniowano żadnego gatunku,
 * spada na wbudowaną, sparowaną nazwę+ikonę (nigdy losowane osobno - stąd
 * brak niedopasowań typu "Golem" z ikoną smoka).
 */
class PetSpeciesPicker
{
    public function pick(int $tier): array
    {
        $template = PetTemplate::where('tier', $tier)->inRandomOrder()->first();
        if ($template) {
            return [$template->name, $template->icon ?: 'paw'];
        }

        return match ($tier) {
            6 => ['Złoty Smok', 'dragon'],
            5 => ['Mroczny Feniks', 'fire'],
            4 => ['Dziki Tygrys', 'cat'],
            3 => ['Tajemniczy Golem', 'chess-rook'],
            2 => ['Szybki Wilk', 'dog'],
            default => ['Mały Duch', 'ghost'],
        };
    }
}
