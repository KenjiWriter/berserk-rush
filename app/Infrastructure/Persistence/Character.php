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

    public function getTotalAttributePoints(): int
    {
        $attrs = $this->attributes ?? [];
        return ($attrs['str'] ?? 0) + ($attrs['int'] ?? 0) + ($attrs['vit'] ?? 0) + ($attrs['agi'] ?? 0);
    }
}
