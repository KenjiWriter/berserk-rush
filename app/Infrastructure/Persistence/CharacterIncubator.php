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

    /**
     * Sprawdza czy jajko jest gotowe do wylęgu.
     */
    public function isReady(): bool
    {
        if ($this->is_hatched || !$this->hatches_at) {
            return false;
        }
        return now()->gte($this->hatches_at);
    }

    /**
     * Zwraca procentowy postęp inkubacji.
     */
    public function getProgress(): float
    {
        if (!$this->started_at || !$this->hatches_at) {
            return 0;
        }

        $total = $this->hatches_at->diffInSeconds($this->started_at);
        $elapsed = now()->diffInSeconds($this->started_at);

        if ($total <= 0) return 100;
        return min(100, ($elapsed / $total) * 100);
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
