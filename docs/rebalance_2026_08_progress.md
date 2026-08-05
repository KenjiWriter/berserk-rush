# Rebalans trudności/tempa/itemizacji (sierpień 2026) — status prac

Dokument śledzący realizację planu z `C:\Users\macie\.claude\plans\distributed-greeting-thimble.md`
(ten plik jest lokalny dla jednej maszyny/sesji Claude, więc pełna treść i status
kolejnych kroków są przepisane tutaj, do repo, żeby przetrwały między sesjami).

## Skąd to się wzięło

Gracze na Discordzie (aso666, potem Swiiezzy/swag) zgłosili spójną krytykę balansu:
1. Tempo progresji za szybkie — max poziom (99) w ~3 dni.
2. Potwory padają w 1-2 trafienia niezależnie od mapy/poziomu — brak wyzwania.
3. Ulepszanie (+0..+9) trywializuje dobór przedmiotu — "wystarczy ubrać +2 i expić".
4. Potwory są płaskie: brak własnych umiejętności, brak archetypu maga.
5. Log walki nie pokazuje wyraźnie efektów specjalnych (trucizna/krwawienie/przebicie pancerza/podpalenie).
6. Itemizacja zbyt liniowa — sztywny trójkąt klasowy STR+VIT/INT/AGI, specjalne mechaniki bojowe tylko na broniach.
7. **Doprecyzowanie (Swiiezzy/swag):** nie chodzi o to, żeby walki trwały wiecznie — grind
   tysięcy mobów musi być szybki. Chodzi o to, żeby **typowy** sprzęt dawał realną walkę
   (~3-4 trafienia), a dopiero pełne **+9 BiS** trywializowało starcia jako nagroda za
   inwestycję. Kalibracja poniżej celuje właśnie w to.

Root cause zdiagnozowany w kodzie: `php artisan balance:monsters` (Monte Carlo, patrz
`app/Console/Commands/BalanceMonstersCommand.php`) dobierał HP/ATK/DEF potworów pod
**gołego** bohatera (bez ulepszeń/zaklęć/zestawu klasowego). Prawdziwi gracze błyskawicznie
przewyższają ten punkt odniesienia.

---

## ✅ Zrobione

### Faza 0 — Rekalibracja potworów pod realnego gracza
- `app/Console/Commands/BalanceMonstersCommand.php`: `buildArchetype()` liczy teraz
  referencyjną postać z **+2 ulepszenia** (`EXPECTED_UPGRADE_LEVEL`), **1 skromnym
  narollem zaklęcia/sztukę** (`EXPECTED_ENCHANT_BONUS_PCT`) oraz **bonusem atrybutu z
  zestawu klasowego zbroi** (`classArmorBonusForLevel()`, dane 1:1 z
  `ItemTemplateSeeder::$classArmorAttributes`). Docelowe stałe (3-4 trafienia, 90%
  winrate dla `regular`) NIE zmienione — zmienił się tylko punkt odniesienia.
- Po drodze wykryty **osobny, realny bug**: auto-atak różdżką (`wand`) bez aktywnego
  skilla zadaje obrażenia bliskie zeru (silnik czyta złe pola —
  `getAttributeAttackBonus('sword', ...)` + `attack_min/max` zamiast
  `getAttributeAttackBonus('wand', ...)` + `magic_attack_min/max`). Wand ma stały
  0% winrate w kalkulatorze przez ten bug. **Wykluczony ze strojenia** (`solveMonster()`
  bierze teraz osobną listę `tuningArchetypes` bez wand, ale nadal raportuje go w
  tabeli weryfikacji). **Zgłoszony jako osobne zadanie w tle** (task_id: `task_68da4768`,
  chip widoczny w UI) — wymaga poprawki w `EncounterService`, `PvPEncounterService`,
  `GuildWarService`, `DungeonService` (reguła parytetu silników, `docs/modules/combat.md` pkt 9).
