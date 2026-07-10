<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class DungeonStage extends Model
{
    protected $fillable = [
        'dungeon_id',
        'stage_order',
        'monster_id',
    ];

    public function dungeon()
    {
        return $this->belongsTo(Dungeon::class);
    }

    public function monster()
    {
        return $this->belongsTo(Monster::class);
    }
}
