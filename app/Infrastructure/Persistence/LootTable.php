<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LootTable extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    // Relationships
    public function monsters(): HasMany
    {
        return $this->hasMany(Monster::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(LootTableEntry::class);
    }

    // Loot generation methods
    public function rollLoot(): array
    {
        $entries = $this->entries()->get();
        $rewards = [];

        foreach ($entries as $entry) {
            if ($this->shouldDropReward($entry)) {
                $quantity = random_int($entry->min_qty, $entry->max_qty);
                $rewards[] = [
                    'type' => $entry->reward_type,
                    'ref_ulid' => $entry->ref_ulid,
                    'ref_numeric_id' => $entry->ref_numeric_id,
                    'quantity' => $quantity,
                ];
            }
        }

        return $rewards;
    }

    private function shouldDropReward(LootTableEntry $entry): bool
    {
        $totalWeight = $this->entries()->sum('weight');
        $dropChance = $entry->weight / max(1, $totalWeight);

        return mt_rand(1, 100) <= ($dropChance * 100);
    }
}
