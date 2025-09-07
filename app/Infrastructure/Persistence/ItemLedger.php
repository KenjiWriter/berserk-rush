<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ItemLedger extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'character_id',
        'item_instance_id',
        'action',
        'ref_type',
        'ref_id',
        'quantity_change',
        'idempotency_key',
    ];

    protected $casts = [
        'quantity_change' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ItemLedger $ledger) {
            if (empty($ledger->id)) {
                $ledger->id = Str::ulid();
            }
        });
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function itemInstance(): BelongsTo
    {
        return $this->belongsTo(ItemInstance::class);
    }
}
