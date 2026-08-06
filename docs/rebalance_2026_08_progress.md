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

## 📊 Status w skrócie (2026-08-05)

| Faza | Zakres | Status |
|---|---|---|
| **Faza 0** | Rekalibracja HP/ATK/DEF potworów `regular` pod realnego gracza (Monte Carlo) | ✅ Zrobione (3x przeliczone: początkowo, po rozdziale D, po naprawie różdżki) |
| **Faza 1** | Spowolnienie krzywej XP (×6) | ✅ Zrobione |
| **Faza 5** | Rework Kuźni: nowa krzywa bonusu +0..+9, szanse, kara `downgrade` od +6, progi +3/+5, spłaszczenie skali tierów (rozdz. D) | ✅ Zrobione (A-F) |
| **Follow-upy** | Bug auto-ataku różdżką (5 silników), widoczność bonusu +3 w tooltipie, zbadanie kalibracji bossów | ✅ Zrobione (kalibracja `boss` celowo odłożona - patrz niżej) |
| **Faza 2** | Umiejętności potworów (DoT/CC/heal/nuke) + archetyp Maga | ✅ Zrobione |
| **Faza 3** | Czytelność logu walki (etykiety/ikony DoT/procków/CC, pasek statusów) | ✅ Zrobione |
| **Faza 4a** | Hybrydowe zestawy klasowe (STR+INT, AGI+INT) - wymaga też grafik | ⬜ Niezaczęte |
| **Faza 4b** | Specjalne afiksy na zbroi/biżuterii (kolce, regen, odporność na CC) | ⬜ Niezaczęte |
| **Ranga `boss`** | Rekalibracja Monte Carlo dla bossów | ⬜ Odłożone (metodologia kalkulatora niespójna z rosterem - patrz niżej) |
| **Wdrożenie prod** | Reseed na produkcyjnej bazie + deploy | ⬜ Decyzja użytkownika |

**Cała praca jest na branchu `rebalance-phase-0-1`.** Nie jest zmergowana do `main`
(świadomie - rebalans niekompletny, a merge poszedłby na produkcję do graczy).

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
### Faza 5 rozdział F — Progi +3/+5 (ZROBIONE)
- **+3 darmowy bonus:** `UpgradeService::syncThresholdBonus()` wywoływane po każdej
  próbie ulepszenia (sukces i porażka z `downgrade`) - przy przekroczeniu +3 w górę
  losuje jeden bonus (`EnchantmentStrategy::generateRandomEnchantment()`) i zapisuje
  w `roll_stats['upgrade_bonuses']` (`ItemInstance::setUpgradeBonus()`), przy spadku
  poniżej +3 czyści go (`clearUpgradeBonuses()`). Wliczane do `getTotalStats()`
  analogicznie do `enchants`.
- **+5 wzmocnienie efektu broni:** dodane w JEDNYM miejscu -
  `Character::getEquipmentStats()` - +5pp do głównej mechaniki specjalnej broni
  głównej ręki (mapowanie broń→stat identyczne z `docs/modules/combat.md` pkt 2).
  Dzięki temu automatycznie działa we wszystkich silnikach walki (PvE, Lochy, Eventy
  Lokacji, PvP, Wojna Gildii) bez duplikowania kodu w każdym z nich.
- Materiały do ulepszenia poprawione: krok do +3 bez materiałów (tylko złoto) - "sam
  wstęp" do darmowego bonusu ma być tani, materiały wracają od +4.
- Testy: `tests/Unit/UpgradeServiceTest.php` (próg +3 grant/clear),
  `tests/Unit/UpgradeWeaponEffectBoostTest.php` (próg +5 na `poison_chance` sztyletu).
- **Nie zrobione:** widoczność darmowego bonusu z +3 w UI tooltipa przedmiotu -
  zgłoszone jako osobne zadanie w tle (task_id: `task_49bedf03`).

### Faza 5 rozdział D — Spłaszczenie skalowania tierów przedmiotów (ZROBIONE)
- **`ItemTemplateSeeder::$themes`** (`scale`): `2, 6, 15, 35, 80, 180, 400, 1000, 3000,
  7000, 15000` → **`2.0, 2.4, 2.88, 3.46, 4.15, 4.98, 5.97, 7.17, 8.60, 10.32, 12.38`**
  (stały mnożnik x1.20/tier, zakotwiczony na tierze 1 = 2.0).
