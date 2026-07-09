# Architektura i Konwencje

Projekt oparty jest o framework **Laravel 12** z użyciem bazy danych **PostgreSQL 17**.

## Wzorce Projektowe
Aplikacja jest silnie ukierunkowana na koncepcje **Domain-Driven Design (DDD)** oraz **CQRS-lite**, wykorzystując modułowy podział wewnątrz katalogu `app/`.

- **Action / Service (Transaction Script):** Główna logika aplikacyjna i biznesowa zorganizowana jest w klasy typu Action/Service (np. `EncounterService`, `CreateCharacter`). Serwisy przyjmują parametry wejściowe, często zawinięte w DTO, wykonują operacje, zmieniają stan bazy danych (najczęściej w ramach transakcji) i zwracają klasę `Result` (wzorzec Result/Either) z ładunkiem sukcesu lub informacją o błędzie.
- **Result / Either:** Aplikacja unika rzucania wyjątków (Exceptions) dla standardowych scenariuszy biznesowych (np. brak punktów życia, brak wymaganych środków). Zamiast tego akcje zwracają obiekt `Result::ok()` lub `Result::error()`.
- **Domain Events:** Znaczące zmiany stanu emitują zdarzenia (np. `CharacterLeveledUp`), które pozwalają na odseparowanie skutków ubocznych za pomocą słuchaczy (Listeners).
- **Idempotentność:** Wszystkie operacje modyfikujące gospodarkę (przyznawanie nagród, waluty, tworzenie przedmiotów) używają kolumn `idempotency_key` w celu uniknięcia ponownego nałożenia efektów, zwłaszcza przy retry'ach lub odświeżaniu zlecenia.
- **ULID:** Aplikacja używa identyfikatorów ULID (Universally Unique Lexicographically Sortable Identifier) zamiast auto-incrementów dla bezpieczeństwa i ułatwienia shardingu/danych z wielu węzłów.
- **Baza Danych i JSONB:** Wiele modeli używa kolumn typu `jsonb` do zapisu złożonych danych (np. statystyk postaci, stanów rzutów walki, rezultatów walki), co pozwala na dużą elastyczność struktury danych w SQL bez potrzeby tworzenia wielu oddzielnych tabel relacyjnych.

## Struktura Katalogów
- `app/Application/`: Klasy Action, Services, DTO implementujące logikę przypadków użycia. Wewnątrz podzielone na moduły (np. `Characters`, `Combat`, `Loot`, `Shared`).
- `app/Domain/`: Czysta logika biznesowa i obiekty wartości, Eventy, reguły niezależne od frameworka.
- `app/Infrastructure/`: Implementacje infrastrukturalne – m.in. modele Eloquent w folderze `Persistence` (Repozatoria, Modele tabel DB), generatory losowości w `RNG`.
- `app/Livewire/`: Komponenty interfejsu użytkownika implementujące logikę prezentacyjną i stany widoków.
