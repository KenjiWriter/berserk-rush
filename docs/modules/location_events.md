# Moduł Eventów Lokacji (Location Events)

Faza 1 (2026-08-05): backend i dane. **UI (modal wyboru, ekran przebiegu, animacje)
nie jest jeszcze podpięty** - `MapStub::startBattle()` nie wywołuje jeszcze
`LocationEventService::rollEventTrigger()`. Ten moduł opisuje gotowy, przetestowany
jednostkowo backend, czekający na integrację z eksploracją mapy.

## Koncepcja

Podczas eksploracji mapy gracz może (docelowo, po podpięciu UI) natrafić na rzadki,
tematyczny **event lokacji** - łańcuch 2-10 kolejnych potworów (ostatni zawsze to
boss) z podbitymi nagrodami i szansą na bonusowe skrzynie. Przy wejściu w event gracz
wybiera tryb:

- **Normalny** - HP resetuje się do pełna przed każdym kolejnym potworem w łańcuchu
  (tak jak zwykła eksploracja).
- **Hardcore** - HP przenosi się przez cały łańcuch bez regeneracji (jedyne leczenie
  to skille lecznicze i mikstury), w zamian za **+50% do gold/exp/dropu**
  (`LocationEventService::HARDCORE_REWARD_BONUS_MULTIPLIER` - stała, świadomie
  przestrajalna, jak `XP_CURVE_MULTIPLIER` w `LevelUpService`).

Porażka w KTÓRYMKOLWIEK starciu (niezależnie od trybu) kończy cały event i **odbiera
cały skumulowany łup** z tego runu - to realna stawka nawet w trybie normalnym, nie
tylko hardcore.

## Architektura

Wzorowana 1:1 na lochach (`App\Application\Dungeon\DungeonService` +
`CharacterDungeonRun`) - własny, w pełni inline silnik walki (bez tworzenia rekordów
`Encounter`), HP/mana trzymane w rekordzie runu, symulacja pojedynczego starcia
zlecana asynchronicznie przez Job, nagrody kumulowane i przyznawane dopiero po
ukończeniu całego runu. Zgodnie z konwencją "parytetu silników" (patrz
`docs/modules/combat.md` pkt 9), silnik walki jest świadomie duplikowany, a nie
współdzielony z `EncounterService`/`DungeonService`.

### Nazewnictwo

`Map::$tier` / `Map::getPlayerTier()` już oznaczają inne pojęcie (poziom gracza vs
trudność mapy). Ranga eventu (T1-T6 z projektu) nazywana jest w kodzie **`rank`**
(kolumna `location_events.rank`), żeby uniknąć kolizji.

### Tabele danych (statyczne, seedowane `LocationEventSeeder`)

**`location_events`** (6 wierszy, ranga 1-6):
| rank | name | spawn_chance_pct | monster_count | attack_mult | reward_mult | group_chance | chest |
|---|---|---|---|---|---|---|---|
| 1 | Jaskinie | 20% | 2-5 | 1.00 | 1.00 | brak | 0-1 |
| 2 | Ruiny Wioski | 15% | 3-6 | 1.00 | 1.00 | brak | 0-1 |
| 3 | Kaplica na Cmentarzu | 10% | 4-7 | 1.00 | 1.00 | brak | 1 |
| 4 | Stara Warownia | 5% | 5-8 | 1.10 | 1.10 | 5%, maks. 2 | 1-2 |
| 5 | Twierdza Upadłego Króla | 4% | 6-9 | 1.20 | 1.20 | 5%, maks. 3 | 2 |
| 6 | Przeklęta Metropolia | 2% | 7-10 | 1.30 | 1.30 | 5%, maks. 4 | 3 |

Suma szans = 56% - pozostałe 44% rzutu to brak eventu (normalna eksploracja).

**`location_event_upgrade_levels`** (6 wierszy, poziom 0-5) - drugi, niezależny rzut
wykonywany TYLKO gdy event już się wylosował, dodatkowo skalujący liczbę
potworów/atak/HP/exp/drop/szansę na grupę/bonus skrzyń. Sumy szans = 100%. Pełne
wartości - patrz `LocationEventSeeder`.

**`character_location_event_runs`** - jeden aktywny run na postać (analogiczna
blokada jak w `CharacterDungeonRun`). `monsters_queue` (json) to lista slotów
wylosowana w `startRun()`: każdy slot ma `monster_id`, `is_boss` (tylko ostatni slot),
`is_group`/`group_size` (rzucane per-slot wg `group_chance_pct` eventu +
`bonus_group_chance_pct` poziomu ulepszenia, potwory nie-bossowe).

## `LocationEventService`

- **`rollEventTrigger(): ?array`** - losuje rangę (wg `spawn_chance_pct`) i poziom
  ulepszenia (wg `roll_chance_pct`, niezależny rzut). Zwraca `null` gdy event nie
  wypadnie (44% szans).