- Uruchomiony `php artisan balance:monsters --rank=regular` i wynik wklejony do
  `database/seeders/MonsterSeeder.php` — **48 potworów rangi `regular`** na wszystkich
  8 mapach ma nowe HP/ATK/DEF/AGI (HP zwykle 1.5-3x wyższe, mocniej na wczesnych
  mapach). `int`/`crit`/`dodge` nietknięte.
- **Ranga `boss` CELOWO nie ruszona.** Ten sam solver z celami dla bossa (8-12
  trafień/65% winrate) dał HP kilkukrotnie *niższe* niż obecne (np. Strażnik Puszczy
  2200 → ~400 wg solvera) — to wygląda na wcześniejszy, osobny problem z celami
  kalibracji bossów (albo bossy nigdy realnie nie przeszły przez ten sam proces).
  **Wymaga osobnej weryfikacji przed wdrożeniem** — nie stosować surowo wyniku
  `--rank=boss` bez zrozumienia, dlaczego solver chce je osłabić.
- `php artisan db:seed --class=MonsterSeeder` wykonany na lokalnej bazie dev (seeder
  jest idempotentny, `Monster::updateOrCreate`) — potwierdzone w DB (`Wilk Leśny`
  hp=99 zamiast 59).
- Poprawiony nieaktualny opis w `docs/modules/combat.md` (opisywał nieistniejący już
  w kodzie mechanizm "10%/poziom różnicy" — realny mechanizm to
  `Map::getMonsterTierMultiplier()`, dotyczy tylko farmienia map PONIŻEJ aktualnego
  tieru gracza, nie ogólnego skalowania trudności).

### Faza 1 — Spowolnienie krzywej XP
- `app/Application/Characters/LevelUpService.php`: dodana stała
  `XP_CURVE_MULTIPLIER = 6.0`, mnoży całą krzywą `xpToNext()` (kształt bez zmian).
  Orientacyjnie: ~3 dni do poziomu 99 → ~2.5-3 tygodnie. Suma XP do 99: 436.6M → 2.62B.
- `tests/Unit/ExpBalancingTest.php` zaktualizowany pod nowe wartości (w tym fixture
  poziomu 98→99, który wcześniej nie miał wystarczająco XP względem nowego progu).

### Faza 5 (rozdziały A/B/C/E) — Rework krzywej ulepszeń **- limit ZOSTAJE na +0..+9**

> **Ważna korekta w trakcie implementacji:** oryginalna specyfikacja gracza mówiła o
> rozszerzeniu do +0..+15 z nowymi progami/karami reset/zniszczenie. Gracz **wycofał
> to w trakcie wdrożenia** i doprecyzował: limit zostaje na +9, zmienia się WYŁĄCZNIE
> kształt krzywej bonusu % - mniej na +1-+3, "trochę więcej" +4-+6, "jeszcze więcej"
> +7-+9. Poniższy opis to już wersja PO korekcie (finalna).

- `app/Infrastructure/Persistence/ItemInstance.php`: nowa stała
  `UPGRADE_BONUS_PERCENT_BY_LEVEL` (4/8/12/20/30/42/58/78/100% dla +1..+9) zastępuje
  płaskie +10%/poziom w `getUpgradeBonusStats()`. Konkretne liczby to moja propozycja
  (gracz podał tylko kierunek "mniej/trochę więcej/jeszcze więcej", nie wartości) -
  do zweryfikowania w praktyce. `MAX_UPGRADE_LEVEL = 9` jako nazwana stała (dawniej
  dwa osobne hardkody `9` w `UpgradeService.php` i `ItemInstance.php`).
- `database/seeders/UpgradeRuleSeeder.php`: nowe szanse powodzenia podane wprost przez
  gracza - **100/100/95/90/85/75/65/55/45%** dla +1..+9 (wcześniej 95/90/80/70/60/50/
  40/28/15%). Kara za porażkę: `on_fail = 'downgrade'` (-1 poziom) od +6 wzwyż, do +5
  bez kary (tylko strata surowców/złota) - **bez** resetu do +0 i bez zniszczenia
  (to było częścią wycofanej propozycji +15).
