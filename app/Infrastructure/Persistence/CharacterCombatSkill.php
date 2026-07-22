<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterCombatSkill extends Model
{
    use HasUlids;

    protected $table = 'character_combat_skills';

    protected $fillable = [
        'character_id',
        'combat_skill_id',
        'level',
        'is_equipped',
        'equip_slot',
    ];

    protected $casts = [
        'is_equipped' => 'boolean',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(CombatSkill::class, 'combat_skill_id');
    }
}
