<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class CharacterIncubator extends Model
{
    protected $fillable = [
        'character_id',
        'egg_item_instance_id',
        'egg_rarity',
        'started_at',
        'hatches_at',
        'is_hatched',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'hatches_at' => 'datetime',
        'is_hatched' => 'boolean',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    public function eggItemInstance()
    {
        return $this->belongsTo(ItemInstance::class, 'egg_item_instance_id');
    }

    public function getEffectiveRarity(): string
    {
        if ($this->eggItemInstance) {
            return $this->eggItemInstance->getEggRarity();
        }
        return $this->egg_rarity ?? 'common';
    }

    /**
     * Synchronicznie koryguje rzadkość i czas inkubacji, jeśli podpięte jajko ma inną rzadkość.
     */
    public function syncRarityIfNecessary(): void
    {
        if ($this->is_hatched || !$this->egg_item_instance_id) {
            return;
        }

        $effectiveRarity = $this->getEffectiveRarity();
        if ($effectiveRarity !== $this->egg_rarity) {
            $hours = self::getIncubationHours($effectiveRarity);
            $this->egg_rarity = $effectiveRarity;
            if ($this->started_at) {
                $this->hatches_at = $this->started_at->copy()->addHours($hours);
            }
            $this->save();
        }
    }

    /**
     * Sprawdza czy jajko jest gotowe do wylęgu.
     */
    public function isReady(): bool
    {
        if ($this->is_hatched || !$this->hatches_at) {
            return false;
        }
        $this->syncRarityIfNecessary();
        return now()->gte($this->hatches_at);
    }

    /**
     * Zwraca procentowy postęp inkubacji.
     */
    public function getProgress(): float
    {
        if (!$this->started_at || !$this->hatches_at) {
            return 0.0;
        }

        $this->syncRarityIfNecessary();

        $nowTs = now()->timestamp;
        $startTs = $this->started_at->timestamp;
        $hatchTs = $this->hatches_at->timestamp;

        if ($nowTs >= $hatchTs) {
            return 100.0;
        }

        if ($nowTs <= $startTs) {
            return 0.0;
        }

        $totalSeconds = $hatchTs - $startTs;
        $elapsedSeconds = $nowTs - $startTs;

        if ($totalSeconds <= 0) {
            return 100.0;
        }

        $progress = ($elapsedSeconds / $totalSeconds) * 100.0;
        return max(0.0, min(100.0, round($progress, 1)));
    }

    /**
     * Czas inkubacji w godzinach zależnie od rzadkości.
     */
    public static function getIncubationHours(string $rarity): int
    {
        return match ($rarity) {
            'common' => 1,
            'uncommon' => 2,
            'rare' => 4,
            'epic' => 8,
            'legendary' => 24,
            default => 1,
        };
    }
}
