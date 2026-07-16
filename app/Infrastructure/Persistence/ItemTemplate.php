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
}
