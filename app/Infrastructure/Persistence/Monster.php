<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class Monster extends Model
{
    protected $fillable = [
        'map_id',
        'name',
        'type',
        'rank',
        'level',
        'stats',
        'abilities',
        'loot_table_id',
        'avatar',
    ];

    protected $casts = [
        'map_id' => 'integer',
        'level' => 'integer',
        'stats' => 'array',
        'abilities' => 'array',
        'loot_table_id' => 'integer',
        'type' => \App\Domain\Combat\Enums\MonsterType::class,
        'rank' => \App\Domain\Combat\Enums\MonsterRank::class,
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

    public function getScaledStats(int $playerLevel = 1, bool $isTutorial = false): array
    {
        if ($isTutorial) {
            return [
                'hp' => 35,
                'atk' => 6,
                'def' => 2,
                'agi' => 3,
                'int' => 1,
                'crit' => 0.05,
                'dodge' => 0.02,
            ];
        }

        return [
            'hp' => (int) ($this->stats['hp'] ?? ($this->level * 20)),
            'atk' => (int) ($this->stats['atk'] ?? ($this->level * 2)),
            'def' => (int) ($this->stats['def'] ?? $this->level),
            'agi' => (int) ($this->stats['agi'] ?? $this->level),
            'int' => (int) ($this->stats['int'] ?? 1),
            'crit' => (float) ($this->stats['crit'] ?? 0.05),
            'dodge' => (float) ($this->stats['dodge'] ?? 0.02),
        ];
    }
}
