<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class CharacterTitle extends Model
{
    protected $fillable = [
        'character_id',
        'title_id',
        'unlocked_at'
    ];

    public $timestamps = true;

    protected $casts = [
        'unlocked_at' => 'datetime',
    ];

    public function title()
    {
        return $this->belongsTo(Title::class);
    }
}
