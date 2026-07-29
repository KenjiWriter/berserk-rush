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

    public const MAX_DAILY_PVP_FIGHTS = 5;

    // UWAGA (rebalans obrażeń/HP, 2026-07-28): przedmioty dają teraz o 25% mniej
    // płaskich statystyk (attack_min/max, magic_attack_min/max, magic_burst_min/max,
    // hp_bonus - patrz seedery), a w zamian atrybuty postaci (STR/INT/AGI dla
    // obrażeń, VIT dla HP) liczą się mocniej w tych samych formułach - o +50%
    // względem poprzednich, "gołych" wartości STR/INT/AGI i VIT*10. Efekt: przy
    // typowym zestawie na poziomie 50 udział atrybutów w obrażeniach rośnie z ok.
    // 31% do ok. 46% całości, przy praktycznie tej samej sumie obrażeń (zmiana
    // źródła mocy, a nie jej redukcja). Ta sama stała jest używana w
    // `PvPEncounterService::resolveTurn()` (tam liczba jest duplikowana z powodu
    // działania na snapshotach, nie na żywym modelu `Character`).
    public const ATTRIBUTE_DAMAGE_MULTIPLIER = 1.5;
    // Analogicznie: mnożnik VIT->HP podniesiony z 10 do 15 (+50%), przy tym samym
    // -25% cięciu `hp_bonus` z przedmiotów.
    public const ATTRIBUTE_HP_MULTIPLIER = 15;

    protected $fillable = [
        'user_id',
        'name',
        'level',
        'xp',
        'auto_donate_exp_guild',
        'gold',
        'attributes',
        'proficiencies',
        'avatar',
        'version',
        'character_points',
        'skill_points',
        'guild_id',
        'elo',
        'league',
        'arena_tokens',
        'pvp_refreshes_used',
        'pvp_refreshes_reset_at',
        'daily_pvp_fights_used',
        'daily_pvp_fights_last_reset_at',
        'active_title_id',
        'achievement_points',
        'current_location',
        'last_active_at',
    ];

    protected $casts = [
        'attributes' => 'array',
        'proficiencies' => 'array',
        'level' => 'integer',
        'xp' => 'integer',
        'auto_donate_exp_guild' => 'boolean',
        'gold' => 'integer',
        'version' => 'integer',
        'elo' => 'integer',
        'arena_tokens' => 'integer',
        'pvp_refreshes_used' => 'integer',
        'pvp_refreshes_reset_at' => 'datetime',
        'daily_pvp_fights_used' => 'integer',
        'daily_pvp_fights_last_reset_at' => 'datetime',
        'last_active_at' => 'datetime',
    ];

    public function checkAndResetDailyPvpFights(): void
    {
        $todayStart = now()->startOfDay();

        if (!$this->daily_pvp_fights_last_reset_at || $this->daily_pvp_fights_last_reset_at->lt($todayStart)) {
            $this->update([
                'daily_pvp_fights_used' => 0,
                'daily_pvp_fights_last_reset_at' => now(),
            ]);
        }
    }

    public function getRemainingDailyPvpFights(): int
    {
        $this->checkAndResetDailyPvpFights();
        return max(0, self::MAX_DAILY_PVP_FIGHTS - ($this->daily_pvp_fights_used ?? 0));
    }

    public function isOnline(): bool
    {
        return $this->last_active_at && $this->last_active_at->diffInMinutes(now()) < 5;
    }

    protected static function booted()
    {
        static::saving(function ($character) {
            if (($character->level ?? 0) >= \App\Application\Characters\LevelUpService::MAX_LEVEL) {
                $character->level = \App\Application\Characters\LevelUpService::MAX_LEVEL;
                $maxXp = max(0, app(\App\Application\Characters\LevelUpService::class)->xpToNext(\App\Application\Characters\LevelUpService::MAX_LEVEL) - 1);
                if ($character->xp > $maxXp) {
                    $character->xp = $maxXp;
                }
            }
        });

        static::updated(function ($character) {
            $character->clearStatsCache();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getGemsAttribute(): int
    {
        return $this->user?->gems ?? \Illuminate\Support\Facades\Auth::user()?->gems ?? 0;
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

    public function equipmentSetItems(): HasMany
    {
        return $this->hasMany(CharacterEquipmentSetItem::class);
    }

    public function equipmentSetItemsFor(string $setType)
    {
        return $this->equipmentSetItems()->where('set_type', $setType)->with('itemInstance.template');
    }

    public function inventoryItems()
    {
        return $this->items()->where('location', 'inventory')->whereHas('template', function($q) {
            $q->where('type', '!=', 'material');
        })->with('template');
    }

    public function materialStashItems()
    {
        return $this->items()->where('location', 'material_stash')->with('template');
    }

    public function getBackpackCapacity(): int
    {
        return $this->user?->hasPremium() ? 64 : 32;
    }

    public function getBackpackCount(): int
    {
        return $this->inventoryItems()->count();
    }

    public function isBackpackFull(): bool
    {
        return $this->getBackpackCount() >= $this->getBackpackCapacity();
    }

    public function getMaterialStashCapacity(): int
    {
        return 100;
    }

    public function getMaterialStashCount(): int
    {
        return $this->items()->where('location', 'material_stash')->count();
    }

    public function isMaterialStashFull(): bool
    {
        return $this->getMaterialStashCount() >= $this->getMaterialStashCapacity();
    }

    public function combatSkills(): HasMany
    {
        return $this->hasMany(CharacterCombatSkill::class, 'character_id');
    }

    public function equippedSkills()
    {
        return $this->combatSkills()->where('is_equipped', true)->with('skill');
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
        $this->unsetRelation('equippedItems');
        $this->unsetRelation('inventoryItems');
    }

    public function syncMissingPoints(): void
    {
        $totalAttrPoints = 10 + max(0, ($this->level - 1) * 3);
        $attrs = $this->getAttribute('attributes') ?? [];
        $spentAttrPoints = ($attrs['str'] ?? 0) + ($attrs['int'] ?? 0) + ($attrs['vit'] ?? 0) + ($attrs['agi'] ?? 0);
        
        $currentTotalAttrPoints = ($this->character_points ?? 0) + $spentAttrPoints;
        if ($currentTotalAttrPoints < $totalAttrPoints) {
            $missingAttrPoints = $totalAttrPoints - $currentTotalAttrPoints;
            $this->character_points = ($this->character_points ?? 0) + $missingAttrPoints;
            $this->save();
        }

        $expectedSkillPoints = max(0, ($this->level - 1) * 3);
        $unlockedSkills = \App\Infrastructure\Persistence\CharacterCombatSkill::with('skill')
            ->where('character_id', $this->id)
            ->get();

        $spentSkillPoints = 0;
        foreach ($unlockedSkills as $cs) {
            // Capping skill level at max 5
            if ($cs->level > 5) {
                $cs->level = 5;
                $cs->save();
            }
            $unlockCost = $cs->skill->unlock_cost ?? 1;
            $upgrades = max(0, $cs->level - 1);
            $spentSkillPoints += ($unlockCost + $upgrades);
        }

        $currentTotalSkillPoints = ($this->skill_points ?? 0) + $spentSkillPoints;
        if ($currentTotalSkillPoints < $expectedSkillPoints) {
            $missingSkillPoints = $expectedSkillPoints - $currentTotalSkillPoints;
            $this->skill_points = ($this->skill_points ?? 0) + $missingSkillPoints;
            $this->save();
        }
    }

    public function getBaseAttributes(): array
    {
        $attrs = $this->getAttribute('attributes') ?? [];
        return [
            'str' => (int)($attrs['str'] ?? 0),
            'int' => (int)($attrs['int'] ?? 0),
            'vit' => (int)($attrs['vit'] ?? 0),
            'agi' => (int)($attrs['agi'] ?? 0),
        ];
    }

    public function getBonusAttributes(): array
    {
        $total = $this->getTotalAttributes();
        $base = $this->getBaseAttributes();

        return [
            'str' => max(0, ($total['str'] ?? 0) - $base['str']),
            'int' => max(0, ($total['int'] ?? 0) - $base['int']),
            'vit' => max(0, ($total['vit'] ?? 0) - $base['vit']),
            'agi' => max(0, ($total['agi'] ?? 0) - $base['agi']),
        ];
    }

    /**
     * Rozwiązuje efektywny zestaw przedmiotów dla wyliczeń bojowych: dla $setType
     * === null zwraca aktualnie fizycznie założone przedmioty (jak dotychczas);
     * dla setów wirtualnych (pvp/guild_war/set_1..3) bierze zapisany przedmiot per
     * slot z `character_equipment_set_items`, a gdy dany slot nie jest
     * skonfigurowany (albo zapisany przedmiot nie należy już do postaci) - spada
     * per-slot na aktualnie założony przedmiot (patrz docs/modules/profile_and_equipment.md).
     */
    private function resolveEffectiveEquipment(?string $setType = null): \Illuminate\Support\Collection
    {
        if ($setType === null) {
            return $this->equippedItems;
        }

        $savedBySlot = $this->equipmentSetItemsFor($setType)->get()->keyBy('slot');
        $liveBySlot = $this->equippedItems->keyBy(fn ($item) => $item->template->slot);

        return collect(CharacterEquipmentSetItem::SLOTS)
            ->map(function (string $slot) use ($savedBySlot, $liveBySlot) {
                $saved = $savedBySlot->get($slot)?->itemInstance;
                if ($saved && $saved->owner_character_id === $this->id) {
                    return $saved;
                }

                return $liveBySlot->get($slot);
            })
            ->filter()
            ->values();
    }

    public function getTotalAttributes(?string $setType = null): array
    {
        $compute = function () use ($setType) {
            $base = $this->getAttribute('attributes') ?? ['str' => 0, 'int' => 0, 'vit' => 0, 'agi' => 0];

            $total = [
                'str' => $base['str'] ?? 0,
                'int' => $base['int'] ?? 0,
                'vit' => $base['vit'] ?? 0,
                'agi' => $base['agi'] ?? 0,
            ];

            foreach ($this->resolveEffectiveEquipment($setType) as $item) {
                $templateStats = $item->getResolvedBaseStats();
                $rollStats = $item->roll_stats ?? [];
                $enchantStats = $rollStats['enchants'] ?? [];
                $upgradeStats = $item->getUpgradeBonusStats();

                // Add template stats
                foreach (['str', 'int', 'vit', 'agi'] as $stat) {
                    $bonusKey = $stat . '_bonus';
                    if (isset($templateStats[$bonusKey])) {
                        $total[$stat] += $templateStats[$bonusKey];
                    }
                    if (isset($rollStats[$bonusKey])) {
                        $total[$stat] += $rollStats[$bonusKey];
                    }
                    if (isset($enchantStats[$bonusKey])) {
                        $total[$stat] += $enchantStats[$bonusKey];
                    }
                    if (isset($upgradeStats[$bonusKey])) {
                        $total[$stat] += $upgradeStats[$bonusKey];
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
        };

        if ($setType === null) {
            return Cache::remember($this->getCacheKey('total_attributes'), 3600, $compute);
        }

        return $compute();
    }

    public function getEquipmentStats(?string $setType = null): array
    {
        $compute = function () use ($setType) {
            $stats = [
                'hp_bonus' => 0,
                'mana_bonus' => 0,
                'attack_min' => 0,
                'attack_max' => 0,
                'magic_attack_min' => 0,
                'magic_attack_max' => 0,
                'defense' => 0,
                'crit_chance' => 0,
                // "Magic burst": dodatkowe, oddzielne obrażenia magiczne z pewną szansą
                // na trafienie (używane przez Dzwony - patrz EncounterService).
                'magic_burst_chance' => 0,
                'magic_burst_min' => 0,
                'magic_burst_max' => 0,
            ];

            // 'attack_power'/'magic_attack' (2026-07-29): afiksy z Czarodzieja/Wiedźmy
            // wyrażone teraz w % (nie płaskiej wartości) - patrz EnchantmentStrategy,
            // gdzie wylosowanie wysokiego % jest celowo rzadkie (rozkład wykładniczy,
            // wzorem Metin2 FMS/zatrutego miecza). Sumujemy je osobno i mnożymy przez
            // całościowe attack_min/max/magic_attack_min/max PO zsumowaniu wkładu
            // wszystkich przedmiotów (a nie jako płaski dodatek per-item), żeby "+30%
            // obrażeń fizycznych" realnie skalowało już wyliczony atak z broni.
            $attackPowerPct = 0;
            $magicAttackPct = 0;
            // 'hp_bonus'/'defense' (2026-07-29): afiksy z Wiedźmy wyrażone teraz w %
            // (nie płaskiej wartości), ten sam hazard co attack_power/magic_attack -
            // patrz EnchantmentStrategy. Zsumowane osobno i zastosowane mnożnikowo
            // do sumarycznego hp_bonus/defense z przedmiotów, PO zsumowaniu wkładu
            // wszystkich sztuk ekwipunku (nie jako płaski dodatek per-item).
            $hpBonusPct = 0;
            $defensePct = 0;

            foreach ($this->resolveEffectiveEquipment($setType) as $item) {
                $base = $item->getResolvedBaseStats();
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
                $stats['magic_burst_chance'] += ($base['magic_burst_chance'] ?? 0) + ($roll['magic_burst_chance'] ?? 0) + ($upgrade['magic_burst_chance'] ?? 0);
                $stats['magic_burst_min'] += ($base['magic_burst_min'] ?? 0) + ($roll['magic_burst_min'] ?? 0) + ($upgrade['magic_burst_min'] ?? 0);
                $stats['magic_burst_max'] += ($base['magic_burst_max'] ?? 0) + ($roll['magic_burst_max'] ?? 0) + ($upgrade['magic_burst_max'] ?? 0);

                if (isset($roll['enchants']) && is_array($roll['enchants'])) {
                    foreach ($roll['enchants'] as $enchantType => $enchantValue) {
                        if ($enchantType === 'attack_power') {
                            $attackPowerPct += $enchantValue;
                            continue;
                        }
                        if ($enchantType === 'magic_attack') {
                            $magicAttackPct += $enchantValue;
                            continue;
                        }
                        if ($enchantType === 'hp_bonus') {
                            $hpBonusPct += $enchantValue;
                            continue;
                        }
                        if ($enchantType === 'defense') {
                            $defensePct += $enchantValue;
                            continue;
                        }

                        if (!isset($stats[$enchantType])) {
                            $stats[$enchantType] = 0;
                        }
                        $stats[$enchantType] += $enchantValue;
                    }
                }
            }

            // !== 0 (nie > 0): zakres [-20, 50] pozwala na wynik ujemny (osłabienie broni),
            // więc trzeba go zastosować niezależnie od znaku - patrz EnchantmentStrategy.
            if ($attackPowerPct !== 0) {
                $stats['attack_min'] = max(0, (int) round($stats['attack_min'] * (1 + $attackPowerPct / 100)));
                $stats['attack_max'] = max(0, (int) round($stats['attack_max'] * (1 + $attackPowerPct / 100)));
            }
            if ($magicAttackPct !== 0) {
                $stats['magic_attack_min'] = max(0, (int) round($stats['magic_attack_min'] * (1 + $magicAttackPct / 100)));
                $stats['magic_attack_max'] = max(0, (int) round($stats['magic_attack_max'] * (1 + $magicAttackPct / 100)));
            }
            if ($hpBonusPct !== 0) {
                $stats['hp_bonus'] = max(0, (int) round($stats['hp_bonus'] * (1 + $hpBonusPct / 100)));
            }
            if ($defensePct !== 0) {
                $stats['defense'] = max(0, (int) round($stats['defense'] * (1 + $defensePct / 100)));
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
        };

        if ($setType === null) {
            return Cache::remember($this->getCacheKey('equipment_stats'), 3600, $compute);
        }

        return $compute();
    }

    public function getMaxHp(?string $setType = null): int
    {
        $compute = function () use ($setType) {
            $vitality = $this->getTotalAttributes($setType)['vit'] ?? 1;
            $eq = $this->getEquipmentStats($setType);
            return 100 + ($vitality * self::ATTRIBUTE_HP_MULTIPLIER) + ($this->level * 5) + ($eq['hp_bonus'] ?? 0);
        };

        if ($setType === null) {
            return Cache::remember($this->getCacheKey('max_hp'), 3600, $compute);
        }

        return $compute();
    }

    public function getTotalCombatPower(?string $setType = null): int
    {
        $compute = function () use ($setType) {
            $cp = 0;
            foreach ($this->resolveEffectiveEquipment($setType) as $item) {
                $cp += $item->getCombatPower();
            }

            // Add Pet combat power
            $equippedPet = Pet::where('character_id', $this->id)->where('is_equipped', true)->first();
            if ($equippedPet) {
                $cp += $equippedPet->getCombatPower();
            }

            return $cp;
        };

        if ($setType === null) {
            return Cache::remember($this->getCacheKey('combat_power'), 3600, $compute);
        }

        return $compute();
    }

    public function pvpEncountersAsAttacker(): HasMany
    {
        return $this->hasMany(PvpEncounter::class, 'attacker_character_id');
    }

    public function pvpEncountersAsDefender(): HasMany
    {
        return $this->hasMany(PvpEncounter::class, 'defender_character_id');
    }

    /**
     * @param string|null $setType Gdy podane (patrz CharacterEquipmentSetItem::ALL_SETS),
     *   snapshot liczy statystyki z zapisanego zestawu (pvp/guild_war/...) zamiast
     *   z aktualnie fizycznie założonego ekwipunku - patrz resolveEffectiveEquipment().
     */
    public function createSnapshot(?string $setType = null): array
    {
        // Ensure skills are loaded
        $this->loadMissing('equippedSkills.skill');

        $skillsData = $this->equippedSkills->map(function($charSkill) {
            return [
                'id' => $charSkill->skill->id,
                'name' => $charSkill->skill->name,
                'type' => $charSkill->skill->type, // active, passive
                'effect_type' => $charSkill->skill->effect_type,
                'is_magic' => (bool) $charSkill->skill->is_magic,
                'is_aoe' => (bool) $charSkill->skill->is_aoe,
                'base_cooldown' => $charSkill->skill->base_cooldown,
                'base_duration' => $charSkill->skill->base_duration,
                'base_value' => $charSkill->skill->base_value,
                'scaling_value' => $charSkill->skill->scaling_value,
                'level' => $charSkill->level,
                'required_weapon_type' => $charSkill->skill->required_weapon_type,
            ];
        })->toArray();

        // Check weapon type accurately
        $weaponType = $this->getEquippedWeaponType($setType);

        return [
            'character_id' => $this->id,
            'name' => $this->name,
            'level' => $this->level,
            'attributes' => $this->getTotalAttributes($setType),
            'equipment_stats' => $this->getEquipmentStats($setType),
            'max_hp' => $this->getMaxHp($setType),
            'combat_power' => $this->getTotalCombatPower($setType),
            'skills' => $skillsData,
            'weapon_type' => $weaponType,
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

    public function getEquippedWeaponType(?string $setType = null): string
    {
        $weapon = $this->resolveEffectiveEquipment($setType)
            ->first(fn ($item) => $item->template?->slot === 'main_hand');

        if (!$weapon || !$weapon->template) {
            return 'barehands';
        }

        if (!empty($weapon->template->sub_type)) {
            return $weapon->template->sub_type;
        }

        $name = mb_strtolower($weapon->template->name, 'UTF-8');

        if (str_contains($name, 'miecz') || str_contains($name, 'ostrze') || str_contains($name, 'pałasz') || str_contains($name, 'sabaty') || str_contains($name, 'sword')) {
            return 'sword';
        }
        if (str_contains($name, 'topór') || str_contains($name, 'rozłupywacz') || str_contains($name, 'maczuga') || str_contains($name, 'axe')) {
            return 'axe';
        }
        if (str_contains($name, 'łuk') || str_contains($name, 'kusza') || str_contains($name, 'bow')) {
            return 'bow';
        }
        if (str_contains($name, 'różdżka') || str_contains($name, 'kostur') || str_contains($name, 'laska') || str_contains($name, 'wand')) {
            return 'wand';
        }
        if (str_contains($name, 'dzwon') || str_contains($name, 'gong') || str_contains($name, 'bell')) {
            return 'bell';
        }
        if (str_contains($name, 'sztylet') || str_contains($name, 'sztylety') || str_contains($name, 'nóż') || str_contains($name, 'dagger')) {
            return 'dagger';
        }

        return 'sword';
    }

    public function getAttributeAttackBonus(?string $weaponType = null): int
    {
        $attributes = $this->getTotalAttributes();
        $weaponType = $weaponType ?? $this->getEquippedWeaponType();

        $str = $attributes['str'] ?? 0;
        $int = $attributes['int'] ?? 0;
        $agi = $attributes['agi'] ?? 0;

        $rawBonus = match ($weaponType) {
            'bow', 'sword', 'dagger' => $str + $agi,
            'bell' => $str + $int,
            'wand' => $int * 2,
            'axe' => $str * 2,
            default => $str * 2,
        };

        return (int) round($rawBonus * self::ATTRIBUTE_DAMAGE_MULTIPLIER);
    }
}
