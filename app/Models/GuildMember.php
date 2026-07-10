<?php

namespace App\Models;

use App\Infrastructure\Persistence\Character;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuildMember extends Model
{
    use HasUlids;

    protected $fillable = [
        'guild_id',
        'character_id',
        'role', // leader, commander, elder, member
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    public function guild(): BelongsTo
    {
        return $this->belongsTo(Guild::class, 'guild_id');
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'character_id');
    }
}
