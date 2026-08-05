<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class Monster extends Model
{
    protected $fillable = [
        'map_id',
        'name',
        'type',
        'rank',
        'level',
        'stats',
        'abilities',
        'loot_table_id',
        'avatar',
    ];

    protected $casts = [
        'map_id' => 'integer',
        'level' => 'integer',
        'stats' => 'array',
        'abilities' => 'array',
        'loot_table_id' => 'integer',
        'type' => \App\Domain\Combat\Enums\MonsterType::class,
        'rank' => \App\Domain\Combat\Enums\MonsterRank::class,
    ];

    public function map()
    {
        return $this->belongsTo(Map::class);
    }

    public function dungeonStages()
    {
        return $this->hasMany(DungeonStage::class);
    }

    public function lootTable()
    {
        return $this->belongsTo(LootTable::class);
    }

    public function getMaxHpAttribute(): int
    {
        return $this->stats['hp'] ?? 100;
    }

    public function getAttackAttribute(): int
    {
        return $this->stats['atk'] ?? 10;
    }

    public function getDefenseAttribute(): int
    {
        return $this->stats['def'] ?? 5;
    }

    public function getAgilityAttribute(): int
    {
        return $this->stats['agi'] ?? 5;
    }

    public function getIntelligenceAttribute(): int
    {
        return $this->stats['int'] ?? 1;
    }

    public function getCritChanceAttribute(): float
    {
        return $this->stats['crit'] ?? 0.05;
    }

    public function getDodgeChanceAttribute(): float
    {
        return $this->stats['dodge'] ?? 0.02;
    }

    /**
     * Umiejętności bojowe potwora (Faza 2 rebalansu, 2026-08-05). Przechowywane w
     * istniejącej kolumnie JSON `abilities` ("efekty specjalne") pod kluczem `skills`,
     * żeby nie mnożyć tabel/FK dla 1-2 skilli per potwór. Zwraca znormalizowaną listę
     * skilli, z których każdy silnik walki (EncounterService/DungeonService/
     * LocationEventService - parytet, patrz docs/modules/combat.md pkt 9) potrafi
     * odczytać efekt.
     *
     * Kształt pojedynczego skilla (wszystkie klucze poza `effect_type` opcjonalne):
     *   effect_type: direct_dmg|poison|fire|stun|freeze|heal
     *   is_magic:    bool  - dmg pokazywany jako magicDamage (archetyp Maga)
     *   value:       float - mnożnik dmg (direct_dmg/stun/freeze) LUB % (poison/fire/heal)
     *   duration:    int   - liczba tur (DoT/CC)
     *   cooldown:    int   - odnowienie w turach
     *   chance:      int   - % szansy na użycie, gdy gotowy (AI niedeterministyczne)
     *   name/icon:   string - do logu/UI
     */
    public function getCombatSkills(): array
    {
        $abilities = $this->abilities ?? [];
        // Historycznie `abilities` bywało pustą tablicą `[]` (nie obiektem) - odczyt defensywny.
        $skills = is_array($abilities) ? ($abilities['skills'] ?? []) : [];
        if (!is_array($skills)) {
            return [];
        }

        $normalized = [];
        foreach ($skills as $s) {
            if (!is_array($s) || empty($s['effect_type'])) {
                continue;
            }
            $normalized[] = [
                'name' => (string) ($s['name'] ?? 'Umiejętność'),
                'effect_type' => (string) $s['effect_type'],
                'is_magic' => (bool) ($s['is_magic'] ?? false),
                'value' => (float) ($s['value'] ?? 0),
                'duration' => (int) ($s['duration'] ?? 0),
                'cooldown' => max(0, (int) ($s['cooldown'] ?? 0)),
                'chance' => max(0, min(100, (int) ($s['chance'] ?? 100))),
                'icon' => $s['icon'] ?? null,
            ];
        }

        return $normalized;
    }

    public function isCaster(): bool
    {
        $abilities = $this->abilities ?? [];
        if (is_array($abilities) && !empty($abilities['is_caster'])) {
            return true;
        }
        foreach ($this->getCombatSkills() as $skill) {
            if ($skill['is_magic']) {
                return true;
            }
        }
        return false;
    }

    public function getScaledStats(int $playerLevel = 1, bool $isTutorial = false): array
    {
        if ($isTutorial) {
            return [
                'hp' => 35,
                'atk' => 6,
                'def' => 2,
                'agi' => 3,
                'int' => 1,
                'crit' => 0.05,
                'dodge' => 0.02,
            ];
        }

        return [
            'hp' => (int) ($this->stats['hp'] ?? ($this->level * 20)),
            'atk' => (int) ($this->stats['atk'] ?? ($this->level * 2)),
            'def' => (int) ($this->stats['def'] ?? $this->level),
            'agi' => (int) ($this->stats['agi'] ?? $this->level),
            'int' => (int) ($this->stats['int'] ?? 1),
            'crit' => (float) ($this->stats['crit'] ?? 0.05),
            'dodge' => (float) ($this->stats['dodge'] ?? 0.02),
        ];
    }
}
