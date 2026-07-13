<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Achievement extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'description',
        'type',
        'conditions',
        'target_value',
        'reward_points',
        'reward_item_template_id',
        'reward_title_id',
        'parent_achievement_id',
        'stats_bonus',
        'reward_gold',
        'reward_exp'
    ];

    protected $casts = [
        'stats_bonus' => 'array',
        'conditions' => 'array',
    ];

    public function parentAchievement()
    {
        return $this->belongsTo(Achievement::class, 'parent_achievement_id');
    }

    public function childAchievements()
    {
        return $this->hasMany(Achievement::class, 'parent_achievement_id');
    }

    public function characterAchievements()
    {
        return $this->hasMany(CharacterAchievement::class, 'achievement_id');
    }

    public function title()
    {
        return $this->belongsTo(Title::class, 'reward_title_id');
    }

    public function itemTemplate()
    {
        return $this->belongsTo(ItemTemplate::class, 'reward_item_template_id');
    }
}
