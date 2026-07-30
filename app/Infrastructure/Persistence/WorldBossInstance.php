<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class WorldBossInstance extends Model
{
    protected $fillable = [
        'map_id',
        'monster_id',
        'level_bracket',
        'total_hp',
        'current_hp',
    ];

    public function map()
    {
        return $this->belongsTo(Map::class);
    }

    public function monster()
    {
        return $this->belongsTo(Monster::class);
    }

    public function damageLogs()
    {
        return $this->hasMany(WorldBossDamageLog::class);
    }
}