- **`startRun(Character, Map, LocationEvent, LocationEventUpgradeLevel, bool $isHardcore): Result`**
  - liczba potworów = `monster_count_min/max`(rank) + `monster_count_delta_min/max`(poziom ulepszenia);
  - ostatni slot wymuszony jako boss (z puli `Map::explorationMonsters()` o randze
    `boss`, fallback - najsilniejszy zwykły potwór mapy);
  - staty potworów skalowane: **atak/def** = `Monster::getScaledStats()` ×
    `attack_multiplier`(rank) × `attack_multiplier`(poziom ulepszenia); **HP** =
    `getScaledStats()` × `hp_multiplier`(TYLKO poziom ulepszenia - baza rangi nie ma
    osobnego mnożnika HP, zgodnie z arkuszem projektowym); `agi`/`crit`/`dodge`
    nieskalowane;
  - `current_hp`/`current_mana` = pełne staty postaci na starcie runu (niezależnie od
    trybu - hardcore wpływa tylko na regenerację MIĘDZY kolejnymi potworami).
- **`fightNextMonster(CharacterLocationEventRun): Result`** - jak
  `DungeonService::fightCurrentStage()`, dispatchuje `SimulateLocationEventStageJob`.
- **`simulateStage(CharacterLocationEventRun): Result`** - symulacja walki ze
  slotem (pojedynczy potwór lub grupa - reużywa mechanikę z `over-level`/`DungeonService`
  "group_mob": wspólna pula HP, gracz atakuje pierwszego żywego, AOE bije wszystkich).
  Po zwycięstwie: nagroda = bazowe gold/xp (formuła 1:1 z
  `EncounterService::calculateGoldReward/calculateXpReward`) × globalny mnożnik
  (`RewardMultiplierService`) × `reward_multiplier`(rank) ×
  `exp_multiplier`/`drop_multiplier`(poziom ulepszenia) × (`×1.5` jeśli hardcore).
  Item z tabeli zrzutów potwora losowany WYŁĄCZNIE na slocie bossa (ostatnim w
  łańcuchu) - analogicznie do `DungeonService::calculateStageLoot()`.
- **HP między potworami:** `!is_hardcore` → reset do maxa po każdej walce;
  `is_hardcore` → zapisywane wprost, bez resetu.
- **Porażka:** dowolna przegrana walka → `is_failed = true`, run się kończy,
  `accumulated_loot` NIE jest przyznawany.
- **Ukończenie (pokonany boss = ostatni slot):** rzut bonusowych skrzyń
  (`chest_min/max`(rank) + `chest_bonus_min/max`(poziom ulepszenia)), przyznanych jako
  tematyczna skrzynia danej mapy (`LocationEventService::MAP_CHEST_NAMES`, ta sama
  pula co `DungeonService::getChestForDungeon()` - patrz `LootChestSeeder`), po czym
  `grantAccumulatedLoot()` przyznaje cały skumulowany gold/xp/itemy.
- **`usePotion()`** - 1:1 mirror `DungeonService::usePotion()`, leczy `run->current_hp`
  bez przechodzenia przez silnik walki.

### Job: `SimulateLocationEventStageJob`

1:1 mirror `SimulateDungeonStageJob` - `ShouldQueue`, ładuje run, odrzuca jeśli
`combat_state !== 'calculating'`, woła `simulateStage()`, zapisuje `combat_data` i
`combat_state = 'completed'`/`'error'`.

## Co NIE jest jeszcze zrobione (Faza 2, UI)

- Podpięcie `LocationEventService::rollEventTrigger()` do `MapStub::startBattle()`.
- Modal wyboru trybu (Normalny/Hardcore) z podglądem nagród przy wylosowaniu eventu.
- Ekran przebiegu eventu (pasek postępu przez sloty, animacje walk, podsumowanie po
  ukończeniu/porażce) - analogiczny do `resources/views/livewire/city/dungeon-run.blade.php`.
- Weryfikacja przy wdrożeniu: czy każda z 8 map ma już tematyczną skrzynię w
  `ItemTemplate` (potwierdzone dla wszystkich 8 w `LootChestSeeder` na 2026-08-05) -
  jeśli lista się zmieni, zaktualizować `LocationEventService::MAP_CHEST_NAMES`.

## Testy

`tests/Unit/LocationEventServiceTest.php` pokrywa: rozkład prawdopodobieństw rzutu
eventu/poziomu ulepszenia (statystycznie, 20k prób), skalowanie statów potwora
(atak/def wg rangi × poziomu, HP wg tylko poziomu), mnożnik nagród hardcore (~x1.5,
statystycznie), zakres liczby potworów + wymuszony boss na ostatnim slocie, blokadę
drugiego aktywnego runu, reset HP (tryb normalny) vs przenoszenie HP (hardcore),
utratę łupu przy porażce, przyznanie gold/xp/tematycznej skrzyni po ukończeniu.
