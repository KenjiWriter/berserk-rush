<?php

namespace App\Infrastructure\Persistence;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Character extends Model
{
    use HasUlids;

    protected $fillable = [
        'user_id',
        'name',
        'level',
        'xp',
        'gold',
        'gems',
        'attributes',
        'proficiencies',
        'avatar',
        'version',
        'character_points',
    ];

    protected $casts = [
        'attributes' => 'array',
        'proficiencies' => 'array',
        'level' => 'integer',
        'xp' => 'integer',
        'gold' => 'integer',
        'gems' => 'integer',
        'version' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }



    public function getStrengthAttribute(): int
    {
        return $this->getAttribute('attributes')['str'] ?? 0;
    }

    public function getIntelligenceAttribute(): int
    {
        return $this->getAttribute('attributes')['int'] ?? 0;
    }

    public function getVitalityAttribute(): int
    {
        return $this->getAttribute('attributes')['vit'] ?? 0;
    }

    public function getAgilityAttribute(): int
    {
        return $this->getAttribute('attributes')['agi'] ?? 0;
    }

    public function getTotalAttributePoints(): int
    {
        $attrs = $this->getAttribute('attributes') ?? [];
        return ($attrs['str'] ?? 0) + ($attrs['int'] ?? 0) + ($attrs['vit'] ?? 0) + ($attrs['agi'] ?? 0);
    }

    public function items()
    {
        return $this->hasMany(ItemInstance::class, 'owner_character_id');
    }

    public function equippedItems()
    {
        return $this->items()->where('location', 'equipped')->with('template');
    }

    public function inventoryItems()
    {
        return $this->items()->where('location', 'inventory')->with('template');
    }

    public function getTotalAttributes(): array
    {
        $base = $this->getAttribute('attributes') ?? ['str' => 0, 'int' => 0, 'vit' => 0, 'agi' => 0];
        
        $total = [
            'str' => $base['str'] ?? 0,
            'int' => $base['int'] ?? 0,
            'vit' => $base['vit'] ?? 0,
            'agi' => $base['agi'] ?? 0,
        ];

        foreach ($this->equippedItems as $item) {
            $templateStats = $item->template->base_stats ?? [];
            $rollStats = $item->roll_stats ?? [];

            // Add template stats
            foreach (['str', 'int', 'vit', 'agi'] as $stat) {
                $bonusKey = $stat . '_bonus';
                if (isset($templateStats[$bonusKey])) {
                    $total[$stat] += $templateStats[$bonusKey];
                }
                if (isset($rollStats[$bonusKey])) {
                    // In a real app we might scale roll_stats by upgrade_level
                    $total[$stat] += $rollStats[$bonusKey];
                }
            }
        }

        return $total;
    }

    public function getEquipmentStats(): array
    {
        $stats = [
            'hp_bonus' => 0,
            'mana_bonus' => 0,
            'attack_min' => 0,
            'attack_max' => 0,
            'magic_attack_min' => 0,
            'magic_attack_max' => 0,
            'defense' => 0,
            'crit_chance' => 0,
        ];

        foreach ($this->equippedItems as $item) {
            $base = $item->template->base_stats ?? [];
            $roll = $item->roll_stats ?? [];
            $upgrade = $item->getUpgradeBonusStats();
            
            $stats['hp_bonus'] += ($base['hp_bonus'] ?? 0) + ($roll['hp_bonus'] ?? 0) + ($upgrade['hp_bonus'] ?? 0);
            $stats['mana_bonus'] += ($base['mana_bonus'] ?? 0) + ($roll['mana_bonus'] ?? 0) + ($upgrade['mana_bonus'] ?? 0);
            $stats['attack_min'] += ($base['attack_min'] ?? 0) + ($roll['attack_min'] ?? 0) + ($upgrade['attack_min'] ?? 0);
            $stats['attack_max'] += ($base['attack_max'] ?? 0) + ($roll['attack_max'] ?? 0) + ($upgrade['attack_max'] ?? 0);
            $stats['magic_attack_min'] += ($base['magic_attack_min'] ?? 0) + ($roll['magic_attack_min'] ?? 0) + ($upgrade['magic_attack_min'] ?? 0);
            $stats['magic_attack_max'] += ($base['magic_attack_max'] ?? 0) + ($roll['magic_attack_max'] ?? 0) + ($upgrade['magic_attack_max'] ?? 0);
            $stats['defense'] += ($base['defense'] ?? 0) + ($roll['defense'] ?? 0) + ($upgrade['defense'] ?? 0);
            $stats['crit_chance'] += ($base['crit_chance'] ?? 0) + ($roll['crit_chance'] ?? 0) + ($upgrade['crit_chance'] ?? 0);
        }

        return $stats;
    }

    public function getMaxHp(): int
    {
        $vitality = $this->getTotalAttributes()['vit'] ?? 1;
        $eq = $this->getEquipmentStats();
        return 100 + ($vitality * 10) + ($this->level * 5) + ($eq['hp_bonus'] ?? 0);
    }
}
