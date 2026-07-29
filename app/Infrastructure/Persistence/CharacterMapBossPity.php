<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class CharacterMapBossPity extends Model
{
    protected $table = 'character_map_boss_pity';

    protected $fillable = [
        'character_id',
        'map_id',
        'kills_since_boss',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    public function map()
    {
        return $this->belongsTo(Map::class);
    }
}