- **`ShopEquipmentSeeder::$themes`** (`scale`): `1.2, 4.6, 10, 22, 45, 100, [160
  gladiator], 250, 600, 1800, 4500` → **`1.2, 1.44, 1.73, 2.07, 2.49, 2.99, [4.78
  gladiator], 3.58, 4.30, 5.16, 6.19`** (gladiator - lvl 55, poza sekwencyjną
  progresją - zachowuje swój stosunek x1.6 do tieru lvl 50, nie kolejny krok x1.20).
- **`BalanceMonstersCommand`**: naprawiony `gearMultiplier` - wcześniej liczył bonus
  ulepszenia jako `EXPECTED_UPGRADE_LEVEL * 0.10` (1:1 kopia STAREJ, płaskiej krzywej
  Kuźni) - teraz czyta z realnej `ItemInstance::UPGRADE_BONUS_PERCENT_BY_LEVEL[2]` (8%).
  Zsynchronizowane `$shopTiers`/`$craftTiers` z nowymi tabelami `scale` (przy okazji
  naprawiony pre-existing drift: kalkulator miał `4.0` dla lvl10 sklepowego, realny
  seeder miał `4.6` - teraz oba `1.44`).
- **Zweryfikowane w DB:** różdżka lvl95→99 - `magic_attack` mediana ~31→37 (min) i
  ~70→83.5 (max) = **+19%**, dokładnie w zakresie 15-25% zgłoszonym przez gracza
  (wcześniej >110%, np. `15.8-26.3k` → `33.8-56.3k`).
- **`php artisan balance:monsters --rank=regular` uruchomiony PONOWNIE** (drugi raz w
  tej serii) z nowym `scale` + naprawionym `gearMultiplier` - nowe, dużo niższe
  bezwzględnie staty (proporcjonalnie do dużo słabszej bazowej mocy przedmiotów po
  spłaszczeniu) wklejone do `MonsterSeeder.php`, dev DB przesiana ponownie (`ItemTemplateSeeder`,
  `ShopEquipmentSeeder`, `MonsterSeeder`).
- Względna trudność (3-4 trafienia/90% winrate dla `regular`) - bez zmian, tylko
  bezwzględne liczby są teraz dużo mniejsze (np. Mroczny Las HP~113 zamiast ~1600+
  sprzed całego rebalansu).

### Follow-upy po Fazie 5 — WSZYSTKIE ZROBIONE (2026-08-05)
- **Bug auto-ataku różdżką — NAPRAWIONY** we wszystkich 5 silnikach walki
  (`EncounterService`, `PvPEncounterService`, `GuildWarService`, `DungeonService`,
  `LocationEventService`), w 2 miejscach podglądu UI (`MapStub::getPlayerCombatStats()`,
  `ArenaCombat::getCombatStats()`) oraz w kalkulatorze balansu
  (`BalanceMonstersCommand::buildArchetype()`). Auto-atak różdżką czyta teraz
  poprawnie `getAttributeAttackBonus('wand')` (INT*2) + `magic_attack_min/max` zamiast
  fizycznego STR+AGI + `attack_min/max`. Weryfikacja: różdżka w kalkulatorze skoczyła
  z 38% do 99.9% winrate. `wand` wróciła do fazy strojenia (wcześniej wykluczona jako
  obejście buga), staty `regular` przeliczone TRZECI raz z pełnym zestawem 6
  archetypów i wgrane do `MonsterSeeder.php`. Notatka w `docs/modules/combat.md` pkt 2.
- **Kalibracja rangi `boss` — ZBADANA, CELOWO ODŁOŻONA.** Powód drastycznie niższych
  HP z solvera to NIE błąd solvera, tylko niespójność `BalanceMonstersCommand::$allMonstersRaw`
  (statyczna tabela referencyjna) z realnym rosterem: (1) każda mapa ma dziś 2 bossy,
  kalkulator śledzi 1; (2) nazwa "Władca Krypty" w kalkulatorze koliduje z bossem
  LOCHU z `DungeonSeeder.php` (inny potwór, inny poziom). Zastosowanie surowego wyniku
  `--rank=boss` bez naprawy tej rozbieżności groziło cichym uszkodzeniem rosteru
  bossów. Staty bossów cofnięte do oryginału - pełne uzasadnienie w docblocku
  `MonsterSeeder.php`. Rekalibracja `boss` odłożona do osobnej sesji (wymaga
  rozszerzenia `$allMonstersRaw` o drugi slot bossa per mapa + usunięcia kolizji nazw).
