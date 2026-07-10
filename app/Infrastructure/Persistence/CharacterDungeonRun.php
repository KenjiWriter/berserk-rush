<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class CharacterDungeonRun extends Model
{
    protected $fillable = [
        'character_id',
        'dungeon_id',
        'current_stage',
        'current_hp',
        'is_completed',
        'is_failed',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'is_failed' => 'boolean',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    public function dungeon()
    {
        return $this->belongsTo(Dungeon::class);
    }

    public function getCurrentStageModel()
    {
        return DungeonStage::where('dungeon_id', $this->dungeon_id)
            ->where('stage_order', $this->current_stage)
            ->first();
    }
}
