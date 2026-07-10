# System Poczty (Faza 7)

Poczta w Berserk Rush jest obecnie kanałem komunikacji asynchronicznej w kierunku: **System -> Gracz**.
Nie służy do komunikacji P2P między graczami. Głównym zadaniem poczty jest obsługa zdarzeń asynchronicznych (jak wygaśnięcie oferty na markecie) oraz bezpieczne dostarczanie załączników.

## Modele
- **Mail**: Posiada informacje o odbiorcy, tytule, treści oraz (opcjonalnie) ustrukturyzowanych załącznikach w formacie JSON (przedmioty, waluty). Posiada również flagę `claimed` (odebrane).

## Główne Procesy (Akcje)
- **SendMailAction**: Proste narzędzie używane z wewnątrz innych procesów systemowych (np. przy zakupie przedmiotu) do powiadamiania gracza.
- **ClaimMailAction**: Odbiera przypisane załączniki w ramach jednej transakcji. Tworzy wpisy w `CurrencyLedger` lub `ItemLedger` za pomocą ustrukturyzowanego `idempotency_key`, aby zapobiec duplikacji odbioru z powodu race conditions. Oznacza mail jako `claimed`.

## Harmonogram zadań (Jobs)
- **ExpireOldMailJob**: Uruchamiany codziennie. Czyści bazę danych ze starszych, nieodebranych wiadomości (powyżej 90 dni), trwale kasując również powiązane, zablokowane przedmioty.

## Załączniki
- Oczekiwana struktura załącznika: `[{type: 'item', id: 'ULID'}, {type: 'gold', qty: 500}]`.
- Odbiór waluty automatycznie używa metody increment i odnotowuje transakcję. Odbiór przedmiotu przypisuje go do `inventory` gracza.
