<?php

namespace App\Models;

use App\Infrastructure\Persistence\Character;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuildLog extends Model
{
    use HasUlids;

    protected $fillable = [
        'guild_id',
        'character_id',
        'action',
        'amount',
    ];

    public function guild(): BelongsTo
    {
        return $this->belongsTo(Guild::class);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
