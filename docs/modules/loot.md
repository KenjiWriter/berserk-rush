# Moduł Łupów i Gospodarki (Loot & Economy)

Moduł ten dba o przyznawanie i logowanie nagród po zakończeniu walki.

## Implementacja
- Pliki logiki: `app/Application/Loot/DropService.php`, `WeightedPicker.php`
- Modele: `LootTable`, `LootTableEntry`, `CurrencyLedger`, `ItemLedger`, `ItemInstance`

## Mechaniki

### 1. Tabele Zrzutów (`LootTable`)
Każdy potwór ma przypisaną własną tabelę loot'u. Gdy potwór zostaje zabity w trybie walki:
- Weryfikowane są pozycje (`entries`) w jego tabeli zrzutów.
- Za pomocą algorytmu **Weighted Picker** losowany jest łup. Łup posiada swoją własną wagę określającą jak często ma szansę wypaść w stosunku do innych opcji.

### 2. Generowanie Łupu (`DropService`)
Uruchamiana jest logika losująca nagrody ze zwycięskiej walki. Główne typy zdobyczy to:
- **Złoto (Gold) i Gemy (Gems):** Generowane w losowych ilościach. Serwis loguje dopływ waluty za pomocą `CurrencyLedger`. Złoto (gold) przypisywane jest bezpośrednio do walczącej postaci (`characters`), a waluta premium (gems) współdzielona jest na całe konto gracza (`users`). Jest to księga audytowa zapewniająca, że historia zasilania konta i jego obecne saldo (zapisywane w locie) pokrywają się ze stanem wirtualnego portfela, a `idempotency_key` eliminuje ryzyko zdublowania dopływu gotówki po stronie serwera.
- **Przedmioty / Materiały (Item / Material):** Przedmioty, bronie, materiały rzemieślnicze. Gdy zostaną wylosowane, serwis tworzy fizyczną instancję przedmiotu w bazie (`ItemInstance`), ustala jego bazową ilość (`stack_size`), ustala jego miejsce (`inventory`) oraz odnotowuje fakt jego otrzymania w `ItemLedger` z unikalnym ULID. Przedmiot początkowo pojawia się jako niezidentyfikowany o rzadkości `common` (+0 na poziomie ulepszenia).

### 3. Zabezpieczenia Ekonomiczne
Aby zapobiec dublowaniu łupów z jednej i tej samej walki wskutek problemów z siecią lub ataków typu *Replay*, serwis przed wygenerowaniem zasobów weryfikuje istnienie `idempotency_key` zbudowanego na bazie ID spotkania `encounter:{encounter_id}:drop`. Wszystko przebiega we wspólnej transakcji bazodanowej, z naciskiem na zachowanie pełnej historii ekonomii (Ledgerów) celem łatwiejszego wykrywania exploitów u graczy.
