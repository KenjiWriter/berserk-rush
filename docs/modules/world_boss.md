# World Boss Module

System World Bossów (Światowych Bossów) pozwala na globalne wyzwania, w których cała społeczność serwera walczy z potężnymi przeciwnikami o bardzo dużej, **współdzielonej puli punktów zdrowia (HP)**. Pula ta NIE regeneruje się - to czysta, monotonicznie malejąca suma obrażeń zadanych przez wszystkich graczy. Jeśli społeczność (lub pojedynczy silny gracz) zada wystarczająco dużo obrażeń, by ją wyczerpać, boss zostaje **zablokowany** dla wszystkich do najbliższego godzinowego resetu.

> **UWAGA (pełny rework 2026-07-30, dopracowany tego samego dnia po kilku rundach feedbacku z żywych testów):** poprzednia wersja tego modułu trzymała 1 world bossa na każdej z 8 map, wystawiała go jako banner/modal na mapie w zakładce "Wyprawy", i rozdawała nagrody wyłącznie po realnym wyzerowaniu współdzielonego `current_hp` (flaga `is_defeated`). Ponieważ HP potrafiło być ogromne (do 2 000 000), a same statystyki world bossów nigdy nie zostały objęte rebalansem Monte Carlo reszty potworów, w praktyce boss nigdy realnie nie padał i **nagrody nie były przyznawane wcale**. Kolejne iteracje rework'u przechodziły przez: (1) stały floor `current_hp = 1` (całkowicie niezabijalny), (2) regenerację co turę na współdzielonej puli (żeby "trudno było zabić przypadkiem") - obie okazały się mylące/za agresywne w praktyce (druga "zjadała" większość sumy zadanych obrażeń z rankingu na dużych pulach HP). Ostateczna wersja: **żadnej regeneracji na współdzielonej puli** - ochronę przed przypadkowym zabiciem WEWNĄTRZ pojedynczej walki gracza zapewnia już sama mechanika `damageDealt` (liczone względem osobnej, fikcyjnej puli 999 999 999 - patrz niżej), więc dodatkowa regeneracja globalnej puli była zbędna i tylko zniekształcała ranking.

## Kluczowe Cechy

* **3 aktywni bossowie naraz, po jednym na przedział poziomowy**: zamiast 1 bossa na mapę, system utrzymuje dokładnie 3 żywe instancje - po jednej dla przedziałów `low` (poziom 0-35), `mid` (35-65) i `high` (65-99). Każda instancja jest losowana z ustalonej puli tych samych 8 potworów rangi `worldboss`, które istniały w grze od zawsze (patrz sekcja "Przydział bossów do przedziałów").
* **Osobna zakładka w Wyprawach**: world boss nie pojawia się już na kartach map w zakładce "Mapy". Ma własną zakładkę **"Worldboss"** wewnątrz `app/Livewire/City/Adventure.php` (`resources/views/livewire/city/adventure.blade.php`), z 3 kartami (po jednej na przedział), paskiem HP, rankingiem Top 10 i przyciskiem ataku.
* **Brak regeneracji na współdzielonej puli - `current_hp` to czysta suma obrażeń**: `EncounterService::simulate()` w gałęzi world bossa robi `current_hp = max(0, current_hp - damageDealt)` - żadnego dodawania z powrotem. Gdy `current_hp` osiągnie 0, zostaje tam zablokowane (żadna dalsza walka go nie rusza) aż do godzinowego resetu - patrz "Stan pokonany" niżej. Nie ma osobnej kolumny/flagi `is_defeated`; stan "pokonany" jest zawsze wyliczany bezpośrednio z `current_hp <= 0`, żeby nie mógł się rozjechać z rzeczywistym HP (to było źródłem historycznego bugu z 2026-07-28).
* **Zadawanie Obrażeń i ochrona przed przypadkowym zabiciem w SWOJEJ walce**: walka z bossem korzysta z rdzennego systemu turowego, ograniczonego do max 20 tur. Sama symulacja 1 na 1 (`$winner` zawsze `'enemy'`) liczy `damageDealt` względem osobnej, fikcyjnej puli **999 999 999 HP** (`$monsterMaxHp`), całkowicie odseparowanej od prawdziwego `current_hp` - dopiero SUMA obrażeń z całej (max 20-turowej) walki jest na końcu jednorazowo odejmowana od współdzielonej puli. Dzięki temu żaden pojedynczy gracz nie jest w stanie przypadkiem wyzerować bossa "w swojej turze" - to właśnie ta fikcyjna pula, a nie jakakolwiek regeneracja, daje tę ochronę.
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

