<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class Pet extends Model
{
    protected $fillable = [
        'character_id',
        'name',
        'rarity',
        'stats',
        'level',
        'exp',
        'is_equipped',
        'icon',
    ];

    protected $casts = [
        'stats' => 'array',
        'is_equipped' => 'boolean',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    /**
     * Zwraca łączne (przeskalowane poziomem) statystyki peta.
     * Każdy poziom powyżej 1 zwiększa bazowe statystyki o +10%.
     */
    public function getTotalStats(): array
    {
        $baseStats = $this->stats ?? [];
        $scaled = [];
        $multiplier = 1 + (($this->level - 1) * 0.10);

        foreach (['str', 'agi', 'int', 'vit'] as $stat) {
            if (isset($baseStats[$stat])) {
                $scaled[$stat] = (int) round($baseStats[$stat] * $multiplier);
            }
        }

        return $scaled;
    }

    /**
     * Oblicza Combat Power peta na podstawie przeskalowanych statystyk.
     */
    public function getCombatPower(): int
    {
        $stats = $this->getTotalStats();
        return array_sum($stats) * $this->level;
    }

    /**
     * Wymagany EXP na następny poziom.
     */
    public function getRequiredExp(): int
    {
        return max(100, $this->level * 100);
    }

    /**
     * Procentowy postęp EXP do następnego poziomu.
     */
    public function getExpProgressPercent(): float
    {
        $req = $this->getRequiredExp();
        if ($req <= 0) {
            return 0.0;
        }
        return min(100.0, round(($this->exp / $req) * 100, 1));
    }
}
