<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActiveBuff extends Model
{
    use HasUlids;

    protected $fillable = [
        'character_id',
        'name',
        'effects',
        'expires_at',
    ];

    protected $casts = [
        'effects' => 'array',
        'expires_at' => 'datetime',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}

