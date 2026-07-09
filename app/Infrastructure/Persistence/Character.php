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
                if (isset($templateStats[$stat])) {
                    $total[$stat] += $templateStats[$stat];
                }
                if (isset($rollStats[$stat])) {
                    // In a real app we might scale roll_stats by upgrade_level
                    $total[$stat] += $rollStats[$stat];
                }
            }
        }

        return $total;
    }
}
