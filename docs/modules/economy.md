# Gospodarka i Market (Faza 7)

Moduł gospodarki w Berserk Rush obsługuje waluty (złoto, klejnoty) oraz handel między graczami.

## Modele
- **CurrencyLedger**: Służy jako dziennik transakcyjny dla każdej operacji na walucie, zapobiegając nadużyciom poprzez klucze idempotencji.
- **ItemLedger**: Dziennik ruchów przedmiotów (transfery do plecaka, wyposażenie, wystawienie na market).
- **MarketListing**: Aktywne, sprzedane, anulowane lub wygasłe oferty rynkowe graczy. Posiada `item_instance_id` LUB `pet_id` (dokładnie jedno z dwóch) - patrz `docs/modules/pets.md`, sekcja "Handel Chowańcami".
- **Purchase**: Rekordy potwierdzające zakup (kto, co, za ile, kiedy).

## Główne Procesy (Akcje)
- **CreateMarketListingAction**: Zabiera przedmiot z ekwipunku, pobiera bezzwrotną opłatę manipulacyjną od sprzedawcy (w zależności od czasu 24/48/72h: 100/250/500 sztuk złota) i tworzy ofertę. Metoda `executeForPet()` obsługuje analogicznie wystawianie chowańców (wymaga braku aktywnego towarzysza i pustego ekwipunku peta), bez ruchów `ItemInstance`/`ItemLedger` - pet nigdy fizycznie nie "leży" na rynku.
- **BuyMarketListingAction**: Rezerwuje ofertę za pomocą pesymistycznego blokowania (`lockForUpdate`), przesyła przedmiot (lub chowańca - zmiana `character_id`) kupującemu oraz złoto/klejnoty sprzedającemu (z potrąceniem 5% prowizji systemowej) przez **pocztę systemową**.
- **CancelMarketListingAction**: Pozwala sprzedawcy anulować ofertę. Zwraca przedmiot (lub nic nie robi dla peta, poza zmianą statusu), ale opłata nie jest zwracana.

## Harmonogram zadań (Jobs)
- **ExpireMarketListingsJob**: Uruchamiany co godzinę. Zmienia status przeterminowanych ofert i zwraca przedmioty sprzedawcom za pomocą poczty systemowej.

## Filtrowanie Rynku (MarketComponent)
Widok `livewire/economy/market.blade.php` (obsługiwany przez `GetMarketListingsQuery`) pozwala filtrować aktywne oferty po: nazwie, rzadkości, kategorii (slot), walucie, maks. cenie, **min./maks. poziomie przedmiotu** oraz **checkliście statystyk bonusowych** (STR/AGI/INT/VIT, obrażenia fizyczne/magiczne, obrona, szansa krytyczna, bonus HP/many).
- Checklista statystyk działa w logice **AND** – oferta musi posiadać KAŻDĄ z zaznaczonych statystyk (sprawdzane zarówno w `roll_stats` przedmiotu, jak i `base_stats` szablonu).
- Dozwolone klucze statystyk są zdefiniowane w białej liście `GetMarketListingsQuery::ALLOWED_STAT_FILTERS`, by bezpiecznie osadzać je w wyrażeniach SQL wyciągających wartość z kolumn JSON(B).
- Wyrażenie SQL do odczytu wartości z JSON jest budowane w zależności od sterownika bazy (`GetMarketListingsQuery::jsonStatExpr`) – składnia PostgreSQL (`jsonb ->> 'klucz'`) różni się od MySQL (`json ->> '$.klucz'`), więc obie są obsługiwane.

## Infobox i Porównywanie Przedmiotów (Item Comparison)
Kliknięcie w kartę dowolnego przedmiotu na rynku (`market.blade.php`) wywołuje wyśrodkowany, interaktywny modal infobox (`<x-item-tooltip>`) działający w oparciu o dyrektywę Alpine `smartTooltip()` z podpiętą nakładką `fixed inset-0 z-[9999] pointer-events-auto`.
- **Porównanie z założonym ekwipunkiem (`:equippedItem`)**: Komponent `MarketComponent` wyciąga z bazy i przekazuje do widoku słownik założonych przedmiotów gracza (`$equipped[$slot]`). Infobox automatycznie porównuje statystyki oferty ze znajdującym się w tym samym slocie ekwipunkiem postaci, prezentując różnice wartości (zielony kolor dla zysku, czerwony dla straty).
- **Przełącznik Porównywania**: Gracz może w każdej chwili przełączać widok porównania przyciskiem ("Pokaż/Ukryj porównanie z założonym"). Preferencja ta jest zapisywana w `localStorage` (`global_show_item_comparison`).
- **Przycisk Akcji Zakupu**: Wewnątrz modalu infobox znajduje się przycisk "KUP ZA [cena]", który pozwala przejść do dwuetapowego zakupu przedmiotu bezpośrednio po przeanalizowaniu jego statystyk i porównania.
- **Moje Oferty**: Podgląd infoboxa i porównania dostępny jest również w tabeli "Moje Aktywne Oferty Aukcyjne" po kliknięciu w wystawiony przedmiot.

## Potwierdzenie Zakupu (Modal)
Aby zapobiec przypadkowym zakupom ofert na rynku, kliknięcie przycisku "KUP ZA..." w `MarketComponent` wywołuje dwuetapowy proces:
1. `MarketComponent::confirmBuy($listingId)` weryfikuje ofertę i otwiera modal potwierdzający (`$showConfirmBuyModal = true`).
2. Modal wyświetla podgląd przedmiotu lub chowańca (nazwa, ikona, rzadkość, poziom, statystyki bonusowe, sprzedawca) oraz zestawienie ceny z aktualnym stanem portfela gracza.
3. Dopiero kliknięcie "Potwierdzam zakup" w modale wywołuje `MarketComponent::buyItem()` i realizuje akcję `BuyMarketListingAction`. Użytkownik może w każdej chwili anulować zakup przyciskiem "Anuluj" lub klikając w tło modalu.

## Zastosowane Wzorce
Wszystkie ważne modyfikacje (zakup, wystawienie) wykorzystują:
- Wzorzec **Result** (z obsługą błędów).
- Transakcje bazodanowe (`DB::transaction`).
- Klucze idempotencji i wpisy w dziennikach (Ledgers).
- Zdarzenia domenowe (Events: `MarketListingCreated`, `MarketListingSold`, `MarketListingExpired`).

