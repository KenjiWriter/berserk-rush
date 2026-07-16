<?php

namespace App\Infrastructure\Persistence;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class Character extends Model
{
    use HasUlids;

    protected $fillable = [
        'user_id',
        'name',
        'level',
        'xp',
        'gold',
        'attributes',
        'proficiencies',
        'avatar',
        'version',
        'character_points',
        'guild_id',
        'elo',
        'league',
        'arena_tokens',
        'pvp_refreshes_used',
        'pvp_refreshes_reset_at',
        'active_title_id',
        'achievement_points',
    ];

    protected $casts = [
        'attributes' => 'array',
        'proficiencies' => 'array',
        'level' => 'integer',
        'xp' => 'integer',
        'gold' => 'integer',
        'version' => 'integer',
        'elo' => 'integer',
        'arena_tokens' => 'integer',
        'pvp_refreshes_used' => 'integer',
        'pvp_refreshes_reset_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::updated(function ($character) {
            $character->clearStatsCache();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guild(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Guild::class, 'guild_id');
    }

    public function guildMember(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\GuildMember::class, 'character_id');
    }

    public function activeTitle(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'active_title_id');
    }

    public function unlockedTitles(): HasMany
    {
        return $this->hasMany(CharacterTitle::class, 'character_id');
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(CharacterAchievement::class, 'character_id');
    }

    public function bestiary(): HasMany
    {
        return $this->hasMany(CharacterBestiary::class, 'character_id');
    }

    public function pokedex(): HasMany
    {
        return $this->hasMany(CharacterPokedex::class, 'character_id');
    }

    public function getStrengthAttribute(): int
    {
        return $this->getAttribute('attributes')['str'] ?? 0;
    }

    public function getIntelligenceAttribute(): int
    {
        return $this->getAttribute('attributes')['int'] ?? 0;
    }

    public function getVitalityAttribute(): int
    {
        return $this->getAttribute('attributes')['vit'] ?? 0;
    }

    public function getAgilityAttribute(): int
    {
        return $this->getAttribute('attributes')['agi'] ?? 0;
    }

    public function getTotalAttributePoints(): int
    {
        $attrs = $this->getAttribute('attributes') ?? [];
        return ($attrs['str'] ?? 0) + ($attrs['int'] ?? 0) + ($attrs['vit'] ?? 0) + ($attrs['agi'] ?? 0);
    }

    public function items()
    {
        return $this->hasMany(ItemInstance::class, 'owner_character_id');
    }

    public function characterQuests(): HasMany
    {
        return $this->hasMany(CharacterQuest::class, 'character_id');
    }

    public function activeQuests()
    {
        return $this->characterQuests()->where('status', \App\Domain\Quests\Enums\QuestStatus::ACTIVE->value);
    }

    public function equippedItems()
    {
        return $this->items()->where('location', 'equipped')->with('template');
    }

    public function inventoryItems()
    {
        return $this->items()->where('location', 'inventory')->with('template');
    }

    public function activeBuffs(): HasMany
    {
        return $this->hasMany(ActiveBuff::class, 'character_id');
    }

    public function getActiveBuffs()
    {
        return $this->activeBuffs()->where('expires_at', '>', Carbon::now())->get();
    }

    public function getCacheKey(string $type): string
    {
        return "character:{$this->id}:{$type}";
    }

    public function clearStatsCache(): void
    {
        Cache::forget($this->getCacheKey('total_attributes'));
        Cache::forget($this->getCacheKey('equipment_stats'));
        Cache::forget($this->getCacheKey('max_hp'));
        Cache::forget($this->getCacheKey('combat_power'));
    }

    public function getTotalAttributes(): array
    {
        return Cache::remember($this->getCacheKey('total_attributes'), 3600, function () {
            $base = $this->getAttribute('attributes') ?? ['str' => 0, 'int' => 0, 'vit' => 0, 'agi' => 0];
            
            $total = [
                'str' => $base['str'] ?? 0,
                'int' => $base['int'] ?? 0,
                'vit' => $base['vit'] ?? 0,
                'agi' => $base['agi'] ?? 0,
            ];

            foreach ($this->equippedItems as $item) {
                $templateStats = $item->template->base_stats ?? [];
                $rollStats = $item->roll_stats ?? [];

                // Add template stats
                foreach (['str', 'int', 'vit', 'agi'] as $stat) {
                    $bonusKey = $stat . '_bonus';
                    if (isset($templateStats[$bonusKey])) {
                        $total[$stat] += $templateStats[$bonusKey];
                    }
                    if (isset($rollStats[$bonusKey])) {
                        // In a real app we might scale roll_stats by upgrade_level
                        $total[$stat] += $rollStats[$bonusKey];
                    }
                }
            }

            // Add Active Buffs
            foreach ($this->getActiveBuffs() as $buff) {
                $effects = $buff->effects ?? [];
                foreach (['str', 'int', 'vit', 'agi'] as $stat) {
                    $bonusKey = $stat . '_bonus';
                    if (isset($effects[$bonusKey])) {
                        $total[$stat] += $effects[$bonusKey];
                    }
                }
            }

            // Add Pet bonuses
            $equippedPet = Pet::where('character_id', $this->id)->where('is_equipped', true)->first();
            if ($equippedPet) {
                $petStats = $equippedPet->stats ?? [];
                foreach (['str', 'int', 'vit', 'agi'] as $stat) {
                    $total[$stat] += $petStats[$stat] ?? 0;
                }
            }

            // Add Title bonuses
            if ($this->active_title_id && $this->activeTitle) {
                $titleStats = $this->activeTitle->stats_bonus ?? [];
                foreach (['str', 'int', 'vit', 'agi'] as $stat) {
                    if (isset($titleStats[$stat])) {
                        $total[$stat] += $titleStats[$stat];
                    }
                    if (isset($titleStats[$stat . '_bonus'])) {
                        $total[$stat] += $titleStats[$stat . '_bonus'];
                    }
                }
            }

            // Add Achievement bonuses
            $completedAchievements = CharacterAchievement::with('achievement')
                ->where('character_id', $this->id)
                ->where('rewarded', true)
                ->get();
            foreach ($completedAchievements as $ca) {
                $achStats = $ca->achievement->stats_bonus ?? [];
                foreach (['str', 'int', 'vit', 'agi'] as $stat) {
                    if (isset($achStats[$stat])) {
                        $total[$stat] += $achStats[$stat];
                    }
                    if (isset($achStats[$stat . '_bonus'])) {
                        $total[$stat] += $achStats[$stat . '_bonus'];
                    }
                }
            }

            return $total;
        });
    }

    public function getEquipmentStats(): array
    {
        return Cache::remember($this->getCacheKey('equipment_stats'), 3600, function () {
            $stats = [
                'hp_bonus' => 0,
                'mana_bonus' => 0,
                'attack_min' => 0,
                'attack_max' => 0,
                'magic_attack_min' => 0,
                'magic_attack_max' => 0,
                'defense' => 0,
                'crit_chance' => 0,
            ];

            foreach ($this->equippedItems as $item) {
                $base = $item->template->base_stats ?? [];
                $roll = $item->roll_stats ?? [];
                $upgrade = $item->getUpgradeBonusStats();
                
                $stats['hp_bonus'] += ($base['hp_bonus'] ?? 0) + ($roll['hp_bonus'] ?? 0) + ($upgrade['hp_bonus'] ?? 0);
                $stats['mana_bonus'] += ($base['mana_bonus'] ?? 0) + ($roll['mana_bonus'] ?? 0) + ($upgrade['mana_bonus'] ?? 0);
                $stats['attack_min'] += ($base['attack_min'] ?? 0) + ($roll['attack_min'] ?? 0) + ($upgrade['attack_min'] ?? 0);
                $stats['attack_max'] += ($base['attack_max'] ?? 0) + ($roll['attack_max'] ?? 0) + ($upgrade['attack_max'] ?? 0);
                $stats['magic_attack_min'] += ($base['magic_attack_min'] ?? 0) + ($roll['magic_attack_min'] ?? 0) + ($upgrade['magic_attack_min'] ?? 0);
                $stats['magic_attack_max'] += ($base['magic_attack_max'] ?? 0) + ($roll['magic_attack_max'] ?? 0) + ($upgrade['magic_attack_max'] ?? 0);
                $stats['defense'] += ($base['defense'] ?? 0) + ($roll['defense'] ?? 0) + ($upgrade['defense'] ?? 0);
                $stats['crit_chance'] += ($base['crit_chance'] ?? 0) + ($roll['crit_chance'] ?? 0) + ($upgrade['crit_chance'] ?? 0);
                
                if (isset($roll['enchants']) && is_array($roll['enchants'])) {
                    foreach ($roll['enchants'] as $enchantType => $enchantValue) {
                        if (!isset($stats[$enchantType])) {
                            $stats[$enchantType] = 0;
                        }
                        $stats[$enchantType] += $enchantValue;
                    }
                }
            }

            // Add Active Buffs
            foreach ($this->getActiveBuffs() as $buff) {
                $effects = $buff->effects ?? [];
                $stats['hp_bonus'] += ($effects['hp_bonus'] ?? 0);
                $stats['mana_bonus'] += ($effects['mana_bonus'] ?? 0);
                $stats['attack_min'] += ($effects['attack_min'] ?? 0);
                $stats['attack_max'] += ($effects['attack_max'] ?? 0);
                $stats['magic_attack_min'] += ($effects['magic_attack_min'] ?? 0);
                $stats['magic_attack_max'] += ($effects['magic_attack_max'] ?? 0);
                $stats['defense'] += ($effects['defense'] ?? 0);
                $stats['crit_chance'] += ($effects['crit_chance'] ?? 0);
            }

            // Add Title bonuses
            if ($this->active_title_id && $this->activeTitle) {
                $titleStats = $this->activeTitle->stats_bonus ?? [];
                $stats['hp_bonus'] += ($titleStats['hp_bonus'] ?? 0) + ($titleStats['hp'] ?? 0);
                $stats['mana_bonus'] += ($titleStats['mana_bonus'] ?? 0);
                $stats['attack_min'] += ($titleStats['attack_min'] ?? 0);
                $stats['attack_max'] += ($titleStats['attack_max'] ?? 0);
                $stats['magic_attack_min'] += ($titleStats['magic_attack_min'] ?? 0);
                $stats['magic_attack_max'] += ($titleStats['magic_attack_max'] ?? 0);
                $stats['defense'] += ($titleStats['defense'] ?? 0);
                $stats['crit_chance'] += ($titleStats['crit_chance'] ?? 0);
                
                // You might want to copy other non-standard modifiers (like bonus_vs_demon) into stats directly
                foreach ($titleStats as $k => $v) {
                    if (!isset($stats[$k]) && str_starts_with($k, 'bonus_vs_')) {
                        $stats[$k] = $v;
                    }
                }
            }

            // Add Achievement bonuses
            $completedAchievements = CharacterAchievement::with('achievement')
                ->where('character_id', $this->id)
                ->where('rewarded', true)
                ->get();
            foreach ($completedAchievements as $ca) {
                $achStats = $ca->achievement->stats_bonus ?? [];
                $stats['hp_bonus'] += ($achStats['hp_bonus'] ?? 0) + ($achStats['hp'] ?? 0);
                $stats['mana_bonus'] += ($achStats['mana_bonus'] ?? 0);
                $stats['attack_min'] += ($achStats['attack_min'] ?? 0);
                $stats['attack_max'] += ($achStats['attack_max'] ?? 0);
                $stats['magic_attack_min'] += ($achStats['magic_attack_min'] ?? 0);
                $stats['magic_attack_max'] += ($achStats['magic_attack_max'] ?? 0);
                $stats['defense'] += ($achStats['defense'] ?? 0);
                $stats['crit_chance'] += ($achStats['crit_chance'] ?? 0);
                
                foreach ($achStats as $k => $v) {
                    if (!isset($stats[$k]) && str_starts_with($k, 'bonus_vs_')) {
                        $stats[$k] = $v;
                    } else if (isset($stats[$k]) && str_starts_with($k, 'bonus_vs_')) {
                        $stats[$k] += $v;
                    }
                }
            }

            return $stats;
        });
    }

    public function getMaxHp(): int
    {
        return Cache::remember($this->getCacheKey('max_hp'), 3600, function () {
            $vitality = $this->getTotalAttributes()['vit'] ?? 1;
            $eq = $this->getEquipmentStats();
            return 100 + ($vitality * 10) + ($this->level * 5) + ($eq['hp_bonus'] ?? 0);
        });
    }

    public function getTotalCombatPower(): int
    {
        return Cache::remember($this->getCacheKey('combat_power'), 3600, function () {
            $cp = 0;
            foreach ($this->equippedItems as $item) {
                $cp += $item->getCombatPower();
            }

            // Add Pet combat power
            $equippedPet = Pet::where('character_id', $this->id)->where('is_equipped', true)->first();
            if ($equippedPet) {
                $cp += $equippedPet->getCombatPower();
            }

            return $cp;
        });
    }

    public function pvpEncountersAsAttacker(): HasMany
    {
        return $this->hasMany(PvpEncounter::class, 'attacker_character_id');
    }

    public function pvpEncountersAsDefender(): HasMany
    {
        return $this->hasMany(PvpEncounter::class, 'defender_character_id');
    }

    public function createSnapshot(): array
    {
        return [
            'character_id' => $this->id,
            'name' => $this->name,
            'level' => $this->level,
            'attributes' => $this->getTotalAttributes(),
            'equipment_stats' => $this->getEquipmentStats(),
            'max_hp' => $this->getMaxHp(),
            'combat_power' => $this->getTotalCombatPower(),
        ];
    }

    public function getLeagueForElo(): string
    {
        $elo = $this->elo ?? 1000;

        if ($elo < 1200) {
            return 'bronze';
        }

        if ($elo < 1500) {
            return 'silver';
        }

        if ($elo < 1800) {
            return 'gold';
        }

        return 'platinum';
    }
}
