<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Domain\Quests\Enums\QuestType;

class Quest extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type',
        'required_level',
        'max_level',
        'target_type',
        'target_id',
        'target_amount',
        'reward_gold',
        'reward_exp',
        'reward_items',
        'is_active',
    ];

    protected $casts = [
        'type' => QuestType::class,
        'reward_items' => 'array',
        'is_active' => 'boolean',
    ];

    public function characterQuests(): HasMany
    {
        return $this->hasMany(CharacterQuest::class, 'quest_id');
    }
}
