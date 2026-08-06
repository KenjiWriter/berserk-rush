<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterChampionSkill extends Model
{
    use HasUlids;

    protected $table = 'character_champion_skills';

    protected $fillable = [
        'character_id',
        'champion_skill_id',
        'points',
    ];

    protected $casts = [
        'points' => 'integer',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(ChampionSkill::class, 'champion_skill_id');
    }
}
