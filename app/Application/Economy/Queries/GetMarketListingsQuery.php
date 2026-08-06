<?php

namespace App\Application\Economy\Queries;

use App\Infrastructure\Persistence\MarketListing;

class GetMarketListingsQuery
{
    /**
     * Klucze statystyk bonusowych dostępne do filtrowania (checklista na rynku).
     * Białą listę stosujemy, by bezpiecznie osadzić klucz w wyrażeniu JSON (whereRaw).
     */
    public const ALLOWED_STAT_FILTERS = [
        'attack_min', 'attack_max', 'magic_attack_min', 'magic_attack_max',
        'defense', 'hp_bonus', 'mana_bonus',
        'str_bonus', 'agi_bonus', 'int_bonus', 'vit_bonus',
        'crit_chance', 'dodge_chance',
        'magic_burst_min', 'magic_burst_max', 'magic_burst_chance',
    ];

    /**
     * Buduje przenośne (MySQL/PostgreSQL) wyrażenie SQL wyciągające liczbową wartość
     * klucza z kolumny JSON(B), zwracające 0 gdy klucz nie istnieje.
     */
    private function jsonStatExpr(string $connection, string $column, string $key): string
    {
        return $connection === 'pgsql'
            ? "COALESCE(({$column}->>'{$key}')::numeric, 0)"
            : "COALESCE(CAST({$column}->>'\$.{$key}' AS DECIMAL(20,4)), 0)";
    }

    /**
     * Jak jsonStatExpr, ale bezpieczna dla item_templates.base_stats, gdzie statystyki
     * zakresowe (np. hp_bonus) trzymane są jako tablica [min, max] zamiast liczby
     * (patrz ItemStatRoller). Rzutowanie takiej tablicy wprost na numeric wywala
     * zapytanie (Postgres: "invalid input syntax for type numeric: [33,57]"),
     * więc sprawdzamy typ wartości i dla tablicy bierzemy jej maksimum (indeks 1).
     */
    private function jsonRangeStatExpr(string $connection, string $column, string $key): string
    {
        if ($connection === 'pgsql') {
            return "COALESCE(CASE json_typeof(({$column}->'{$key}'))"
                . " WHEN 'array' THEN ({$column}->'{$key}'->>1)::numeric"
                . " WHEN 'number' THEN ({$column}->>'{$key}')::numeric"
                . " ELSE 0 END, 0)";
        }

        // UPPER() bo SQLite (używane w testach) zwraca małe litery ('array'),
        // a MySQL wielkie ('ARRAY') - ujednolicamy porównanie.
        return "COALESCE(CASE UPPER(JSON_TYPE(JSON_EXTRACT({$column}, '\$.{$key}')))"
            . " WHEN 'ARRAY' THEN CAST(JSON_EXTRACT({$column}, '\$.{$key}[1]') AS DECIMAL(20,4))"
            . " WHEN 'INTEGER' THEN CAST(JSON_EXTRACT({$column}, '\$.{$key}') AS DECIMAL(20,4))"
            . " WHEN 'DOUBLE' THEN CAST(JSON_EXTRACT({$column}, '\$.{$key}') AS DECIMAL(20,4))"
            . " WHEN 'REAL' THEN CAST(JSON_EXTRACT({$column}, '\$.{$key}') AS DECIMAL(20,4))"
            . " ELSE 0 END, 0)";
    }

