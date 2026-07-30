# World Boss Module

System World Bossów (Światowych Bossów) pozwala na globalne wyzwania, w których cała społeczność serwera walczy z potężnymi przeciwnikami o bardzo dużej puli punktów zdrowia (HP). W przeciwieństwie do zwykłych potworów, world boss jest **teoretycznie nie do zabicia** — regeneruje HP z każdym trafieniem — więc jedynym celem starcia jest zmaksymalizowanie zadanego DMG przed hourly rozliczeniem nagród.

> **UWAGA (pełny rework 2026-07-30):** poprzednia wersja tego modułu trzymała 1 world bossa na każdej z 8 map, wystawiała go jako banner/modal na mapie w zakładce "Wyprawy", i rozdawała nagrody wyłącznie po realnym wyzerowaniu współdzielonego `current_hp` (flaga `is_defeated`). Ponieważ HP potrafiło być ogromne (do 2 000 000), a same statystyki world bossów nigdy nie zostały objęte rebalansem Monte Carlo reszty potworów, w praktyce boss nigdy realnie nie padał i **nagrody nie były przyznawane wcale**. Rework opisany niżej usuwa koncepcję "zabicia" bossa na rzecz czysto czasowego rozliczenia co godzinę.

## Kluczowe Cechy

* **3 aktywni bossowie naraz, po jednym na przedział poziomowy**: zamiast 1 bossa na mapę, system utrzymuje dokładnie 3 żywe instancje - po jednej dla przedziałów `low` (poziom 0-35), `mid` (35-65) i `high` (65-99). Każda instancja jest losowana z ustalonej puli tych samych 8 potworów rangi `worldboss`, które istniały w grze od zawsze (patrz sekcja "Przydział bossów do przedziałów").
* **Osobna zakładka w Wyprawach**: world boss nie pojawia się już na kartach map w zakładce "Mapy". Ma własną zakładkę **"Worldboss"** wewnątrz `app/Livewire/City/Adventure.php` (`resources/views/livewire/city/adventure.blade.php`), z 3 kartami (po jednej na przedział), paskiem HP, rankingiem Top 10 i przyciskiem ataku.
* **Nie da się go zabić - regeneracja HP**: `EncounterService::simulate()` w gałęzi world bossa dolicza regenerację `+ceil(total_hp * 0.02)` do `current_hp` PRZED odjęciem zadanych obrażeń, a wynik zawsze przycina do `max(1, ...)`. Kolumna `is_defeated` została usunięta ze schematu - nie ma już stanu "pokonany".
* **Zadawanie Obrażeń**: walka z bossem korzysta z rdzennego systemu turowego, ograniczonego do max 20 tur. Gracz dołącza do walki i próbuje przeżyć jak najdłużej, by zmaksymalizować swój DMG - mechanika samej symulacji 1 na 1 (`$winner` zawsze `'enemy'`, `damageDealt` liczone względem fikcyjnej puli 999 999 999 HP) jest niezmieniona względem poprzedniej wersji.
* **Jedna Próba (Single Attempt)**: gracz może zaatakować daną instancję World Bossa tylko raz - twarda blokada transakcyjna w `EncounterService::start()` (sprawdzenie istniejącego `WorldBossDamageLog` dla pary `character_id` + `world_boss_instance_id`).
* **Globalny Ranking**: po każdym uderzeniu wynik gracza dopisywany jest do logów (`WorldBossDamageLog`). System grupuje logi po `character_id` i sumuje zadany DMG, układając Top 10 najlepszych wojowników na kartę danego przedziału.
* **Rozliczenie co godzinę, niezależnie od stanu HP**: `WorldBossRewardJob` (uruchamiany `Schedule::job(...)->hourly()`) nagradza Top 9 graczy po zadanym DMG dla KAŻDEJ aktywnej instancji, po czym zawsze ją kasuje i losuje nowego bossa na kolejną godzinę - również wtedy, gdy nikt nie walczył.

## Przydział 8 istniejących world bossów do przedziałów

`app/Application/Combat/WorldBossService::BRACKET_POOLS`:

| Przedział | Bossowie w puli (losowanie 1 z N co godzinę) |
|---|---|
| `low` (0-35) | Król Lasu (lvl 10), Licz Cieni (25), Król Trolli (35) |
| `mid` (35-65) | Wódz Orków (50), Moczarowy Behemot (65) |
| `high` (65-99) | Smok Cienia (75), Arcymag (85), Pan Zniszczenia (99) |

`WorldBossService::bracketForLevel(int $level)` to fallback wyznaczający przedział z samego poziomu potwora (używany np. gdy `EncounterService` musi dosiać instancję ad-hoc).

### Przeskalowanie statystyk (2026-07-30)

