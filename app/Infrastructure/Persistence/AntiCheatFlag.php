<?php

namespace App\Infrastructure\Persistence;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AntiCheatFlag extends Model
{
    protected $table = 'anti_cheat_flags';

    protected $fillable = [
        'character_id',
        'type',
        'severity',
        'metric_value',
        'threshold',
        'window_minutes',
        'details',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'details' => 'array',
        'metric_value' => 'integer',
        'threshold' => 'integer',
        'window_minutes' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'character_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }
}
