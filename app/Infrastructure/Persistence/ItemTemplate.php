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
    ];

    protected $casts = [
        'base_stats' => 'array',
        'rarity_weights' => 'array',
        'level_requirement' => 'integer',
    ];

    public function instances(): HasMany
    {
        return $this->hasMany(ItemInstance::class, 'template_id');
    }
}
