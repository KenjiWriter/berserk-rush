# Moduł Kuźni (Upgrades / Forge)

Moduł Kuźni pozwala graczom na ulepszanie siły bazowych przedmiotów (broni i zbroi). Zwiększa ich statystyki, by sprostać silniejszym wyzwaniom.

> **Uwaga (refaktor):** Ulepszanie zostało wydzielone z Brońmistrza (`Weaponsmith`) i Zbrojmistrza (`Armorsmith`) do osobnej domeny **Kowal** (`Blacksmith`), wspólnej dla broni i zbroi. Pełny opis: `docs/modules/blacksmith.md`. Brońmistrz i Zbrojmistrz zostali od tamtej pory dodatkowo scaleni w jednego NPC **Handlarza** (`Merchant`) - patrz `docs/modules/merchant.md` - który odpowiada wyłącznie za kupno i sprzedaż.

## Implementacja
- Pliki logiki i akcji:
  - `app/Application/Items/UpgradeService.php`
  - `app/Infrastructure/Persistence/UpgradeRule.php`
- Komponent Livewire:
  - `app/Livewire/City/Blacksmith.php`
- Widok:
  - `resources/views/livewire/city/blacksmith.blade.php`

## Mechaniki

### 1. Interfejs Kowala
- Moduł dostępny w mieście pod postacią Kowala, dostępnego z Hubu (kafelek "Kowal") oraz z poziomu Handlarza (przycisk "Kowal (Ulepszanie i Rzemiosło)").
- Kowal obsługuje ulepszanie zarówno broni (Slot: `main_hand`), jak i zbroi (`head`, `chest`, `feet`) w jednym, ogólnym widoku - bez podziału na osobne postaci NPC.
- Posiada dwie zakładki: **Kuźnię Ulepszeń** oraz **Rzemiosło** (patrz `docs/modules/blacksmith.md`), a nad obiema zakładkami pasek filtrów typu/slotu (Wszystko / Broń / Hełmy / Zbroje / Buty).

### 2. Proces Ulepszania
- Przedmioty mają swój poziom ulepszenia wyrażony w systemie od `+0` (domyślny) do `+9` (maksymalny, `ItemInstance::MAX_UPGRADE_LEVEL`).
- Każdy proces podnoszenia poziomu obarczony jest szansą na powodzenie (`UpgradeRuleSeeder::$upgradeSteps`).
- **Koszt:** Ulepszanie pochłania Złoto (`gold`) oraz zdefiniowane materiały rzemieślnicze z dedykowanego schowka na materiały (`material_stash`). Krok do `+3` celowo **nie wymaga materiałów** (tylko złoto) - próg `+3` sam w sobie daje darmowy bonus (patrz pkt 4 niżej), więc koszt samego wejścia na niego ma zostać niski. Materiały wracają od kroku do `+4` wzwyż.

> **Uwaga (rework krzywej, Faza 5 rebalansu, 2026-08-05):** na życzenie graczy (Discord:
> aso666, potem doprecyzowanie) płaska krzywa `+10% bazowych statystyk/poziom` i płaskie
> szanse powodzenia zostały zastąpione przyspieszającą krzywą - pierwsze poziomy
> (`+1`-`+3`) dają WYRAŹNIE mniej niż dawniej, `+4`-`+6` przyspieszają, `+7`-`+9`
> przyspieszają najmocniej. Dokładne wartości: `ItemInstance::UPGRADE_BONUS_PERCENT_BY_LEVEL`
> (4/8/12/20/30/42/58/78/100% dla `+1`..`+9`). Nowe szanse powodzenia (podane wprost
> przez gracza): **100/100/95/90/85/75/65/55/45%** dla `+1`..`+9`.

### 3. Skutki i Porażki
- **Sukces:** Poziom przedmiotu rośnie o +1 (zapisywane w `ItemInstance->upgrade_level`). Przedmiot otrzymuje dodatkowe statystyki kalkulowane w czasie rzeczywistym wg krzywej z pkt 2 (min. +1 dla statystyk dodatnich) i dopisywane do statystyk z szablonu. Widoczne jako `( +X )` przy nazwie przedmiotu.
- **Porażka (Faza 5 rebalansu, 2026-08-05):** do `+5` gracz traci jedynie zużyte zasoby (`on_fail = 'nothing'`, jak dawniej). **Od `+6` wzwyż** nieudane ulepszenie obniża poziom przedmiotu o 1 (`on_fail = 'downgrade'`) - to świadome ryzyko na wyższych poziomach, w odróżnieniu od wcześniejszej wersji systemu, gdzie porażka nigdy nie kosztowała poziomu.
- Przebieg i zasady są wyraźnie określone na ekranie w interfejsie Kuźni. O sukcesie bądz porażce informuje modal z graficznym komunikatem (ikona ✨ przy sukcesie lub 💥 przy failu).

### 4. Progi Specjalne (Faza 5 rebalansu, 2026-08-05)
- **`+3` - Darmowy Bonus:** w momencie przekroczenia poziomu `+3`, przedmiot automatycznie i bezpłatnie otrzymuje jeden losowy magiczny bonus z tej samej puli, z której losuje Czarodziej/Wiedźma przy zaklinaniu (`EnchantmentStrategy::generateRandomEnchantment()`). Zapisywany osobno (`ItemInstance::setUpgradeBonus()`, klucz `roll_stats['upgrade_bonuses']`) - **nie** zajmuje jednego z 5 slotów zwykłego zaklinania i nie da się go przelosować/zablokować u Wiedźmy. Jeśli poziom przedmiotu spadnie z powrotem poniżej `+3` (kara `downgrade` z `+6`+), bonus jest automatycznie usuwany (`ItemInstance::clearUpgradeBonuses()`). Logika progu: `UpgradeService::syncThresholdBonus()`.
- **`+5` - Wzmocnienie Efektu Broni:** broń w slocie `main_hand` na poziomie `+5` lub wyższym dostaje jednorazowy, płaski `+5` punktów procentowych do SWOJEJ głównej mechaniki specjalnej (patrz `docs/modules/combat.md` pkt 2 "Specjalizacje Klas Broni"): Miecz→`double_strike_chance`, Topór→`bleed_chance`, Łuk→`armor_pen_pct`, Sztylet→`poison_chance`, Dzwon→`magic_burst_chance`, Różdżka→`magic_infusion_chance`. Zaimplementowane w jednym miejscu - `Character::getEquipmentStats()` - więc automatycznie obejmuje wszystkie silniki walki (PvE, Lochy, Eventy Lokacji, PvP, Wojna Gildii) bez potrzeby duplikowania logiki w każdym z nich.
