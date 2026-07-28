# Moduł: Kowal (Blacksmith) - Ulepszanie Sprzętu

## 1. Przeznaczenie Domeny
Domena **Kowal** to zunifikowany punkt w mieście, w którym gracz ulepsza swój sprzęt bojowy - zarówno **broń**, jak i **zbroję** - w jednym, wspólnym widoku. Domena powstała z wydzielenia funkcjonalności ulepszania (`forge`), która wcześniej była zduplikowana osobno u Brońmistrza (`Weaponsmith`) i Zbrojmistrza (`Armorsmith`).

Brońmistrz i Zbrojmistrz od tej pory odpowiadają wyłącznie za **kupno i sprzedaż** przedmiotów (zakładka `shop`) i posiadają w nagłówku przycisk przekierowujący do Kowala ("Kowal (Ulepszanie)"), aby zachować płynność nawigacji dla graczy przyzwyczajonych do starego układu.

> **Uwaga:** Kowal obsługuje wyłącznie ulepszanie (`forge`). Wytwarzanie nowych przedmiotów (crafting) - zarówno mikstur, jak i broni/zbroi z receptur - pozostaje wyłącznie domeną Wiedźmy (`Witch`), patrz `docs/modules/witch_and_crafting.md`.

## 2. Pliki Domeny
- Komponent Livewire: `app/Livewire/City/Blacksmith.php`
- Widok: `resources/views/livewire/city/blacksmith.blade.php`
- Trasa: `city.blacksmith` (`/city/{character}/blacksmith`), zarejestrowana w `routes/web.php`
- Usługa domenowa (reużyta, bez zmian): `app/Application/Items/UpgradeService.php`

## 3. Dostęp
- **Hub:** kafelek "Kowal" w bento-gridzie (desktop) oraz przycisk w karuzeli "Dzielnica Handlowa" (mobile), routowane przez `Hub::goTo('blacksmith')`.
- **Nawigacja:** pozycja "Kowal" w sekcji "Sklepy & Rzemiosło" zarówno w `desktop-nav.blade.php`, jak i `mobile-nav.blade.php`.
- **Z poziomu Brońmistrza/Zbrojmistrza:** przycisk w nagłówku widoku sklepu, `goToBlacksmith()`.

## 4. Widok
Widok Kowala nie ma zakładek (jedna funkcja = jeden ekran). Zawiera **Kuźnię Ulepszeń**: listę przedmiotów gracza (ekwipunek + założone), które można ulepszyć - filtrowaną po `type` w `['weapon', 'armor']` zamiast po pojedynczym typie jak w poprzedniej implementacji. Wybór przedmiotu (`selectItemForUpgrade`) otwiera panel ulepszenia z kosztami i szansą powodzenia, obsługiwany przez `UpgradeService`.

`UpgradeService` jest z natury generyczny (operuje na dowolnym `ItemInstance`), dlatego jego kod nie wymagał żadnych zmian - scalenie dotyczyło wyłącznie warstwy Livewire/UI.

## 5. Szczegóły mechaniki
Pełny opis mechaniki ulepszania (szanse powodzenia, koszty, efekty sukcesu/porażki) znajduje się w `docs/modules/upgrades.md`.

## 6. Historia zmian
> **Refaktor:** Wcześniej ulepszanie broni/zbroi było zduplikowane w dwóch osobnych komponentach (`Weaponsmith` obsługiwał tylko `type === 'weapon'`, `Armorsmith` tylko `type === 'armor'`/`'accessory'`), każdy z własną zakładką `forge` i niemal identycznym kodem widoku. Wydzielenie do wspólnej domeny Kowal eliminuje tę duplikację i pozwala ulepszać dowolny ekwipunek bojowy w jednym miejscu. Crafting (wytwarzanie) pozostał scentralizowany u Wiedźmy i obejmuje teraz również receptury na broń i zbroję, nie tylko mikstury.