Ranga `worldboss` nigdy nie była objęta rebalansem Monte Carlo reszty potworów (`MonsterSeeder.php`), przez co część bossów miała ATK wyraźnie niższe niż skalibrowany `boss` mapy na zbliżonym poziomie. Statystyki `atk`/`def` wszystkich 8 world bossów zostały ręcznie przeskalowane względem najbliższego poziomowo skalibrowanego potwora rangi `boss`: **ATK ×1.5, DEF ×1.25** względem tego punktu odniesienia. `hp`, `agi`, `int`, `crit`, `dodge` pozostały bez zmian. Poziom Pana Zniszczenia obniżono ze 100 do 99 (`LevelUpService::MAX_LEVEL = 99`).

## Architektura Systemu

### 1. `WorldBossInstance` (Model)
Przechowuje dane o aktualnie żywej instancji bossa. Kolumny: `monster_id`, `map_id` (z potwora, do celów wyświetlania/routingu walki), `level_bracket` (`low`/`mid`/`high` - klucz jednoznaczności "1 instancja na przedział"), `total_hp`, `current_hp`. **Nie ma już kolumny `is_defeated`.**

### 2. `WorldBossDamageLog` (Model)
Rejestruje każde uderzenie zadane przez gracza danej instancji bossa. Powiązuje `world_boss_instance_id`, `character_id` oraz `damage`.

### 3. `WorldBossService`
- `ensureBossesSpawned()`: dla każdego z 3 przedziałów, jeśli nie istnieje żadna aktywna instancja, losuje potwora z puli tego przedziału i tworzy instancję.
- `resetBosses()`: kasuje wszystkie instancje + logi, respawnuje 3 świeże (używane przez `php artisan app:world-boss-reset`, komenda ręczna/awaryjna).
- `bracketForLevel(int $level)`: fallback do wyznaczenia przedziału z poziomu potwora.

### 4. Zadania Cykliczne (Cron Jobs, `routes/console.php`)
* **`app:world-boss-tick` → `WorldBossService::tickHourly()`**: bezpieczeństwo - dosiewa brakujące instancje (`ensureBossesSpawned()`), nie rusza walk w toku.
* **`WorldBossRewardJob`** (hourly): JEDYNY autorytet od nagród. Dla każdej aktywnej instancji (niezależnie od jej HP): liczy ranking Top 9 z `WorldBossDamageLog`, rozsyła maile z nagrodami (patrz tabela niżej), **zawsze** kasuje logi i instancję, po czym woła `ensureBossesSpawned()`, żeby każdy przedział dostał nowego, losowo wybranego bossa na kolejną godzinę - również gdy dana instancja nie miała żadnej aktywności.

## Cykl Życia World Bossa
1. **Pojawienie się (Spawn)**: `ensureBossesSpawned()` tworzy brakujące instancje dla przedziałów, które nie mają aktualnie żywego bossa.
2. **Ataki Graczy**: z każdym atakiem `EncounterService::simulate()` dolicza regenerację, odejmuje zadane obrażenia i przycina wynik do `max(1, ...)` - `current_hp` nigdy nie osiąga faktycznego zera jako "koniec walki".
3. **Rozliczenie (co godzinę)**: `WorldBossRewardJob` nagradza Top 9 graczy każdej instancji, kasuje ją i respawnuje nowy, losowy skład na kolejną godzinę.

## Nagrody

| Miejsce | Gemy | Klucze do lochu |
|---|---|---|
| 1 | 50 | 5 |
| 2-3 | 30 | 5 |
| 4-6 | — | 3 |
| 7-9 | — | 1 |
| 10+ | — | — |

Nagroda trafia jednym mailem (`Mail` + `attachments`: `{'type':'gems','qty':N}` i/lub `{'type':'item','id':itemInstanceId}`), rozliczana standardowym mechanizmem `ClaimMailAction` (`app/Application/Mail/Actions/ClaimMailAction.php`) - nic nie trzeba było zmieniać w odbiorze poczty.

### Mapowanie klucza do lochu per boss

Klucz dobierany jest per konkretny boss (nie per przedział), wg najbliższego poziomowo lochu z `database/seeders/DungeonSeeder.php` (`WorldBossRewardJob::MONSTER_KEY_MAP`):

| Boss | Klucz |
|---|---|
| Król Lasu | Klucz Katakumb |
| Licz Cieni | Klucz Krypty |
| Król Trolli | Klucz Krypty |
| Wódz Orków | Klucz Pustkowi |
| Moczarowy Behemot | Klucz Cytadeli |
| Smok Cienia | Klucz Cytadeli |
| Arcymag | Klucz Otchłani |
| Pan Zniszczenia | Klucz Otchłani |

## Uwagi dot. dropów z world bossów
Starcia ze światowym bossem NIGDY nie kończą się `$winner = 'player'`, więc `DropService` nigdy nie jest wywoływany dla tych walk i world boss nie ma własnej `LootTable`. Unikalne materiały/przedmioty każdego world bossa są przypisane do zwykłego, zabijalnego potwora rangi `boss` z tej samej mapy - patrz `docs/modules/loot.md`. Nagrody za sam udział w walce z world bossem (gemy + klucze do lochów) idą całkowicie osobnym, opisanym wyżej torem.
