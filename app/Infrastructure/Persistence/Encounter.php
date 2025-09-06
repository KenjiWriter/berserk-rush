<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Encounter extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'character_id',
        'map_id',
        'monster_id',
        'state',
        'result',
        'player_first',
        'turns',
        'combat_data',
        'gold_reward',
        'xp_reward',
        'rewards_applied',
        'started_at',
        'ended_at', // używaj ended_at zamiast finished_at
    ];

    protected $casts = [
        'id' => 'string',
        'character_id' => 'string',
        'map_id' => 'integer', // to jest smallint w bazie
        'monster_id' => 'integer', // to jest bigint w bazie
        'player_first' => 'boolean',
        'turns' => 'array',
        'combat_data' => 'array',
        'result' => 'array', // to jest JSONB
        'gold_reward' => 'integer',
        'xp_reward' => 'integer',
        'rewards_applied' => 'boolean',
        'started_at' => 'datetime',
        'ended_at' => 'datetime', // zmienione z finished_at
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Encounter $encounter) {
            if (empty($encounter->id)) {
                $encounter->id = Str::ulid()->toString();
            }
            if (empty($encounter->started_at)) {
                $encounter->started_at = now();
            }
        });
    }

    // Relationships
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function map(): BelongsTo
    {
        return $this->belongsTo(Map::class);
    }

    public function monster(): BelongsTo
    {
        return $this->belongsTo(Monster::class);
    }

    // State management - używaj prawidłowych wartości state
    public function isOngoing(): bool
    {
        return $this->state === 'ongoing';
    }

    public function isFinished(): bool
    {
        return in_array($this->state, ['win', 'lose']);
    }

    public function markAsWon(): void
    {
        $this->update([
            'state' => 'win',
            'result' => [
                'winner' => 'player',
                'outcome' => 'victory'
            ],
            'ended_at' => now(),
        ]);
    }

    public function markAsLost(): void
    {
        $this->update([
            'state' => 'lose',
            'result' => [
                'winner' => 'enemy',
                'outcome' => 'defeat'
            ],
            'ended_at' => now(),
        ]);
    }

    public function markRewardsApplied(): void
    {
        $this->update(['rewards_applied' => true]);
    }

    public function setRewards(int $gold, int $xp): void
    {
        $this->update([
            'gold_reward' => $gold,
            'xp_reward' => $xp,
        ]);
    }

    public function setTurns(array $turns): void
    {
        $this->update(['turns' => $turns]);
    }

    // Scopes
    public function scopeOngoing($query)
    {
        return $query->where('state', 'ongoing');
    }

    public function scopeFinished($query)
    {
        return $query->whereIn('state', ['win', 'lose']);
    }

    public function scopeForCharacter($query, string $characterId)
    {
        return $query->where('character_id', $characterId);
    }
}
