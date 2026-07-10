<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemInstance extends Model
{
    use HasUlids;

    protected $fillable = [
        'template_id',
        'owner_character_id',
        'location',
        'stack_size',
        'rarity',
        'roll_stats',
        'upgrade_level',
        'bound_to_character',
        'version',
    ];

    protected $casts = [
        'stack_size' => 'integer',
        'upgrade_level' => 'integer',
        'roll_stats' => 'array',
        'bound_to_character' => 'boolean',
        'version' => 'integer',
    ];

    protected static function booted()
    {
        $clearCache = function ($item) {
            if ($item->owner_character_id) {
                $item->owner?->clearStatsCache();
            }
        };

        static::saved($clearCache);
        static::deleted($clearCache);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ItemTemplate::class, 'template_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'owner_character_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(ItemLedger::class);
    }

    public function scopeInInventory($query)
    {
        return $query->where('location', 'inventory');
    }

    public function scopeEquipped($query)
    {
        return $query->where('location', 'equipped');
    }

    public function scopeForCharacter($query, string $characterId)
    {
        return $query->where('owner_character_id', $characterId);
    }

    public function scopeByRarity($query, string $rarity)
    {
        return $query->where('rarity', $rarity);
    }

    public function isEquipped(): bool
    {
        return $this->location === 'equipped';
    }

    public function isInInventory(): bool
    {
        return $this->location === 'inventory';
    }

    public function isBound(): bool
    {
        return $this->bound_to_character;
    }

    public function canBeUpgraded(): bool
    {
        return $this->upgrade_level < 9; // Max upgrade level
    }

    public function getTotalStats(): array
    {
        $baseStats = $this->template->base_stats ?? [];
        $rollStats = $this->roll_stats ?? [];

        // Merge base stats with rolled stats
        $totalStats = $baseStats;
        foreach ($rollStats as $stat => $value) {
            if ($stat === 'enchants' && is_array($value)) {
                foreach ($value as $enchantType => $enchantValue) {
                    $totalStats[$enchantType] = ($totalStats[$enchantType] ?? 0) + $enchantValue;
                }
            } else {
                $totalStats[$stat] = ($totalStats[$stat] ?? 0) + $value;
            }
        }

        return $totalStats;
    }

    public function getEnchantments(): array
    {
        return $this->roll_stats['enchants'] ?? [];
    }

    public function addEnchantment(string $type, int $value): void
    {
        $stats = $this->roll_stats ?? [];
        if (!isset($stats['enchants'])) {
            $stats['enchants'] = [];
        }
        
        $stats['enchants'][$type] = $value;
        $this->roll_stats = $stats;
    }

    public function clearEnchantments(): void
    {
        $stats = $this->roll_stats ?? [];
        $stats['enchants'] = [];
        $this->roll_stats = $stats;
    }

    public function getUpgradeBonusStats(?int $level = null): array
    {
        $level = $level ?? $this->upgrade_level;
        if ($level <= 0) return [];

        $base = $this->template->base_stats ?? [];
        $bonus = [];

        foreach ($base as $stat => $val) {
            if (in_array($stat, ['attack_min', 'magic_attack_min'])) {
                $bonus[$stat] = $level * 2;
            } elseif (in_array($stat, ['attack_max', 'magic_attack_max'])) {
                $bonus[$stat] = $level * 3;
            } elseif ($stat === 'defense') {
                $bonus[$stat] = $level * 2;
            } elseif ($stat === 'hp_bonus') {
                $bonus[$stat] = $level * 10;
            } elseif (str_ends_with($stat, '_bonus') && $stat !== 'hp_bonus' && $stat !== 'mana_bonus') {
                $bonus[$stat] = (int) floor($level / 2);
            }
        }

        return $bonus;
    }

    public function getCombatPower(): int
    {
        $cp = 0;
        $stats = $this->getTotalStats();
        $bonus = $this->getUpgradeBonusStats();

        // Combine base+rolls and upgrade bonuses
        $allStats = $stats;
        foreach ($bonus as $stat => $val) {
            $allStats[$stat] = ($allStats[$stat] ?? 0) + $val;
        }

        $weights = [
            'attack_min' => 1.0,
            'attack_max' => 1.0,
            'magic_attack_min' => 1.0,
            'magic_attack_max' => 1.0,
            'defense' => 1.5,
            'hp_bonus' => 0.1,
            'mana_bonus' => 0.1,
            'str_bonus' => 2.0,
            'agi_bonus' => 2.0,
            'int_bonus' => 2.0,
            'vit_bonus' => 2.0,
        ];

        foreach ($allStats as $stat => $value) {
            $weight = $weights[$stat] ?? 1.0;
            $cp += $value * $weight;
        }

        // Rarity multiplier
        $multiplier = match ($this->rarity) {
            'uncommon' => 1.05,
            'rare' => 1.10,
            'epic' => 1.20,
            'legendary' => 1.35,
            default => 1.0,
        };

        return (int) round($cp * $multiplier);
    }
}