### Ujednolicenie HP per przedział (2026-08-05)

Współdzielone `hp` (`MonsterSeeder.php`) zostało ujednolicone w obrębie przedziałów `mid` i `high`, żeby obaj/wszyscy bossowie danego przedziału stanowili porównywalne wyzwanie niezależnie od tego, który zostanie wylosowany na daną godzinę:

| Przedział | HP każdego bossa w puli |
|---|---|
| `low` (0-35) | bez zmian - Król Lasu 8 925, Licz Cieni 60 000, Król Trolli 140 000 |
| `mid` (35-65) | **500 000** (Wódz Orków, Moczarowy Behemot) |
| `high` (65-99) | **800 000 000** (Smok Cienia, Arcymag, Pan Zniszczenia) |

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

> **UWAGA (fix 2026-07-30):** `WorldBossRewardJob` celowo **nie implementuje** `ShouldQueue`. Wcześniej `Schedule::job(...)` kolejkował go, co wymagało dodatkowo działającego, osobnego procesu `php artisan queue:work` - jeśli worker nie działał, harmonogram i tak "odpalał się" punktualnie co godzinę, ale zadanie tylko trafiało do kolejki i nigdy się nie wykonywało (zgłoszony bug: mimo upływu godziny bossy się nie resetowały, nagrody nie przychodziły). Bez `ShouldQueue`, Laravel wykonuje `handle()` synchronicznie w samym procesie schedulera (`Schedule::dispatchNow()`), więc jedyną zależnością infrastrukturalną pozostaje działający cron (`* * * * * php artisan schedule:run`) - **zweryfikuj na serwerze, że ten cron faktycznie jest zarejestrowany** (`crontab -l`), bo to teraz jedyny warunek działania tego joba.

## Cykl Życia World Bossa
1. **Pojawienie się (Spawn)**: `ensureBossesSpawned()` tworzy brakujące instancje dla przedziałów, które nie mają aktualnie żywego bossa.
2. **Ataki Graczy**: z każdym atakiem `EncounterService::simulate()` odejmuje zadane obrażenia od `current_hp` (bez żadnej regeneracji) i przycina wynik do `max(0, ...)`.
3. **(Opcjonalnie) Pokonanie**: jeśli `current_hp` osiągnie 0, boss przechodzi w stan zablokowany - patrz "Stan pokonany" niżej. To NIE wyzwala żadnych natychmiastowych nagród (te i tak czekają na krok 4).
4. **Rozliczenie (co godzinę)**: `WorldBossRewardJob` nagradza Top 9 graczy każdej instancji (pokonanej lub nie), kasuje ją i respawnuje nowy, losowy skład na kolejną godzinę.

## Stan pokonany (`current_hp <= 0`)

Gdy wspólna pula HP zostanie wyczerpana (przez jednego bardzo silnego gracza albo skumulowany dmg wielu graczy) w trakcie godziny:
- `EncounterService::start()` odrzuca każdą kolejną próbę ataku na tę instancję błędem `WORLD_BOSS_DEFEATED`, zanim jeszcze powstanie `Encounter` - żaden dodatkowy `WorldBossDamageLog` nie może już powstać.
- `MapStub::mount()` łapie ten sam przypadek wcześniej (przy wejściu z linku `?world_boss=...`) i pokazuje flash-warning zamiast wpuszczać gracza na ekran walki.
- UI (`adventure.blade.php`) pokazuje kartę bossa ze złotym paskiem HP, ikoną trofeum i przyciskiem "Boss pokonany" zamiast "Atakuj" - stan ten ma pierwszeństwo przed sprawdzeniem przedziału poziomowego/uczestnictwa, bo to fakt globalny, a nie zależny od danego gracza.
- Boss NIE jest usuwany ani respawnowany od razu - zostaje zablokowany aż do najbliższego uruchomienia `WorldBossRewardJob` (top godziny), które i tak rozda nagrody i wylosuje nowego.

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