- **Widoczność bonusu z +3 w tooltipie — ZROBIONA.** `resources/views/components/item-tooltip.blade.php`
  renderuje teraz `roll_stats['upgrade_bonuses']` jako osobną, bursztynową sekcję
  "Bonus Kuźni (+3)" (zarówno dla podglądanego przedmiotu, jak i w kolumnie porównania
  z założonym). Zweryfikowane renderem: pokazuje np. "Bonus Kuźni (+3) — Crit Chance
  +3%", ukryte gdy brak bonusu. Brak podwójnego liczenia (getResolvedBaseStats() nie
  widzi upgrade_bonuses).

### Pozostałe (nie blokujące)
- [ ] Rozważyć doprecyzowanie `EXPECTED_UPGRADE_LEVEL`/`EXPECTED_ENCHANT_BONUS_PCT`/
  `XP_CURVE_MULTIPLIER` na podstawie realnej telemetrii graczy po wdrożeniu (to były
  świadome, udokumentowane przybliżenia, nie idealnie zmierzone wartości).
- [ ] **Zdecydować o wdrożeniu na produkcję**: reseed `MonsterSeeder`+`ItemTemplateSeeder`+
  `ShopEquipmentSeeder`+`UpgradeRuleSeeder` na prod DB + deploy zmian kodu — to wpłynie
  na wszystkich obecnych graczy (postacie w trakcie gry na starych progach paska XP nie
  zostaną cofnięte, ale próg do następnego poziomu się zmieni; już posiadany ekwipunek
  zachowa stare, wysokie wartości `roll_stats` aż do naturalnej wymiany — nowe dropy/
  crafty/zakupy będą już na spłaszczonej skali).

### Weryfikacja
- `php -l` czysty na wszystkich zmienionych plikach; `php artisan view:cache` przechodzi
  (tooltip Blade kompiluje się bez błędów).
- Pełny `php artisan test` (na branchu `rebalance-phase-0-1`, po Fazie 0+1+5 A-F +
  wszystkich follow-upach, wielokrotnie powtórzony po każdym kroku): **te same 13 awarii
  co przed jakąkolwiek zmianą w tej serii** (potwierdzone przez `git stash` + ponowny
  przebieg) - pre-existing problemy środowiska (503 na kilku trasach Feature, jeden
  flaky test `OverLevelCombatTest`), niezwiązane z tą pracą. Cała reszta (237 testów)
  zielona, w tym `ExpBalancingTest`, `UpgradeServiceTest`, `ItemStatRollerTest`,
  `UpgradeWeaponEffectBoostTest`.

### Stan brancha/commitów
Wszystko na branchu `rebalance-phase-0-1` (niezmergowane do `main` - świadomie).
- **Faza 0 + Faza 1** - commit `aa35311`.
- **Faza 5 A-C+E (stara wersja krzywej)** - commit `90bb1c5` (+ merge `88cd99c` z `main`).
- **Faza 5 F (+3/+5) + rozdział D + wszystkie follow-upy + CAŁA Faza 2** - commit
  `6f3b907` ("forge rework +0..+9, flatter item tiers, monster skills + Mage archetype").
- **Faza 3 (czytelność logu walki) - NIESCOMMITOWANA** (bieżąca partia). Zmienione pliki:
```
M app/Application/Combat/EncounterService.php          (stateSnapshot na wszystkich turach)
M app/Livewire/Adventure/MapStub.php                  (getPlayer/EnemyStatusEffects)
M resources/views/livewire/adventure/map-stub.blade.php (log -> komponent + pasek statusów)
M resources/views/livewire/city/dungeon-run.blade.php   (log -> komponent)
M resources/views/livewire/city/arena-combat.blade.php  (log -> komponent)
M docs/modules/combat.md                              (sekcja 2c)
M docs/rebalance_2026_08_progress.md
M tests/Feature/MonsterSkillsTest.php                 (assert state.playerDots)
?? app/Helpers/CombatLogHelper.php
?? resources/views/components/combat-log-entry.blade.php
?? resources/views/components/combat-status-bar.blade.php
```

### Faza 2 — Umiejętności potworów + archetyp Maga (ZROBIONA 2026-08-05)
- **Przechowywanie bez nowej tabeli:** skille potwora w istniejącej kolumnie JSON
  `monsters.abilities` (klucz `skills`). `Monster::getCombatSkills()` (normalizuje) +
  `Monster::isCaster()`. Zero migracji/FK/N+1 - świadoma decyzja (kolumna już istniała,
  udokumentowana jako "efekty specjalne", zawsze pusta `[]`).
