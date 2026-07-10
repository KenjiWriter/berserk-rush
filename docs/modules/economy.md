# Gospodarka i Market (Faza 7)

Moduł gospodarki w Berserk Rush obsługuje waluty (złoto, klejnoty) oraz handel między graczami.

## Modele
- **CurrencyLedger**: Służy jako dziennik transakcyjny dla każdej operacji na walucie, zapobiegając nadużyciom poprzez klucze idempotencji.
- **ItemLedger**: Dziennik ruchów przedmiotów (transfery do plecaka, wyposażenie, wystawienie na market).
- **MarketListing**: Aktywne, sprzedane, anulowane lub wygasłe oferty rynkowe graczy.
- **Purchase**: Rekordy potwierdzające zakup (kto, co, za ile, kiedy).

## Główne Procesy (Akcje)
- **CreateMarketListingAction**: Zabiera przedmiot z ekwipunku, pobiera bezzwrotną opłatę manipulacyjną od sprzedawcy (w zależności od czasu 24/48/72h: 100/250/500 sztuk złota) i tworzy ofertę.
- **BuyMarketListingAction**: Rezerwuje ofertę za pomocą pesymistycznego blokowania (`lockForUpdate`), przesyła przedmiot kupującemu oraz złoto/klejnoty sprzedającemu (z potrąceniem 5% prowizji systemowej) przez **pocztę systemową**.
- **CancelMarketListingAction**: Pozwala sprzedawcy anulować ofertę. Zwraca przedmiot, ale opłata nie jest zwracana.

## Harmonogram zadań (Jobs)
- **ExpireMarketListingsJob**: Uruchamiany co godzinę. Zmienia status przeterminowanych ofert i zwraca przedmioty sprzedawcom za pomocą poczty systemowej.

## Zastosowane Wzorce
Wszystkie ważne modyfikacje (zakup, wystawienie) wykorzystują:
- Wzorzec **Result** (z obsługą błędów).
- Transakcje bazodanowe (`DB::transaction`).
- Klucze idempotencji i wpisy w dziennikach (Ledgers).
- Zdarzenia domenowe (Events: `MarketListingCreated`, `MarketListingSold`, `MarketListingExpired`).
