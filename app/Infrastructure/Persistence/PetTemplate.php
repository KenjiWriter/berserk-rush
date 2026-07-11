<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class PetTemplate extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'rarity',
        'base_stats',
        'icon',
    ];

    protected $casts = [
        'base_stats' => 'array',
    ];
}
