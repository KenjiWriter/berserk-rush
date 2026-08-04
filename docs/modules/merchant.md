# Moduł: Handlarz (Merchant) - Kupno i Sprzedaż Ekwipunku

## 1. Przeznaczenie Domeny
Domena **Handlarz** to zunifikowany NPC w mieście, u którego gracz kupuje nową broń, zbroję i biżuterię za złoto oraz sprzedaje zbędny ekwipunek/materiały z plecaka. Domena powstała ze scalenia dwóch wcześniej zduplikowanych NPC - Brońmistrza (`Weaponsmith`, sprzedawał tylko `type === 'weapon'`) i Zbrojmistrza (`Armorsmith`, sprzedawał `armor`/`accessory`) - analogicznie do wcześniejszego scalenia ulepszania/craftingu w domenie **Kowal** (patrz `docs/modules/blacksmith.md`).

Handlarz **nie** obsługuje ulepszania (`forge`) ani rzemiosła (`crafting`) - dostęp do Kowala odbywa się przez osobną pozycję "Kowal" w bocznej nawigacji (`desktop-nav.blade.php`/`mobile-nav.blade.php`), tak jak w przypadku każdej innej domeny miasta.

## 2. Pliki Domeny
- Komponent Livewire: `app/Livewire/City/Merchant.php`
- Widok: `resources/views/livewire/city/merchant.blade.php`
- Trasa: `city.merchant` (`/play/{character}/merchant`), zarejestrowana w `routes/web.php`
- Usługi domenowe (reużyte, bez zmian): `app/Application/Items/ShopService.php`

## 3. Dostęp
- **Hub:** kafelek "Handlarz" w bento-gridzie (desktop) oraz przycisk w karuzeli "Dzielnica Handlowa" (mobile), routowane przez `Hub::goTo('merchant')`.
- **Nawigacja:** pozycja "Handlarz" w sekcji "Sklepy & Rzemiosło" zarówno w `desktop-nav.blade.php`, jak i `mobile-nav.blade.php`.

## 4. Asortyment (`MerchantItem`)
Asortyment Handlarza to wszystkie wpisy `MerchantItem` z `merchant_id = 'merchant'` o `required_level <= poziom postaci`. Seeder `database/seeders/ShopEquipmentSeeder.php` generuje asortyment w "tematycznych" grupach (`themes`) rosnących wraz z poziomem (1, 10, 20, 30, 40, 50, 60, 70, 80, 90) - każda grupa zawiera pełny zestaw: 6 typów broni (miecz/topór/łuk/różdżka/sztylet/dzwon) oraz zbroję/hełm/buty/naszyjnik/pierścień. Sklep gladiatora (`merchant_id = 'gladiator'`, poziom 55, NPC `GladiatorShop`) jest osobnym asortymentem i nie jest częścią Handlarza.

> **Balans Ekwipunku Sklepowego:**
> - **Statystyki Tieru 10 (Rycerski)**: Zostały podniesione o **+15%** (skala 4.0 → 4.6), dając więcej pancerza, HP oraz ataku, aby ułatwić postęp postaci na 15 mapie.
> - **Bonus Szansa na Podwójny Łup (`double_drop_chance`)**: Każdy element ekwipunku u Handlarza posiada wbudowaną delikatną szansę na podwójny drop przedmiotu – płynnie skalowaną od **5%** (dla poziomu 1) do **15%** (dla poziomu 50+).

Widok jest dwukolumnowy: **lewa** kolumna to Asortyment Sklepu, **prawa** to Plecak/Magazyn Materiałów gracza (kolejność celowo odwrotna niż w dawnych `Weaponsmith`/`Armorsmith`, gdzie plecak był po lewej).

