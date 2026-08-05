<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class CharacterLocationEventRun extends Model
{
    protected $fillable = [
        'character_id',
        'map_id',
        'location_event_id',
        'upgrade_level',
        'is_hardcore',
        'monsters_queue',
        'current_monster_index',
        'total_monsters',
        'current_hp',
        'current_mana',
        'is_completed',
        'is_failed',
        'combat_state',
        'combat_data',
        'accumulated_loot',
    ];

    protected $casts = [
        'upgrade_level' => 'integer',
        'is_hardcore' => 'boolean',
        'monsters_queue' => 'array',
        'current_monster_index' => 'integer',
        'total_monsters' => 'integer',
        'is_completed' => 'boolean',
        'is_failed' => 'boolean',
        'combat_data' => 'array',
        'accumulated_loot' => 'array',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    public function map()
    {
        return $this->belongsTo(Map::class);
    }

    public function locationEvent()
    {
        return $this->belongsTo(LocationEvent::class);
    }

    public function getCurrentSlot(): ?array
    {
        return $this->monsters_queue[$this->current_monster_index] ?? null;
    }

    public function isLastSlot(): bool
    {
        return $this->current_monster_index >= $this->total_monsters - 1;
    }
}
