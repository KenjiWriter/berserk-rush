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
        'user_id',
        'guild_id',
        'location',
        'stack_size',
        'rarity',
        'roll_stats',
        'rolled_stats',
        'upgrade_level',
        'bound_to_character',
        'version',
    ];

    protected $casts = [
        'stack_size' => 'integer',
        'upgrade_level' => 'integer',
        'roll_stats' => 'array',
        'rolled_stats' => 'array',
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
        
        static::created(function ($item) {
            if ($item->owner_character_id && $item->template_id) {
                event(new \App\Domain\Collections\Events\ItemDiscovered($item->owner, $item->template_id));
            }
        });
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ItemTemplate::class, 'template_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'owner_character_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function guild(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Guild::class, 'guild_id');
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

    public function scopeInPlayerStash($query, $userId)
    {
        return $query->where('location', 'player_stash')->where('user_id', $userId);
    }

    public function scopeInGuildStash($query, string $guildId)
    {
        return $query->where('location', 'guild_stash')->where('guild_id', $guildId);
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
        return (bool) ($this->bound_to_character ?? false);
    }

    public function canBeUpgraded(): bool
    {
        return $this->upgrade_level < 9; // Max upgrade level
    }

    /**
     * Flattens the template's base_stats into concrete numbers: ranged stats
     * ([min, max]) resolve to this instance's rolled_stats value (falling back
     * to the range midpoint for legacy/incomplete data), scalars pass through
     * unchanged.
     */
    public function getResolvedBaseStats(): array
    {
        $baseStats = $this->template->base_stats ?? [];
        $resolved = [];

        foreach ($baseStats as $stat => $value) {
            if (is_array($value)) {
                $min = $value[0] ?? 0;
                $max = $value[1] ?? $min;
                $resolved[$stat] = $this->rolled_stats[$stat] ?? (int) round(($min + $max) / 2);
            } elseif (is_numeric($value)) {
                $resolved[$stat] = $value;
            }
            // Non-numeric, non-range scalars (e.g. the 'loot_table' name stored on
            // chest consumables, see LootChestSeeder) are metadata, not a stat - skip
            // them so they don't reach the arithmetic below (getCombatPower(), the
            // upgrade-bonus calc, and the tooltip's total/diff math).
        }

        return $resolved;
    }

    public function isStatMaxed(string $stat): bool
    {
        $range = $this->template->base_stats[$stat] ?? null;
        if (!is_array($range) || count($range) < 2) {
            return false;
        }

        $rolled = $this->rolled_stats[$stat] ?? null;

        return $rolled !== null && $rolled >= $range[1];
    }

    public function getTotalStats(): array
    {
        $baseStats = $this->getResolvedBaseStats();
        $rollStats = $this->roll_stats ?? [];

        // Merge base stats with rolled stats
        $totalStats = $baseStats;
        foreach ($rollStats as $stat => $value) {
            if ($stat === 'enchant_locks') {
                // Metadane (lista zablokowanych typów zaklęć, patrz
                // ItemInstance::toggleEnchantLock()) - nie jest to statystyka.
                continue;
            }
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
        $stats['enchant_locks'] = [];
        $this->roll_stats = $stats;
    }

    /**
     * Zablokowane typy zaklęć (klucze z roll_stats['enchants']) - u Wiedźmy przelosowanie
     * (RerollEnchantments) pomija zablokowane bonusy, losując od nowa tylko resztę.
     */
    public function getEnchantLocks(): array
    {
        $locks = $this->roll_stats['enchant_locks'] ?? [];
        // Zablokowany może być tylko bonus, który faktycznie istnieje na przedmiocie -
        // odfiltrowuje "osierocone" wpisy po ewentualnym reroll/reset gdzie indziej.
        return array_values(array_intersect($locks, array_keys($this->getEnchantments())));
    }

    public function isEnchantLocked(string $type): bool
    {
        return in_array($type, $this->getEnchantLocks(), true);
    }

    public function toggleEnchantLock(string $type): void
    {
        if (!array_key_exists($type, $this->getEnchantments())) {
            return;
        }

        $stats = $this->roll_stats ?? [];
        $locks = $stats['enchant_locks'] ?? [];

        if (in_array($type, $locks, true)) {
            $locks = array_values(array_diff($locks, [$type]));
        } else {
            $locks[] = $type;
        }

        $stats['enchant_locks'] = $locks;
        $this->roll_stats = $stats;
    }

    public function getUpgradeBonusStats(?int $level = null): array
    {
        $level = $level ?? $this->upgrade_level;
        if ($level <= 0) return [];

        $base = $this->getResolvedBaseStats();
        $bonus = [];

        foreach ($base as $stat => $val) {
            if (is_numeric($val) && $val > 0) {
                $calc = (int) round($val * 0.10 * $level);
                $bonus[$stat] = max(1, $calc);
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
            // Wyjątek biżuterii: przedmioty co do zasady nie dają już atrybutów, ale
            // gdy naszyjnik/pierścień wylosuje mały, płaski bonus (+1..+5), nadal
            // powinien się liczyć do Combat Power.
            'str_bonus' => 2.0,
            'agi_bonus' => 2.0,
            'int_bonus' => 2.0,
            'vit_bonus' => 2.0,
            'crit_chance' => 1.0,
            'magic_burst_min' => 1.0,
            'magic_burst_max' => 1.0,
            'magic_burst_chance' => 1.5,
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
