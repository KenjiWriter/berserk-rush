# Moduł: Kowal (Blacksmith) - Ulepszanie i Rzemiosło

## 1. Przeznaczenie Domeny
Domena **Kowal** to zunifikowany punkt w mieście, w którym gracz zajmuje się rozwojem swojego sprzętu bojowego - zarówno **broni**, jak i **zbroi** - w jednym, wspólnym widoku. Domena powstała z wydzielenia funkcjonalności ulepszania (`forge`) i wytwarzania (`crafting`), które wcześniej były zduplikowane osobno u Brońmistrza (`Weaponsmith`) i Zbrojmistrza (`Armorsmith`).

Kupno i sprzedaż przedmiotów obsługuje dziś jeden, scalony NPC **Handlarz** (`app/Livewire/City/Merchant.php`, route `city.merchant`) - patrz `docs/modules/merchant.md`. Handlarz posiada w nagłówku przycisk przekierowujący do Kowala ("Kowal (Ulepszanie i Rzemiosło)"), aby zachować płynność nawigacji.

Wiedźma pozostaje osobną domeną odpowiedzialną wyłącznie za crafting **mikstur** (`consumable`) - patrz `docs/modules/witch_and_crafting.md`. Kowal obsługuje crafting oraz ulepszanie **broni i zbroi**.

## 2. Pliki Domeny
- Komponent Livewire: `app/Livewire/City/Blacksmith.php`
- Widok: `resources/views/livewire/city/blacksmith.blade.php`
- Trasa: `city.blacksmith` (`/city/{character}/blacksmith`), zarejestrowana w `routes/web.php`
- Usługi domenowe: `app/Application/Items/UpgradeService.php`, `app/Application/Items/CraftingService.php`, `app/Application/Items/DismantleService.php`

## 3. Dostęp
- **Hub:** kafelek "Kowal" w bento-gridzie (desktop) oraz przycisk w karuzeli "Dzielnica Handlowa" (mobile), routowane przez `Hub::goTo('blacksmith')`.
- **Nawigacja:** pozycja "Kowal" w sekcji "Sklepy & Rzemiosło" zarówno w `desktop-nav.blade.php`, jak i `mobile-nav.blade.php`.
- **Z poziomu Handlarza:** przycisk w nagłówku widoku sklepu, `goToBlacksmith()`.

## 4. Zakładki
Widok Kowala posiada trzy zakładki (`activeTab`):
1.  **`forge` (Kuźnia Ulepszeń):** Lista przedmiotów gracza (ekwipunek + założone), które można ulepszyć - filtrowane po `type` w `['weapon', 'armor', 'accessory']`. Wybór przedmiotu (`selectItemForUpgrade`) otwiera panel ulepszenia z kosztami i szansą powodzenia, obsługiwany przez `UpgradeService`.
2.  **`crafting` (Rzemiosło):** Lista receptur (`ItemRecipe`), których wynikowy szablon (`resultItemTemplate`) ma `type` w `['weapon', 'armor', 'accessory']`. Logika i mechanika rzadkości identyczna jak opisana w `docs/modules/witch_and_crafting.md` (sekcja "System Warzenia i Rzemiosła"), realizowana przez wspólny `CraftingService`.
3.  **`dismantle` (Przetapianie):** Pozwala nieodwracalnie przetapiać nieużywany ekwipunek z plecaka na nowy materiał rzemieślniczy **Runiczny Odłamek** (`DismantleService`). Liczba uzyskiwanych odłamków zależy od poziomu przedmiotu, mnożnika rzadkości, poziomu ulepszenia oraz liczby zaczarowań.

## 5. Filtr typu/slotu ekwipunku
Ponieważ zakładki (Kuźnia, Rzemiosło, Przetapianie) mieszają bronie, zbroje i akcesoria w jednej liście, dodano pasek filtrów (`$itemFilter` w `Blacksmith.php`, metoda `setItemFilter(string $filter)`):
- **Wszystko** (`all`) - domyślny, brak filtrowania.
- **Broń** (`weapon`) - tylko przedmioty/`ItemRecipe` z `type === 'weapon'` (slot `main_hand`).
- **Hełmy** (`head`) - zbroja w slocie `head`.
- **Zbroje** (`chest`) - zbroja w slocie `chest`.
- **Buty** (`feet`) - zbroja w slocie `feet`.
- **Naszyjniki** (`neck`) - akcesoria w slocie `neck`.
- **Pierścienie** (`ring`) - akcesoria w slocie `ring`.

Filtr działa spójnie dla wszystkich zakładek.

## 6. Szczegóły mechanik
Pełny opis mechaniki ulepszania (szanse powodzenia, koszty, efekty sukcesu/porażki, wymaganie odłamków $>70$ lvl od $+6$ do $+9$) znajduje się w `docs/modules/upgrades.md`. Pełny opis mechaniki craftingu znajduje się w `docs/modules/witch_and_crafting.md`.

## 7. Przetapianie Przedmiotów i Runiczne Odłamki
Przetapianie przedmiotu u Kowala przekształca broń, zbroję lub akcesorium w materiał **Runiczny Odłamek** trafiający bezpośrednio do schowka na materiały (`material_stash`).
- **Wzór uzysku:** $\max(1, \text{round}(\text{Base} \times \text{RarityMult} \times \text{UpgradeMult} \times \text{EnchantMult}))$
  - $\text{Base} = \lceil\text{level\_requirement} / 10\rceil$
  - $\text{RarityMult}$: common: 1.0x, uncommon: 2.0x, rare: 4.0x, epic: 8.0x, legendary: 15.0x
  - $\text{UpgradeMult}$: $+1 \to +9$ (+10% do +500%)
  - $\text{EnchantMult}$: $1.0 + (\text{liczba zaczarowań} \times 0.15)$
- **Zastosowania Runicznych Odłamków:**
  1. Ulepszenia ekwipunku $>70$ lvl na poziomy $+6 \dots +9$ (15, 35, 75, 150 odłamków).
  2. Wymóg do awansu poziomu Czempiona ($50 + \text{level} \times 10$ odłamków).

## 8. Historia zmian
> **Runiczne Odłamki & Przetapianie:** Dodano 3. zakładkę w widoku Kowala `dismantle` (Przetapianie), obsługiwaną przez `DismantleService`. Wprowadzono uniwersalny surowiec `Runiczny Odłamek` z dedykowaną grafiką, wymóg odłamków do ulepszeń ekwipunku $>70$ lvl od $+6$ do $+9$ oraz wymóg odłamków przy awansie Czempiona (`ChampionService`).