- `app/Application/Items/UpgradeService.php`: `>= 9` zastąpione
  `ItemInstance::MAX_UPGRADE_LEVEL`, logika porażki niezmieniona względem oryginału
  (obsługuje `on_fail='downgrade'`/`'break'`/`'nothing'` - `'break'` był i jest
  zaimplementowany, ale żadna reguła go nie używa).
- Przesiane: `php artisan db:seed --class=UpgradeRuleSeeder` na dev DB.
- **Wciąż DO ZROBIENIA z potwierdzonego zakresu:** progi `+3` (darmowy bonus z puli
  `EnchantmentStrategy::poolFor()`) i `+5` (wzmocnienie głównej mechaniki specjalnej
  broni o +5pp) - patrz "Do zrobienia" niżej.

### Weryfikacja
- `php -l` czysty na wszystkich zmienionych plikach.
- Pełny `php artisan test` (na branchu `rebalance-phase-0-1`, po Fazie 0+1+5A-C+E):
  **te same 13 awarii co przed jakąkolwiek zmianą w tej serii** (potwierdzone kilka
  razy przez `git stash` + ponowny przebieg) - to pre-existing problemy środowiska
  (503 na kilku trasach Feature, jeden flaky test `OverLevelCombatTest`), niezwiązane
  z tą pracą. `ExpBalancingTest`, `UpgradeServiceTest`, `ItemStatRollerTest` - wszystkie
  zielone (zaktualizowane fixture pod nowe krzywe).

### Stan brancha/commitów
- **Faza 0 + Faza 1 są SCOMMITOWANE** na branchu `rebalance-phase-0-1` (commit
  `aa35311`, wypchnięty na `origin/rebalance-phase-0-1`).
- **Faza 5 (A/B/C/E) jest NIESCOMMITOWANA**, zrobiona na tym samym branchu
  (`rebalance-phase-0-1`), na wierzchu commita Fazy 0+1:
```
M app/Application/Items/UpgradeService.php
M app/Infrastructure/Persistence/ItemInstance.php
M database/seeders/UpgradeRuleSeeder.php
M tests/Unit/ItemStatRollerTest.php
M tests/Unit/UpgradeServiceTest.php
```
**Uwaga:** praca powstała najpierw na `main` (bo sesja nie wiedziała o istnieniu tego
brancha), a w trakcie zauważono rozjazd i przełączono się (`git checkout
rebalance-phase-0-1`, uncommitted changes pojechały razem) - stąd całość Fazy 0+1+5
jest teraz na jednym branchu, gotowa do docelowego review/PR.

---

## ⬜ Do zrobienia

### Natychmiastowe follow-upy z Fazy 0
- [ ] **Zbadać i naprawić bug auto-ataku różdżką** (patrz task_68da4768) — realny,
  poważny bug osłabiający całą ścieżkę "mag" niezależnie od tego rebalansu.
- [ ] **Osobno przeanalizować kalibrację rangi `boss`** — dlaczego solver z celami
  8-12 trafień/65% winrate chce drastycznie obniżyć HP bossów względem obecnych
  wartości. Dopiero potem ewentualnie zastosować `--rank=boss`.
- [ ] Rozważyć doprecyzowanie `EXPECTED_UPGRADE_LEVEL`/`EXPECTED_ENCHANT_BONUS_PCT`/
  `XP_CURVE_MULTIPLIER` na podstawie realnej telemetrii graczy po wdrożeniu (to były
  świadome, udokumentowane przybliżenia, nie idealnie zmierzone wartości).
- [ ] **Zdecydować o wdrożeniu na produkcję**: reseed `MonsterSeeder` na prod DB +
  deploy zmiany `LevelUpService` — to wpłynie na wszystkich obecnych graczy (postacie
  w trakcie gry na starych, teraz nieaktualnych postępach paska XP nie zostaną
  cofnięte, ale próg do następnego poziomu się zmieni).

### Faza 2 — Umiejętności potworów + archetyp Maga (niezaczęte)
- Nowy model `MonsterSkill` (migracja + relacja `Monster hasMany MonsterSkill`),
  reużywający słownik `effect_type` z `CombatSkill`.