    public function execute(array $filters = [], string $sortBy = 'created_at', string $sortDir = 'desc', int $perPage = 20)
    {
        if (($filters['type'] ?? 'item') === 'pet') {
            return $this->executeForPets($filters, $sortBy, $sortDir, $perPage);
        }

        $query = MarketListing::query()
            ->where('status', 'active')
            ->has('item')
            ->with(['item.template', 'seller']);

        // Search by item name
        if (!empty($filters['search'])) {
            $search = mb_strtolower(trim($filters['search']), 'UTF-8');
            $query->whereHas('item.template', function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . $search . '%']);
            });
        }

        // Filter by rarity
        if (!empty($filters['rarity'])) {
            $query->whereHas('item', function ($q) use ($filters) {
                $q->where('rarity', $filters['rarity']);
            });
        }

        // Filter by level range
        if (isset($filters['min_level'])) {
            $query->whereHas('item.template', function ($q) use ($filters) {
                $q->where('level_requirement', '>=', (int) $filters['min_level']);
            });
        }

        if (isset($filters['max_level'])) {
            $query->whereHas('item.template', function ($q) use ($filters) {
                $q->where('level_requirement', '<=', (int) $filters['max_level']);
            });
        }

        // Filter by required bonus stats (checklist - przedmiot musi posiadać KAŻDĄ z zaznaczonych statystyk)
        if (!empty($filters['stats']) && is_array($filters['stats'])) {
            $statKeys = array_values(array_intersect($filters['stats'], self::ALLOWED_STAT_FILTERS));
            $driver = $query->getConnection()->getDriverName();

            foreach ($statKeys as $statKey) {
                $rollExpr = $this->jsonStatExpr($driver, 'roll_stats', $statKey);
                $baseExpr = $this->jsonRangeStatExpr($driver, 'base_stats', $statKey);

                $query->where(function ($sub) use ($rollExpr, $baseExpr) {
                    $sub->whereHas('item', function ($q) use ($rollExpr) {
                        $q->whereRaw("{$rollExpr} <> 0");
                    })->orWhereHas('item.template', function ($q) use ($baseExpr) {
                        $q->whereRaw("{$baseExpr} <> 0");
                    });
                });
            }
        }

        // Filter by currency
        if (!empty($filters['currency'])) {
            $query->where('currency', $filters['currency']);
        }

        // Filter by price range
        if (isset($filters['min_price'])) {
            $query->where('price', '>=', (int) $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', (int) $filters['max_price']);
        }

        // Filter by item slot/type
        if (!empty($filters['slot'])) {
            $slotFilter = $filters['slot'];
            $query->whereHas('item.template', function ($q) use ($slotFilter) {
                if (in_array($slotFilter, ['weapon', 'main_hand'])) {
                    $q->where(function ($sub) {
                        $sub->where('type', 'weapon')
                            ->orWhere('slot', 'main_hand')
                            ->orWhere('slot', 'weapon');
                    });
                } elseif (in_array($slotFilter, ['boots', 'feet'])) {
                    $q->whereIn('slot', ['feet', 'boots']);
                } elseif ($slotFilter === 'accessory') {
                    $q->where(function ($sub) {
                        $sub->where('type', 'accessory')
                            ->orWhereIn('slot', ['ring', 'neck', 'accessory']);
                    });
                } elseif ($slotFilter === 'material') {
                    $q->where('type', 'material');
                } elseif ($slotFilter === 'consumable') {
                    $q->where('type', 'consumable');
                } else {
                    $q->where(function ($sub) use ($slotFilter) {
                        $sub->where('slot', $slotFilter)
                            ->orWhere('type', $slotFilter);
                    });
                }
            });
        }

        // Validate sort column
        $allowedSorts = ['created_at', 'price', 'expires_at', 'level'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'level') {
            // level_requirement mieszka w item_templates, więc sortujemy podzapytaniem,
            // by uniknąć duplikowania wierszy przez join i konfliktów nazw kolumn.
            $levelSubquery = \App\Infrastructure\Persistence\ItemTemplate::query()
                ->selectRaw('level_requirement')
                ->join('item_instances', 'item_instances.template_id', '=', 'item_templates.id')
                ->whereColumn('item_instances.id', 'market_listings.item_instance_id')
                ->limit(1);

            $query->orderBy($levelSubquery, $sortDir);
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        return $query->paginate($perPage);
    }

    /**
     * Wariant dla ofert chowańców - pety mają zupełnie inny zestaw filtrów
     * (tier/poziom/CP) niż itemy (statystyki bonusowe), więc to osobna ścieżka
     * zamiast doklejania kolejnych warunków do execute().
     */
    private function executeForPets(array $filters, string $sortBy, string $sortDir, int $perPage)
    {
        $query = MarketListing::query()
            ->where('status', 'active')
            ->has('pet')
            ->with(['pet', 'seller']);

        if (!empty($filters['search'])) {
            $search = mb_strtolower(trim($filters['search']), 'UTF-8');
            $query->whereHas('pet', function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . $search . '%']);
            });
        }

        if (!empty($filters['tier'])) {
            $query->whereHas('pet', function ($q) use ($filters) {
                $q->where('tier', (int) $filters['tier']);
            });
        }

        if (isset($filters['min_level'])) {
            $query->whereHas('pet', function ($q) use ($filters) {
                $q->where('level', '>=', (int) $filters['min_level']);
            });
        }

        if (isset($filters['max_level'])) {
            $query->whereHas('pet', function ($q) use ($filters) {
                $q->where('level', '<=', (int) $filters['max_level']);
            });
        }

        if (!empty($filters['currency'])) {
            $query->where('currency', $filters['currency']);
        }

        if (isset($filters['min_price'])) {
            $query->where('price', '>=', (int) $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', (int) $filters['max_price']);
        }

        $allowedSorts = ['created_at', 'price', 'expires_at', 'level', 'tier'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['level', 'tier'], true)) {
            $petSubquery = \App\Infrastructure\Persistence\Pet::query()
                ->selectRaw($sortBy)
                ->whereColumn('pets.id', 'market_listings.pet_id')
                ->limit(1);

            $query->orderBy($petSubquery, $sortDir);
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        return $query->paginate($perPage);
    }
}
