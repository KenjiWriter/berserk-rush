# Moduł: Kowal (Blacksmith) - Ulepszanie i Rzemiosło

## 1. Przeznaczenie Domeny
Domena **Kowal** to zunifikowany punkt w mieście, w którym gracz zajmuje się rozwojem swojego sprzętu bojowego - zarówno **broni**, jak i **zbroi** - w jednym, wspólnym widoku. Domena powstała z wydzielenia funkcjonalności ulepszania (`forge`) i wytwarzania (`crafting`), które wcześniej były zduplikowane osobno u Brońmistrza (`Weaponsmith`) i Zbrojmistrza (`Armorsmith`).

Kupno i sprzedaż przedmiotów obsługuje dziś jeden, scalony NPC **Handlarz** (`app/Livewire/City/Merchant.php`, route `city.merchant`) - patrz `docs/modules/merchant.md`. Handlarz posiada w nagłówku przycisk przekierowujący do Kowala ("Kowal (Ulepszanie i Rzemiosło)"), aby zachować płynność nawigacji.

Wiedźma pozostaje osobną domeną odpowiedzialną wyłącznie za crafting **mikstur** (`consumable`) - patrz `docs/modules/witch_and_crafting.md`. Kowal obsługuje crafting oraz ulepszanie **broni i zbroi**.

## 2. Pliki Domeny
- Komponent Livewire: `app/Livewire/City/Blacksmith.php`
- Widok: `resources/views/livewire/city/blacksmith.blade.php`
- Trasa: `city.blacksmith` (`/city/{character}/blacksmith`), zarejestrowana w `routes/web.php`
- Usługi domenowe (reużyte, bez zmian): `app/Application/Items/UpgradeService.php`, `app/Application/Items/CraftingService.php`

## 3. Dostęp
- **Hub:** kafelek "Kowal" w bento-gridzie (desktop) oraz przycisk w karuzeli "Dzielnica Handlowa" (mobile), routowane przez `Hub::goTo('blacksmith')`.
- **Nawigacja:** pozycja "Kowal" w sekcji "Sklepy & Rzemiosło" zarówno w `desktop-nav.blade.php`, jak i `mobile-nav.blade.php`.
- **Z poziomu Handlarza:** przycisk w nagłówku widoku sklepu, `goToBlacksmith()`.

## 4. Zakładki
Widok Kowala posiada dwie zakładki (`activeTab`):
1.  **`forge` (Kuźnia Ulepszeń):** Lista przedmiotów gracza (ekwipunek + założone), które można ulepszyć - filtrowane po `type` w `['weapon', 'armor']` zamiast po pojedynczym typie jak w poprzedniej implementacji. Wybór przedmiotu (`selectItemForUpgrade`) otwiera panel ulepszenia z kosztami i szansą powodzenia, obsługiwany przez `UpgradeService`.
2.  **`crafting` (Rzemiosło):** Lista receptur (`ItemRecipe`), których wynikowy szablon (`resultItemTemplate`) ma `type` w `['weapon', 'armor']`. Logika i mechanika rzadkości identyczna jak opisana w `docs/modules/witch_and_crafting.md` (sekcja "System Warzenia i Rzemiosła"), realizowana przez wspólny `CraftingService`.

Obie usługi (`UpgradeService`, `CraftingService`) są z natury generyczne (operują na dowolnym `ItemInstance`/`ItemRecipe`), dlatego ich kod nie wymagał żadnych zmian - scalenie dotyczyło wyłącznie warstwy Livewire/UI.

## 5. Filtr typu/slotu ekwipunku
Ponieważ obie zakładki (Kuźnia i Rzemiosło) mieszają teraz bronie i różne części zbroi w jednej liście, dodano pasek filtrów (`$itemFilter` w `Blacksmith.php`, metoda `setItemFilter(string $filter)`) widoczny nad zawartością obu zakładek:
- **Wszystko** (`all`) - domyślny, brak filtrowania.
- **Broń** (`weapon`) - tylko przedmioty/`ItemRecipe` z `type === 'weapon'` (slot `main_hand`).
- **Hełmy** (`head`) - zbroja w slocie `head`.
- **Zbroje** (`chest`) - zbroja w slocie `chest`.
- **Buty** (`feet`) - zbroja w slocie `feet`.

Filtr działa identycznie dla listy przedmiotów do ulepszenia (`upgradableItems`) oraz listy receptur (`recipes`) - filtrowanie odbywa się po stronie komponentu (metoda prywatna `matchesItemFilter()`), więc obie zakładki reagują spójnie na wybrany filtr.

## 6. Szczegóły mechanik
Pełny opis mechaniki ulepszania (szanse powodzenia, koszty, efekty sukcesu/porażki) znajduje się w `docs/modules/upgrades.md`. Pełny opis mechaniki craftingu (encja `ItemRecipe`, losowanie rzadkości, panel administratora) znajduje się w `docs/modules/witch_and_crafting.md`.

## 7. Podpowiedzi Materiałów (Skąd zdobyć)
Zarówno w zakładce `forge` (materiały do ulepszenia, budowane przez `UpgradeService::getUpgradeCost()`), jak i `crafting` (składniki receptur, budowane w `Blacksmith::render()`), tooltip każdego materiału ("Do zdobycia z") pokazuje listę potworów, z których dany surowiec wypada, **wraz z nazwą krainy** (`Monster::map->name`), np. "Wilk Leśny · Mroczny Las". Dane pobierane są zbiorczo z `LootTableEntry` (z eager-loadem `lootTable.monsters.map`), żeby uniknąć zapytań N+1 przy wielu materiałach jednocześnie.

## 8. Historia zmian
> **Refaktor:** Wcześniej funkcjonalność ulepszania i craftingu broni/zbroi była zduplikowana w dwóch osobnych komponentach (`Weaponsmith` obsługiwał tylko `type === 'weapon'`, `Armorsmith` tylko `type === 'armor'`/`'accessory'`), każdy z własnymi zakładkami `forge`/`crafting` i niemal identycznym kodem widoku. Wydzielenie do wspólnej domeny Kowal eliminuje tę duplikację i pozwala ulepszać/wytwarzać dowolny ekwipunek bojowy w jednym miejscu. Dodano też filtr typu/slotu, żeby ułatwić nawigację po połączonej liście broni i różnych części zbroi.
