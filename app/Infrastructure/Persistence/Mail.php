<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Mail extends Model
{
    use HasUlids;

    protected $table = 'mail';

    protected $fillable = [
        'to_character_id',
        'subject',
        'body',
        'attachments',
        'claimed',
    ];

    protected $casts = [
        'attachments' => 'array',
        'claimed'     => 'boolean',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    /* ---------- Relations ---------- */

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'to_character_id');
    }

    /* ---------- Scopes ---------- */

    public function scopeUnclaimed(Builder $query): Builder
    {
        return $query->where('claimed', false);
    }

    public function scopeForCharacter(Builder $query, string $characterId): Builder
    {
        return $query->where('to_character_id', $characterId);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('created_at', '<', now()->subDays(90));
    }

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where('created_at', '>=', now()->subDays(90));
    }

    /* ---------- Helpers ---------- */

    public function hasAttachments(): bool
    {
        return !empty($this->attachments);
    }

    public function isExpired(): bool
    {
        return $this->created_at->lt(now()->subDays(90));
    }

    public function isClaimed(): bool
    {
        return $this->claimed;
    }

    public function daysUntilExpiry(): int
    {
        $expiresAt = $this->created_at->addDays(90);
        return max(0, (int) now()->diffInDays($expiresAt, false));
    }
}
