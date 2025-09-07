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
            $totalStats[$stat] = ($totalStats[$stat] ?? 0) + $value;
        }

        return $totalStats;
    }
}
