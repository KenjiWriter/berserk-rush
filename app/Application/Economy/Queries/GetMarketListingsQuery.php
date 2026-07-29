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

    public function execute(array $filters = [], string $sortBy = 'created_at', string $sortDir = 'desc', int $perPage = 20)
    {
        $query = MarketListing::query()
            ->where('status', 'active')
            ->has('item')
            ->with(['item.template', 'seller']);

        // Search by item name
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('item.template', function ($q) use ($search) {
                $q->where('name', 'LIKE', '%' . $search . '%');
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
                $baseExpr = $this->jsonStatExpr($driver, 'base_stats', $statKey);

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
}
