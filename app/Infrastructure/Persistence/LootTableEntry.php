<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LootTableEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'loot_table_id',
        'reward_type',
        'ref_ulid',
        'ref_numeric_id',
        'weight',
        'min_qty',
        'max_qty',
        'conditions',
    ];

    protected $casts = [
        'loot_table_id' => 'integer',
        'weight' => 'integer',
        'min_qty' => 'integer',
        'max_qty' => 'integer',
        'conditions' => 'array',
    ];

    // Relationships
    public function lootTable(): BelongsTo
    {
        return $this->belongsTo(LootTable::class);
    }
}
