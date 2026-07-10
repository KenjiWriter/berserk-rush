<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class WorldBossDamageLog extends Model
{
    protected $fillable = [
        'world_boss_instance_id',
        'character_id',
        'damage',
    ];

    public function worldBossInstance()
    {
        return $this->belongsTo(WorldBossInstance::class);
    }

    public function character()
    {
        return $this->belongsTo(Character::class);
    }
}
