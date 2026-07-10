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
     * Zwraca łączne statystyki peta do dodania do Combat Power postaci.
     */
    public function getTotalStats(): array
    {
        return $this->stats ?? [];
    }

    /**
     * Oblicza Combat Power peta.
     */
    public function getCombatPower(): int
    {
        $stats = $this->stats ?? [];
        return array_sum($stats) * $this->level;
    }
}
