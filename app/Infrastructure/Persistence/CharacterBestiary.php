<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class CharacterBestiary extends Model
{
    protected $table = 'character_bestiary';

    protected $fillable = [
        'character_id',
        'monster_id',
        'kills'
    ];

    public function monster()
    {
        return $this->belongsTo(Monster::class);
    }
}
