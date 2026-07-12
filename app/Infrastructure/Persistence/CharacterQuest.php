<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Quests\Enums\QuestStatus;

class CharacterQuest extends Model
{
    protected $fillable = [
        'character_id',
        'quest_id',
        'status',
        'progress',
    ];

    protected $casts = [
        'status' => QuestStatus::class,
        'progress' => 'integer',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'character_id');
    }

    public function quest(): BelongsTo
    {
        return $this->belongsTo(Quest::class, 'quest_id');
    }
}
