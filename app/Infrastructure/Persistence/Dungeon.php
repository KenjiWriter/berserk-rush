<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class Dungeon extends Model
{
    protected $fillable = [
        'name',
        'min_level',
        'entry_item_template_id',
    ];

    public function stages()
    {
        return $this->hasMany(DungeonStage::class)->orderBy('stage_order');
    }

    public function entryItemTemplate()
    {
        return $this->belongsTo(ItemTemplate::class, 'entry_item_template_id');
    }

    public function runs()
    {
        return $this->hasMany(CharacterDungeonRun::class);
    }

    public function canCharacterEnter(Character $character): bool
    {
        return $character->level >= $this->min_level;
    }
}
