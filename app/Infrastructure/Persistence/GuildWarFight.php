<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuildWarFight extends Model
{
    protected $fillable = [
        'guild_war_id',
        'fight_order',
        'challenger_character_id',
        'defender_character_id',
        'winner_character_id',
        'challenger_snapshot',
        'defender_snapshot',
        'turns',
        'combat_data',
        'challenger_survivors',
        'defender_survivors',
        'rounds',
    ];

    protected $casts = [
        // Starcie drużynowe 5v5 (2026-07-28): tablica do 5 snapshotów postaci
        // zamiast pojedynczego obiektu - patrz GuildWarService::simulateTeamBattle().
        'challenger_snapshot' => 'array',
        'defender_snapshot' => 'array',
        'turns' => 'array',
        'combat_data' => 'array',
        'fight_order' => 'integer',
        'challenger_survivors' => 'integer',
        'defender_survivors' => 'integer',
        'rounds' => 'integer',
    ];

    // Relationships

    public function guildWar(): BelongsTo
    {
        return $this->belongsTo(GuildWar::class, 'guild_war_id');
    }

    public function challengerCharacter(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'challenger_character_id');
    }

    public function defenderCharacter(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'defender_character_id');
    }
}
