<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class MarketListing extends Model
{
    use HasUlids;

    protected $fillable = [
        'seller_character_id',
        'item_instance_id',
        'material_ref_ulid',
        'quantity',
        'price',
        'currency',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'quantity'   => 'integer',
        'price'      => 'integer',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* ---------- Relations ---------- */

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'seller_character_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ItemInstance::class, 'item_instance_id');
    }

    /* ---------- Scopes ---------- */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeForSeller(Builder $query, string $characterId): Builder
    {
        return $query->where('seller_character_id', $characterId);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', 'active')
                     ->where('expires_at', '<=', now());
    }

    /* ---------- Helpers ---------- */

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function currencyLabel(): string
    {
        return match ($this->currency) {
            'gold' => 'Złoto',
            'gems' => 'Klejnoty',
            default => $this->currency,
        };
    }

    public function currencyEmoji(): string
    {
        return match ($this->currency) {
            'gold' => '💰',
            'gems' => '💎',
            default => '💲',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'active'    => 'Aktywna',
            'sold'      => 'Sprzedana',
            'expired'   => 'Wygasła',
            'cancelled' => 'Anulowana',
            default     => $this->status,
        };
    }
}
