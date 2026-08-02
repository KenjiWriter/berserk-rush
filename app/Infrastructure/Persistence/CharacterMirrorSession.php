<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class CharacterMirrorSession extends Model
{
    protected $table = 'character_mirror_sessions';

    protected $fillable = [
        'character_id',
        'map_id',
        'duration_hours',
        'exp_per_minute',
        'gold_per_minute',
        'started_at',
        'ends_at',
        'status',
        'claimed_at',
    ];

    protected $casts = [
        'duration_hours' => 'integer',
        'exp_per_minute' => 'integer',
        'gold_per_minute' => 'integer',
        'started_at' => 'datetime',
        'ends_at' => 'datetime',
        'claimed_at' => 'datetime',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function map(): BelongsTo
    {
        return $this->belongsTo(Map::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && now()->lt($this->ends_at);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'active' && now()->gte($this->ends_at);
    }

    /**
     * Total elapsed minutes, capped by duration_hours * 60.
     */
    public function getElapsedMinutes(): int
    {
        if (!$this->started_at) {
            return 0;
        }

        $now = now();
        $effectiveEnd = $now->lt($this->ends_at) ? $now : $this->ends_at;
        $minutes = (int) $this->started_at->diffInMinutes($effectiveEnd);
        $maxMinutes = $this->duration_hours * 60;

        return min($maxMinutes, max(0, $minutes));
    }

    /**
     * Total remaining seconds.
     */
    public function getRemainingSeconds(): int
    {
        if ($this->status !== 'active' || now()->gte($this->ends_at)) {
            return 0;
        }

        return max(0, (int) now()->diffInSeconds($this->ends_at, false));
    }

    /**
     * Calculate current accumulated rewards (40% rate of max exp/min and gold/min, plus material drops).
     */
    public function calculateCurrentRewards(): array
    {
        $elapsedMinutes = $this->getElapsedMinutes();

        // 40% rate of normal yield
        $accumulatedXp = (int) floor($elapsedMinutes * $this->exp_per_minute * 0.40);
        $accumulatedGold = (int) floor($elapsedMinutes * $this->gold_per_minute * 0.40);

        // Material drops: 1 roll per 15 minutes of elapsed time
        $materials = [];
        $dropIntervals = (int) floor($elapsedMinutes / 15);

        if ($dropIntervals > 0 && $this->map) {
            $mapMaterials = $this->getAvailableMapMaterials();
            if ($mapMaterials->isNotEmpty()) {
                for ($i = 0; $i < $dropIntervals; $i++) {
                    // 70% chance per interval to drop a material
                    // We generate deterministic seed or random roll
                    $mat = $mapMaterials[$i % $mapMaterials->count()];
                    $qty = 1;
                    $matId = $mat->id;
                    if (!isset($materials[$matId])) {
                        $materials[$matId] = [
                            'item_template' => $mat,
                            'quantity' => 0,
                        ];
                    }
                    $materials[$matId]['quantity'] += $qty;
                }
            }
        }

        return [
            'elapsed_minutes' => $elapsedMinutes,
            'max_minutes' => $this->duration_hours * 60,
            'xp' => $accumulatedXp,
            'gold' => $accumulatedGold,
            'materials' => $materials,
        ];
    }

    /**
     * Get candidate material item templates that drop from the map's monsters.
     */
    public function getAvailableMapMaterials()
    {
        if (!$this->map) {
            return collect();
        }

        $monsters = $this->map->explorationMonsters()->with(['lootTable.entries.itemTemplate'])->get();
        $materials = collect();

        foreach ($monsters as $monster) {
            if (!$monster->lootTable) continue;
            foreach ($monster->lootTable->entries as $entry) {
                if ($entry->itemTemplate && $entry->itemTemplate->type === 'material') {
                    $materials->push($entry->itemTemplate);
                }
            }
        }

        $materials = $materials->unique('id')->values();

        // Fallback: If no material drops explicitly configured on map loot tables, search DB for material templates matching map level/tier
        if ($materials->isEmpty()) {
            $materials = ItemTemplate::where('type', 'material')
                ->where('level_requirement', '<=', $this->map->level_max ?? 99)
                ->limit(5)
                ->get();
        }

        return $materials;
    }
}
