<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class CharacterPokedex extends Model
{
    protected $table = 'character_pokedex';

    protected $fillable = [
        'character_id',
        'item_template_id',
        'discovered_at'
    ];

    public $timestamps = true;

    protected $casts = [
        'discovered_at' => 'datetime',
    ];

    public function itemTemplate()
    {
        return $this->belongsTo(ItemTemplate::class);
    }
}
