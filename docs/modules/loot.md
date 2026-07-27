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
- **Przedmioty i Materiały (Item / Material):** Z potworów wypadają materiały rzemieślnicze (do schowka materiałów) oraz z **bardzo małą szansą (waga 1)** wybrane, unikalne przedmioty ekwipunku przypisane do danego gatunku potwora (np. *Wilk Leśny* może dropped *Sztylety z Kości Wilka* lub *Pancerz z Wilczej Skóry*, *Ogr Rozłupywacz* -> *Maczuga Ogra*, *Smok Cienia* -> *Piekielny Miecz Smoka* itp.). Pozostały ekwipunek gracze wytwarzają z zebranych surowców w systemie rzemiosła. Gdy przedmiot/materiał zostanie wylosowany, serwis tworzy fizyczną instancję w bazie (`ItemInstance`), ustala ilosc (`stack_size`) oraz rejestruje fakt zdobyczy w `ItemLedger`.

### 3. Zabezpieczenia Ekonomiczne
Aby zapobiec dublowaniu łupów z jednej i tej samej walki wskutek problemów z siecią lub ataków typu *Replay*, serwis przed wygenerowaniem zasobów weryfikuje istnienie `idempotency_key` zbudowanego na bazie ID spotkania `encounter:{encounter_id}:drop`. Wszystko przebiega we wspólnej transakcji bazodanowej, z naciskiem na zachowanie pełnej historii ekonomii (Ledgerów) celem łatwiejszego wykrywania exploitów u graczy.
