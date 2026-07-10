<?php

namespace App\Models;

use App\Infrastructure\Persistence\Character;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guild extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'title',
        'min_level',
        'is_public',
        'level',
        'xp',
        'gold',
        'gems',
        'bonus_xp_level',
        'bonus_gold_level',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'level' => 'integer',
        'min_level' => 'integer',
        'xp' => 'integer',
        'gold' => 'integer',
        'gems' => 'integer',
        'bonus_xp_level' => 'integer',
        'bonus_gold_level' => 'integer',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(GuildMember::class, 'guild_id');
    }

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class, 'guild_id');
    }

    public function getMaxMembers(): int
    {
        return 15 + (int) floor($this->level * 0.35); // level 0 = 15, level 100 = 50
    }

    public function getMaxGold(): int
    {
        // level 0 = 5000, level 100 = 10,000,000
        return 5000 + ($this->level * 99950);
    }

    public function getMaxGems(): int
    {
        // level 0 = 100, level 100 = 1000
        return 100 + ($this->level * 9);
    }

    public function getRequiredXpForNextLevel(): ?int
    {
        if ($this->level >= 100) {
            return null;
        }

        // 10x required xp of a character at the same level
        // and scales with max members.
        $baseCharacterXp = 100 + pow($this->level, 1.8) * 10;
        $memberMultiplier = 1 + ($this->getMaxMembers() / 15);

        return (int) round($baseCharacterXp * 10 * $memberMultiplier);
    }

    public function addXp(int $amount): void
    {
        if ($this->level >= 100) return;

        $this->xp += $amount;
        $req = $this->getRequiredXpForNextLevel();

        while ($req !== null && $this->xp >= $req) {
            $this->xp -= $req;
            $this->level++;
            if ($this->level >= 100) {
                $this->xp = 0;
                break;
            }
            $req = $this->getRequiredXpForNextLevel();
        }

        $this->save();
    }
}
