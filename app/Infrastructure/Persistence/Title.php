<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Title extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'prefix',
        'description',
        'stats_bonus'
    ];

    protected $casts = [
        'stats_bonus' => 'array',
    ];
}
