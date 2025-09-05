<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class Monster extends Model
{
    protected $fillable = [
        'map_id',
        'name',
        'level',
        'stats',
        'abilities',
        'loot_table_id',
    ];

    protected $casts = [
        'map_id' => 'integer',
        'level' => 'integer',
        'stats' => 'array',
        'abilities' => 'array',
        'loot_table_id' => 'integer',
    ];

    public function map()
    {
        return $this->belongsTo(Map::class);
    }

    public function lootTable()
    {
        return $this->belongsTo(LootTable::class);
    }

    public function getMaxHpAttribute(): int
    {
        return $this->stats['hp'] ?? 100;
    }

    public function getAttackAttribute(): int
    {
        return $this->stats['atk'] ?? 10;
    }

    public function getDefenseAttribute(): int
    {
        return $this->stats['def'] ?? 5;
    }

    public function getAgilityAttribute(): int
    {
        return $this->stats['agi'] ?? 5;
    }

    public function getIntelligenceAttribute(): int
    {
        return $this->stats['int'] ?? 1;
    }

    public function getCritChanceAttribute(): float
    {
        return $this->stats['crit'] ?? 0.05;
    }

    public function getDodgeChanceAttribute(): float
    {
        return $this->stats['dodge'] ?? 0.02;
    }
}
