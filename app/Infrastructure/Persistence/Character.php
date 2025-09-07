<?php

namespace App\Infrastructure\Persistence;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Character extends Model
{
    use HasUlids;

    protected $fillable = [
        'user_id',
        'name',
        'level',
        'xp',
        'gold',
        'gems',
        'attributes',
        'proficiencies',
        'avatar',
        'version',
        'character_points',
    ];

    protected $casts = [
        'attributes' => 'array',
        'proficiencies' => 'array',
        'level' => 'integer',
        'xp' => 'integer',
        'gold' => 'integer',
        'gems' => 'integer',
        'version' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getAttribute($attribute)
    {
        return $this->attributes[$attribute] ?? 0;
    }

    public function getAttributeValue($attribute): int
    {
        $attributes = $this->attributes['attributes'] ?? [];
        if (is_string($attributes)) {
            $attributes = json_decode($attributes, true);
        }
        return $attributes[$attribute] ?? 0;
    }

    public function getStrengthAttribute(): int
    {
        return $this->attributes['attributes']['str'] ?? 0;
    }

    public function getIntelligenceAttribute(): int
    {
        return $this->attributes['attributes']['int'] ?? 0;
    }

    public function getVitalityAttribute(): int
    {
        return $this->attributes['attributes']['vit'] ?? 0;
    }

    public function getAgilityAttribute(): int
    {
        return $this->attributes['attributes']['agi'] ?? 0;
    }

    public function getTotalAttributePoints(): int
    {
        $attrs = $this->attributes ?? [];
        return ($attrs['str'] ?? 0) + ($attrs['int'] ?? 0) + ($attrs['vit'] ?? 0) + ($attrs['agi'] ?? 0);
    }
}
