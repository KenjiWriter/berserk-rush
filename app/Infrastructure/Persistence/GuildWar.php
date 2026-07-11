<?php

namespace App\Infrastructure\Persistence;

use App\Models\Guild;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuildWar extends Model
{
    use HasUlids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'challenger_guild_id',
        'defender_guild_id',
        'status',
        'winner_guild_id',
        'challenger_roster',
        'defender_roster',
        'gold_prize',
        'gems_prize',
        'xp_prize',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'challenger_roster' => 'array',
        'defender_roster' => 'array',
        'gold_prize' => 'integer',
        'gems_prize' => 'integer',
        'xp_prize' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    // Relationships

    public function challengerGuild(): BelongsTo
    {
        return $this->belongsTo(Guild::class, 'challenger_guild_id');
    }

    public function defenderGuild(): BelongsTo
    {
        return $this->belongsTo(Guild::class, 'defender_guild_id');
    }

    public function winnerGuild(): BelongsTo
    {
        return $this->belongsTo(Guild::class, 'winner_guild_id');
    }

    public function fights(): HasMany
    {
        return $this->hasMany(GuildWarFight::class, 'guild_war_id');
    }

    // State checks

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }
}
