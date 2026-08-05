<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class Map extends Model
{
    protected $fillable = [
        'name',
        'level_min',
        'level_max',
        'tier',
        'image_path',
    ];

    protected $casts = [
        'level_min' => 'integer',
        'level_max' => 'integer',
        'tier' => 'integer',
    ];

    public function isAccessibleBy(Character $character): bool
    {
        if (!is_null($this->level_min)) {
            return $character->level >= $this->level_min;
        }

        return true;
    }

    public function isOverLevel(Character $character): bool
    {
        return !is_null($this->level_max) && $character->level > $this->level_max;
    }

    public function getLevelRangeAttribute(): string
    {
        return "{$this->level_min}–{$this->level_max}";
    }

    /**
     * Determine the player's "natural" tier — the highest map tier they have reached
     * based on their level (highest tier where level_min <= player.level).
     */
    public function getPlayerTier(Character $character): int
    {
        $playerTier = Map::orderBy('tier', 'desc')
            ->whereNotNull('tier')
            ->where('level_min', '<=', $character->level)
            ->value('tier');

        return (int)($playerTier ?? 1);
    }

    /**
     * How many tiers above the player is relative to this map (playerTier - mapTier).
     * Returns 0 if player is at or below the map tier.
     */
    public function getTierDiff(Character $character): int
    {
        $playerTier = $this->getPlayerTier($character);
        return max(0, $playerTier - ($this->tier ?? 1));
    }

    /**
     * Stat multiplier applied to monsters on this map for this character.
     * Formula: 1.0 + (playerTier * 0.20) when tierDiff > 0, else 1.0.
     */
    public function getMonsterTierMultiplier(Character $character): float
    {
        $playerTier = $this->getPlayerTier($character);
        $tierDiff = $this->getTierDiff($character);

        if ($tierDiff <= 0) {
            return 1.0;
        }

        return round(1.0 + ($playerTier * 0.20), 2);
    }

    public function monsters()
    {
        return $this->hasMany(Monster::class);
    }

    /**
     * Potwory dostępne w losowej puli eksploracji mapy - wyklucza potwory
     * przypisane do etapów lochów oraz World Bossów (ci mają dedykowany mechanizm spotkań).
     */
    public function explorationMonsters()
    {
        return $this->monsters()
            ->whereDoesntHave('dungeonStages')
            ->where('rank', '!=', \App\Domain\Combat\Enums\MonsterRank::WORLDBOSS);
    }
}
