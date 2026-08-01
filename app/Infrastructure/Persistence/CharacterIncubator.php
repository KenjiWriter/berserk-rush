<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Pets\PetTier;
use Illuminate\Database\Eloquent\Model;

class CharacterIncubator extends Model
{
    protected $fillable = [
        'character_id',
        'egg_item_instance_id',
        'egg_rarity',
        'egg_tier',
        'started_at',
        'hatches_at',
        'is_hatched',
    ];

    protected $casts = [
        'egg_tier' => 'integer',
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

    public function getEffectiveTier(): int
    {
        if ($this->eggItemInstance && $this->eggItemInstance->getEggTier()) {
            return $this->eggItemInstance->getEggTier();
        }
        return $this->egg_tier ?? 1;
    }

    /**
     * Synchronicznie koryguje tier i czas inkubacji, jeśli podpięte jajko ma inny tier
     * niż zapisany (np. admin podmienił szablon jajka po umieszczeniu w inkubatorze).
     */
    public function syncTierIfNecessary(): void
    {
        if ($this->is_hatched || !$this->egg_item_instance_id) {
            return;
        }

        $effectiveTier = $this->getEffectiveTier();
        if ($effectiveTier !== $this->egg_tier) {
            $hours = PetTier::hatchHours($effectiveTier);
            $this->egg_tier = $effectiveTier;
            $this->egg_rarity = PetTier::slug($effectiveTier);
            if ($this->started_at) {
                $this->hatches_at = $this->started_at->copy()->addMinutes((int) round($hours * 60));
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
        $this->syncTierIfNecessary();
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

        $this->syncTierIfNecessary();

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
}
