<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PvpEncounter extends Model
{
    use HasUlids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'attacker_character_id',
        'defender_character_id',
        'state',
        'winner_character_id',
        'attacker_snapshot',
        'defender_snapshot',
        'turns',
        'combat_data',
        'attacker_elo_change',
        'defender_elo_change',
        'arena_tokens_reward',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'attacker_snapshot' => 'array',
        'defender_snapshot' => 'array',
        'turns' => 'array',
        'combat_data' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'attacker_elo_change' => 'integer',
        'defender_elo_change' => 'integer',
        'arena_tokens_reward' => 'integer',
    ];

    // Relationships

    public function attacker(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'attacker_character_id');
    }

    public function defender(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'defender_character_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'winner_character_id');
    }

    // State checks

    public function isPending(): bool
    {
        return $this->state === 'pending';
    }

    public function isCalculating(): bool
    {
        return $this->state === 'calculating';
    }

    public function isFinished(): bool
    {
        return $this->state === 'finished';
    }

    // State transitions

    public function markAsCalculating(): void
    {
        $this->update(['state' => 'calculating']);
    }

    public function markAsFinished(
        string $winnerId,
        array $turns,
        array $combatData,
        int $attackerEloChange,
        int $defenderEloChange,
        int $tokensReward,
    ): void {
        $this->update([
            'state' => 'finished',
            'winner_character_id' => $winnerId,
            'turns' => $turns,
            'combat_data' => $combatData,
            'attacker_elo_change' => $attackerEloChange,
            'defender_elo_change' => $defenderEloChange,
            'arena_tokens_reward' => $tokensReward,
            'ended_at' => now(),
        ]);
    }

    // Scopes

    public function scopeForCharacter($query, string $characterId)
    {
        return $query->where(function ($q) use ($characterId) {
            $q->where('attacker_character_id', $characterId)
              ->orWhere('defender_character_id', $characterId);
        });
    }

    public function scopePending($query)
    {
        return $query->where('state', 'pending');
    }

    public function scopeFinished($query)
    {
        return $query->where('state', 'finished');
    }
}
