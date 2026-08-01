<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemTemplate extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'type',
        'sub_type',
        'slot',
        'level_requirement',
        'base_stats',
        'description',
        'icon',
        'rarity_weights',
        'is_tradeable',
    ];

    protected $casts = [
        'base_stats' => 'array',
        'rarity_weights' => 'array',
        'level_requirement' => 'integer',
        'is_tradeable' => 'boolean',
    ];

    public function instances(): HasMany
    {
        return $this->hasMany(ItemInstance::class, 'template_id');
    }

    public function quest(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Quest::class, 'quest_id');
    }

    public function getIconAttribute(?string $value): ?string
    {
        if (empty($value) || $value === '🥚' || $value === '📦') {
            if (!empty($this->name)) {
                return \Illuminate\Support\Str::slug($this->name) . '.png';
            }
            return $value;
        }

        if (!preg_match('/\.(png|jpg|jpeg|webp)$/i', $value)) {
            return $value . '.png';
        }

        return $value;
    }
}