- **Silnik walki:** nowy stan po stronie gracza `playerDots` (DoT NA graczu) i
  `playerCcTurns` (ogłuszenie gracza) - lustro istniejącego `activeDots`/`monsterCcTurns`
  (dotąd tylko gracz→potwór). Potwór co turę może rzucić skill (cooldown 0 + rzut
  `chance`) zamiast ataku. Obsługa `direct_dmg`(+is_magic=Mag)/`poison`/`fire`/`stun`/
  `freeze`/`heal`. Skille potwora zawsze trafiają (bez uniku), jak skille gracza w PvE.
- **Parytet 3 silników:** zreplikowane w `EncounterService`, `DungeonService`,
  `LocationEventService` (potwory w Eventach Lokacji). PvP/GvG nie dotyczy.
- **Seeder:** `MonsterSeeder::$monsterSkills` - ~18 potworów na 8 mapach dostało skille;
  magowie (`is_caster`+`is_magic` direct_dmg): Mroczny Kultysta, Troll Szaman, Szaman
  Krwi, Wędrowny Czarownik, Adepci Run, Żywiołak Lodu (freeze), Żywiołak Płomieni (fire),
  Mistrz Iluzji, Czarownica Zgnilizny. Reszta: poison (pająki/skorpion), stun
  (golemy/ogr), heal (troll/wiedźma). NIE wszystkie potwory - zwykłe walki nadal istnieją.
- **Test:** `tests/Feature/MonsterSkillsTest.php` (4 testy: poison DoT tyka na graczu,
  magic nuke tagowany, stun = gracz traci turę, brak skilli = zero zmian).
- **Frontend:** tury skilli potworów degradują się gracefully w istniejącym silniku
  animacji (`map-stub.blade.php` - `type:'skill_heal'`/`effectType:'heal'` pokazuje
  leczenie, `type:'skill'`+`value:0` pokazuje rzut; paski HP czytają `playerHp`/`enemyHp`
  z tury, więc DoT/heal odzwierciedlają się poprawnie). Bogatsze etykiety (osobne ikony
  DoT gracza, licznik CC nad HP) to zakres Fazy 3 - tu tylko poprawność + brak zawieszenia.
- **Poza MVP (przyszłość):** skille potworów w starciach grupowych/AOE, monster-buffy
  (`buff_phys_dmg`), panel admina do edycji skilli potworów (dziś edycja przez JSON
  `abilities` / seeder).

### Faza 3 — Czytelność logu walki (ZROBIONA 2026-08-05)
- **Wspólny komponent wpisu logu** `resources/views/components/combat-log-entry.blade.php`
  - zastąpił zduplikowane, inline'owe bloki logu w `map-stub`, `dungeon-run`,
  `arena-combat`. Rozpoznaje typy tur z Fazy 2 (`crowd_controlled`, `player_dot`, skille
  potworów: nuke magiczny z tagiem MAGIA, nałożenie DoT, samoleczenie) oraz tyknięcia
  DoT-a na graczu. Superset: obsługuje też `actor_name`/`round`/`target_name` (Arena/GvG).
- **`App\Helpers\CombatLogHelper`** - centralne mapowanie effect_type -> etykieta PL +
  kolor Tailwind + ikona FA (poison/bleed/fire/stun/freeze/heal/magic/magic_burst/
  double_strike/armor_pen). Efekty jako kolorowe pigułki (badge).
- **Pasek statusów nad HP** `resources/views/components/combat-status-bar.blade.php` -
  ikony aktywnych efektów + licznik tur, dla gracza i przeciwnika. Zasilany migawką
  `state` doklejaną do każdej tury (`EncounterService::stateSnapshot()` -
  `dots`/`playerDots`/`playerCc`/`monsterCc`) i czytaną przez
  `MapStub::getPlayerStatusEffects()`/`getEnemyStatusEffects()`. Na razie w PvE (map-stub).
- **Test:** rozszerzony `tests/Feature/MonsterSkillsTest.php` (turn `state` niesie
  `playerDots` pod pasek statusów). Weryfikacja renderu: każda gałąź logu wyrenderowana
  z realnymi kształtami tur (PvE + Arena) przez `view()->render()` - wszystkie czytelne.
- **Odłożone (opcjonalny polish):** pasek statusów w Lochach/Arenie (wymaga dołożenia
  `state` w `DungeonService`/`PvPEncounterService`); per-turowe tagi `armor_pen`/
  `double_strike` w logu (poison/bleed/fire już płyną przez `dotType`).

### Faza 4 — Głębsza itemizacja (niezaczęte)
- 4a: hybrydowe zestawy klasowe (STR+INT, AGI+INT) w `ItemTemplateSeeder.php` — wymaga
  też nowych ikon/nazw (grafika, poza zakresem programistycznym).
