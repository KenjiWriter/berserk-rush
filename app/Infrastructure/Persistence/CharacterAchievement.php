<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class CharacterAchievement extends Model
{
    protected $fillable = [
        'character_id',
        'achievement_id',
        'progress',
        'completed_at',
        'rewarded'
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'rewarded' => 'boolean'
    ];

    public function achievement()
    {
        return $this->belongsTo(Achievement::class);
    }
}