- Rozszerzenie `EncounterService` (i `DungeonService` — reguła parytetu) o wykonywanie
  skilla potwora w jego turze (cooldown + `chance_to_use`).
- Oznaczenie 1-2 potworów/mapę jako `is_caster` z magicznym skillem (kandydaci już
  istnieją w seederze z wysokim `int`: Szaman Krwi, Wędrowny Czarownik, Żywiołak
  Płomieni, Mistrz Iluzji).
- Rozszerzenie panelu `/admin/combat-skills` (lub nowy `/admin/monster-skills`).

### Faza 3 — Czytelność logu walki (niezaczęte)
- Jawne, otagowane wpisy w strukturze tury dla: krwawienia, przebicia pancerza,
  rozbłysku magii, podwójnego ciosu, procków otrucia/ogłuszenia z ekwipunku (dziś
  działają mechanicznie, ale nie mają spójnej reprezentacji w UI).
- Rozszerzenie `map-stub.blade.php` (+ `dungeon-run`, `arena-combat`) o etykiety/ikony
  per typ efektu, pasek aktywnych statusów nad HP.

### Faza 4 — Głębsza itemizacja (niezaczęte)
- 4a: hybrydowe zestawy klasowe (STR+INT, AGI+INT) w `ItemTemplateSeeder.php` — wymaga
  też nowych ikon/nazw (grafika, poza zakresem programistycznym).
- 4b: specjalne mechaniki na zbroi/biżuterii (kolce, regeneracja, odporność na CC) w
  `EnchantmentStrategy` + silniki walki.

### Faza 5 — dokończenie (progi +3/+5) - następny krok
- **+3 "odblokowuje pierwszy bonus":** przy przekroczeniu +3 przedmiot automatycznie
  dostaje jeden losowy bonus z puli `EnchantmentStrategy::poolFor()`, zapisany osobno
  (`roll_stats['upgrade_bonuses']`, NIE `roll_stats['enchants']`) - nie konkuruje o
  5-slotowy limit zwykłego zaklinania, usuwany jeśli poziom spadnie z powrotem
  poniżej +3 (downgrade od +6).
- **+5 "wzmacnia efekt broni":** +5 punktów procentowych do głównej mechaniki
  specjalnej broni (Miecz→`double_strike_chance`, Topór→`bleed_chance`,
  Łuk→`armor_pen_pct`, Sztylet→`poison_chance`, Dzwon→`magic_burst_chance`,
  Różdżka→`magic_infusion_chance`) - flaga `upgrade_level >= 5`, do dodania w
  silnikach walki (`EncounterService`, `DungeonService`, `LocationEventService`,
  `PvPEncounterService`, `GuildWarService` - parytet 5 silników, patrz Faza 0c/2).
- Rozdział D planu (spłaszczenie `scale` tierów `ItemTemplateSeeder`/
  `ShopEquipmentSeeder`) - NIE zaczęte, wciąż aktualne niezależnie od korekty +9
  (root cause przykładu z różdżką, patrz plan).

---

## Jak kontynuować w nowej sesji

1. Przeczytaj ten plik + pełny plan `C:\Users\macie\.claude\plans\distributed-greeting-thimble.md`.
2. Upewnij się, że jesteś na branchu **`rebalance-phase-0-1`** (`git branch --show-current`) - tam jest cała dotychczasowa praca (Faza 0+1 scommitowane, Faza 5 A-C+E niescommitowane).
3. `git status`/`git diff` żeby zobaczyć dokładny stan niescommitowanych zmian z Fazy 5.
4. Zdecyduj: commitować teraz Fazę 5 (A-C+E), czy kontynuować od razu progami +3/+5 na tym samym stanie roboczym.
5. Zacznij od progów +3/+5 (potwierdzone w zakresie, opisane wyżej) - potem follow-upy (bug różdżki, kalibracja bossów), potem rozdział D (spłaszczenie tierów).