- 4b: **Odporności PvP/GvG na zbroi (Odporność na Ludzi `resist_hero` 2-10% oraz Odporności na Bronie `resist_sword`/`dagger`/`bell`/`axe`/`bow`/`wand` 2-10%)** - ✅ **ZROBIONE** (2026-08-06) w `EnchantmentStrategy`, `Character::getEquipmentStats()`, `PvPEncounterService`, `GuildWarService`, UI (`item-tooltip`, `profile`, `witch`) oraz testy `PvpEquipmentResistancesTest`.

### Rebalans CD Umiejętności Gracza (2026-08-06, ZROBIONY, na `main`, NIEZALEŻNIE od brancha `rebalance-phase-0-1`)
- Zgłoszenie gracza: stara redukcja CD (`base_cooldown - 1/-2/-3` płasko, niezależnie od
  bazowej wartości) dawała skrajności - szybkie skille (CD 1-2) były spammowalne bez
  rekompensaty za rosnącą moc z mistrzostwa, a długie/ultimate skille (CD 8-10) zostawały
  praktycznie bezużyteczne przez całą fazę Normal/Master (levele 1-16).
- `CharacterCombatSkill::getCooldown()` przepisane na 3 kategorie szybkości wg
  `base_cooldown` (Normal/Lv.1): **Szybkie** (1-2, CD **rośnie** z mistrzostwem do floora
  3-4), **Średnie** (3-5, floor 3 od Arcymistrza), **Długie** (6+, Normal bez zmian, floor
  5 od Arcymistrza, BEZ dalszego skracania na Perfect). Szczegóły i tabela w
  `docs/modules/skills.md` pkt 1.
- Pojedyncze miejsce w kodzie (jedno źródło prawdy) - konsumowane przez wszystkie 5
  silników walki (PvE/Lochy/Eventy Lokacji bezpośrednio, PvP/GvG przez
  `Character::createSnapshot()`), zero duplikacji.
- Nie dotyka `database/seeders/CombatSkillSeeder.php` (wartości `base_cooldown` per skill
  zostają jako "Normal-tier" punkt startowy dla klasyfikacji - żadna zawartość skilli nie
  zmieniona).
- Testy: pełny `php artisan test` na `main` - **287 passed, 0 failed** (żadnych
  pre-istniejących awarii na tym branchu, w przeciwieństwie do `rebalance-phase-0-1`).
- **Ta zmiana jest na `main`, NIE na `rebalance-phase-0-1`** - dotyczy innego pliku
  (`CharacterCombatSkill.php`) niż reszta tego dokumentu, niezależna od stanu rebalansu
  potworów/itemizacji opisanego niżej.

### Faza 5 — CAŁA ZROBIONA (rozdziały A-F)
- Progi +3/+5, rozdział D (spłaszczenie tierów) - **ZROBIONE**, patrz wyżej.
- Widoczność bonusu z +3 w tooltipie przedmiotu - zgłoszone jako osobne zadanie
  (`task_49bedf03`), nie blokuje reszty.
- Jedyny pozostały wątek: `EXPECTED_UPGRADE_LEVEL`/`EXPECTED_ENCHANT_BONUS_PCT` w
  kalkulatorze to wciąż moje przybliżenia (patrz follow-upy niżej) - do
  doprecyzowania po realnej telemetrii.

---

## Jak kontynuować w nowej sesji

1. Przeczytaj ten plik + pełny plan `C:\Users\macie\.claude\plans\distributed-greeting-thimble.md`.
2. Upewnij się, że jesteś na branchu **`rebalance-phase-0-1`** (`git branch --show-current`) - tam jest cała dotychczasowa praca. Faza 0+1+5(A-C+E stara wersja) scommitowane, progi +3/+5 i rozdział D (najnowsze) niescommitowane.
3. `git status`/`git diff` żeby zobaczyć dokładny stan niescommitowanych zmian.
4. **Faza 5 jest w całości zrobiona** (A-F) - zdecyduj czy commitować teraz, czy kontynuować dalej na tym samym stanie roboczym.
5. Następny krok wg planu: **Faza 2** (skille potworów + archetyp Maga) - albo najpierw follow-upy (bug różdżki `task_68da4768`, kalibracja rangi `boss`, widoczność bonusu +3 w tooltipie `task_49bedf03`), jeśli chcesz je odhaczyć przed nowymi funkcjami.
6. **Uwaga o merge do `main`:** branch NIE jest jeszcze zmergowany do `main` (świadoma decyzja - rebalans niekompletny). Nie mergować bez wyraźnej prośby - to trafi na produkcję do prawdziwych graczy.
