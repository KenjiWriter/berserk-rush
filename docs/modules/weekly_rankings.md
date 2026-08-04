# Rankingi Tygodniowe (Weekly Rankings)

System rankingów tygodniowych umożliwia graczom rywalizację w 6 kategoriach. Dane zbierane są przez cały tydzień (poniedziałek 00:00 → niedziela 23:59), po czym co poniedziałek o 00:01 nagrody gem wysyłane są automatycznie mailem.

## Implementacja

- **Model:** `app/Infrastructure/Persistence/WeeklyRankingEntry.php`
- **Serwis:** `app/Application/Rankings/WeeklyRankingService.php`
- **Job:** `app/Jobs/WeeklyRankingRewardJob.php`
- **Scheduler:** `routes/console.php` → `weeklyOn(1, '00:01')`
- **UI:** `app/Livewire/City/Adventure.php` + `resources/views/livewire/city/adventure.blade.php`

## Kategorie Rankingów

| Kategoria | Klucz | Źródło inkrementacji |
|-----------|-------|----------------------|
| Pokonane Potwory | `monsters_killed` | `EncounterService::simulate()` — win + rank NOT boss/worldboss |
| Ukończone Lochy | `dungeons_completed` | `DungeonService` — `is_completed = true` (1 run = 1 pkt) |
| DMG World Bossa | `world_boss_damage` | `EncounterService::simulate()` — gałąź world boss, wartość `damageDealt` |
| Wbite Poziomy | `levels_gained` | `LevelUpService::checkAndApply()` — 1 pkt per level-up |
| Bossowie na Mapach | `map_bosses_killed` | `EncounterService::simulate()` — win + rank = boss |
| Zwycięstwa na Arenie | `arena_wins` | `PvPEncounterService` — wygrany atakujący |

## Nagrody

| Miejsce | Gemy | Tytuł Czasowy (7 dni) | Bonusy Pasywne Tytułu |
|---------|------|------------------------|------------------------|
| **1**   | 300  | `[Top 1 ...]` | **+5% Dedykowany Bonus** + Bazowe Statystyki |
| **2**   | 250  | `[Top 2 ...]` | **+3% Dedykowany Bonus** + Bazowe Statystyki |
| **3**   | 200  | `[Top 3 ...]` | **+1% Dedykowany Bonus** + Bazowe Statystyki |
| **4–10**| 100  | — | — |

### Zestaw Tytułów Czasowych per Kategoria:
- **Pokonane Potwory**: `[Top 1/2/3 Łowca]` → **+5%/3%/1% Obrażeń vs Potwory (PvE)** (`strong_vs_monsters`) + Atak (+20/+12/+6)
- **DMG World Bossa**: `[Top 1/2/3 Pogromca Bossów]` → **+5%/3%/1% Obrażeń vs Bossowie** (`strong_vs_bosses`) + Szansa na Kryta (+2%/1%/0.5%)
- **Ukończone Lochy**: `[Top 1/2/3 Zdobywca Lochów]` → **+5%/3%/1% Przebicie Pancerza** (`armor_pen_pct`) + Obrona (+15/+10/+5)
- **Wbite Poziomy**: `[Top 1/2/3 Mistrz Doświadczenia]` → **+5%/3%/1% EXP Bonus** (`exp_bonus`) + Wszystkie Statystyki (+10/+6/+3)
- **Bossowie na Mapach**: `[Top 1/2/3 Łowca Czempionów]` → **+5%/3%/1% Szansa na Podwójny Łup** (`double_drop_chance`) + Atak (+15/+10/+5)
- **Zwycięstwa na Arenie**: `[Top 1/2/3 Gladiator]` → **+5%/3%/1% Silny vs Bohaterów (PvP)** (`strong_vs_hero`) + Szansa na Unik (+2%/1%/0.5%)

Nagrody wysyłane mailem przez `SendMailAction` z załącznikiem `{'type': 'gems', 'qty': N}` oraz automatycznym odblokowaniem 7-dniowego tytułu czasowego. Tytuły wygasają automatycznie po 7 dniach.

## Baza Danych

```sql
weekly_ranking_entries
  id          ULID (PK)
  character_id VARCHAR FK → characters.id (CASCADE DELETE)
  week_start  DATE  -- zawsze poniedziałek
  category    VARCHAR(50)
  score       BIGINT UNSIGNED DEFAULT 0
  created_at, updated_at

UNIQUE (character_id, week_start, category)
INDEX  (week_start, category, score)
```

## Atomowy Upsert

`WeeklyRankingService::incrementScore()` korzysta z `INSERT ... ON CONFLICT DO UPDATE` (PostgreSQL native upsert) — bezpieczne nawet przy równoległych żądaniach walki.

## Harmonogram

```
co poniedziałek 00:01 → WeeklyRankingRewardJob::handle()
  → WeeklyRankingService::computeAndAwardWeekly()
      → getLeaderboard() dla poprzedniego tygodnia
      → SendMailAction dla Top 10 per kategoria
      → kasowanie wpisów starszych niż 4 tygodnie
```

Job **nie implementuje ShouldQueue** — wykonywany synchronicznie w procesie schedulera (analogicznie do `WorldBossRewardJob`).

## UI

Przycisk **„Rankingi Tygodniowe"** na stronie Przygody otwiera modal z:
- 6 zakładkami (po jednej na kategorię)
- Kafelkiem „Twoja pozycja" (zawsze widoczny, nawet gdy poza Top 10)
- Tabelą Top 10 z avatarami, wynikami i liczbą gemów do zdobycia
- Licznikiem do następnego resetu

Dane ładowane lazy — tylko gdy modal jest otwarty (`$showRankingsModal`).