## 5. Tiery (podział wg poziomu)
Ponieważ scalenie podwoiło liczbę pozycji w jednym widoku, `Merchant::render()` wylicza dynamicznie listę dostępnych tierów jako unikalne wartości `required_level` obecne w odblokowanym (wg poziomu postaci) asortymencie (`$availableTiers`) - **bez hardkodowania** konkretnych progów, więc jeśli seeder doda/zmieni tier w przyszłości, pasek filtrów w UI automatycznie się dostosuje. Pasek tierów w widoku (`setTierFilter($tier)`, właściwość `$tierFilter`) pozwala zawęzić widoczny asortyment do jednej grupy poziomowej naraz (lub "Wszystkie Tiery").

Niezależnie od wybranego filtra, siatka Asortymentu jest zawsze wizualnie pogrupowana wg tieru (`$shopItems->groupBy('required_level')` w widoku) - każda grupa poprzedzona jest nagłówkiem-separatorem ("Tier N (poz. X+)"), więc nawet przy "Wszystkie Tiery" gracz widzi wyraźny podział między kolejnymi poziomami asortymentu zamiast jednej płaskiej siatki.

## 6. Filtr slotu ekwipunku
Analogicznie do filtra typu/slotu w Kowalu (`docs/modules/blacksmith.md`, sekcja 5), pasek filtrów (`$slotFilter` w `Merchant.php`, metoda `setSlotFilter(string $filter)`) pozwala zawęzić zarówno asortyment sklepu, jak i widok plecaka gracza (zakładka "Przedmioty") do jednej kategorii:
- **Wszystko** (`all`) - domyślny, brak filtrowania.
- **Broń** (`weapon`) - `ItemTemplate::slot === 'main_hand'`.
- **Zbroja** (`chest`) - slot `chest`.
- **Hełmy** (`head`) - slot `head`.
- **Buty** (`feet`) - slot `feet`.
- **Naszyjniki** (`neck`) - slot `neck`.
- **Pierścienie** (`ring`) - slot `ring`.

Filtr slotu i filtr tieru działają niezależnie i łącznie (logiczne AND) - filtrowanie odbywa się po stronie komponentu (`matchesSlotFilter()`, `matchesTierFilter()`), analogicznie do `matchesItemFilter()` w Kowalu.

## 7. Kupno i sprzedaż
Logika kupna (`buyItem()`) i sprzedaży (`sellItem()`, `sellSelectedItems()` przy masowej sprzedaży) jest bez zmian w stosunku do dawnych `Weaponsmith`/`Armorsmith` - reużywa `ShopService::buyItem()`/`sellItem()`/`sellMultipleItems()`. Zakładka "Materiały" w panelu plecaka gracza pozwala sprzedawać surowce rzemieślnicze niezależnie od filtra slotu (filtr slotu dotyczy wyłącznie zakładki "Przedmioty").

## 8. Tutorial
Kroki 17-20 samouczka (`resources/views/livewire/global/tutorial-overlay.blade.php`) prowadzą nową postać do Handlarza po kupno pierwszej broni (Miecz Nowicjusza) - dialogi Kapitana odwołują się teraz do "Handlarza" zamiast osobno do "Brońmistrza". Zakup dowolnej broni (`template->type === 'weapon'`) na etapie `game_stage === 19` odblokowuje kolejny etap samouczka (`Merchant::buyItem()`).

## 9. Historia zmian
> **Refaktor:** Wcześniej kupno/sprzedaż ekwipunku było zduplikowane w dwóch osobnych komponentach (`Weaponsmith` obsługiwał tylko bronie, `Armorsmith` tylko zbroje/biżuterię), każdy z niemal identycznym kodem widoku i logiki (`ShopService`). Scalenie do jednego NPC **Handlarz** eliminuje tę duplikację - analogicznie do wcześniejszego scalenia ulepszania/craftingu w Kowalu. Ponieważ scalony asortyment zawiera dwa razy więcej pozycji, dodano pasek tierów poziomowych (wyliczany dynamicznie z `required_level`) oraz rozszerzono filtr slotu o biżuterię (naszyjniki/pierścienie), których wcześniej nie dało się filtrować osobno.
