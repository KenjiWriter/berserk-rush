<?php

namespace App\Application\Economy\Queries;

use App\Infrastructure\Persistence\MarketListing;

class GetMarketListingsQuery
{
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
            $query->whereHas('item.template', function ($q) use ($filters) {
                $q->where('slot', $filters['slot']);
            });
        }

        // Validate sort column
        $allowedSorts = ['created_at', 'price', 'expires_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }
}
