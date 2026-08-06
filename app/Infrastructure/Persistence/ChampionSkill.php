<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class ChampionSkill extends Model
{
    use HasUlids;

    protected $table = 'champion_skills';

    protected $fillable = [
        'key',
        'name',
        'description',
        'stat_type',
        'bonus_per_point',
        'max_points',
        'icon',
        'sort_order',
    ];

    protected $casts = [
        'bonus_per_point' => 'float',
        'max_points' => 'integer',
        'sort_order' => 'integer',
    ];
}
