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
- **Przedmioty i Materiały (Item / Material):** Z potworów wypadają materiały rzemieślnicze (do schowka materiałów) oraz z **bardzo małą szansą (waga 1, waga 2 dla bossów)** wybrane, unikalne przedmioty ekwipunku przypisane do danego gatunku potwora (np. *Wilk Leśny* może dropped *Sztylety z Kości Wilka* lub *Pancerz z Wilczej Skóry*, *Ogr Rozłupywacz* -> *Maczuga Ogra*, *Władca Cieni* -> *Piekielny Miecz Smoka* itp.). Pozostały ekwipunek gracze wytwarzają z zebranych surowców w systemie rzemiosła. Gdy przedmiot/materiał zostanie wylosowany, serwis tworzy fizyczną instancję w bazie (`ItemInstance`), ustala ilosc (`stack_size`) oraz rejestruje fakt zdobyczy w `ItemLedger`.

### 3. Zabezpieczenia Ekonomiczne
Aby zapobiec dublowaniu łupów z jednej i tej samej walki wskutek problemów z siecią lub ataków typu *Replay*, serwis przed wygenerowaniem zasobów weryfikuje istnienie `idempotency_key` zbudowanego na bazie ID spotkania `encounter:{encounter_id}:drop`. Wszystko przebiega we wspólnej transakcji bazodanowej, z naciskiem na zachowanie pełnej historii ekonomii (Ledgerów) celem łatwiejszego wykrywania exploitów u graczy.

### 4. Potwory rangi `worldboss` NIE mogą mieć własnego drop-u (ograniczenie architektury)
`DropService` uruchamia się wyłącznie po zwycięstwie gracza (`$winner === 'player'`). Starcia ze światowym bossem w `EncounterService` zawsze rozstrzygają się jako `$winner = 'enemy'` (to celowe — world boss to wspólny licznik obrażeń, patrz `docs/modules/world_boss.md`), więc **żaden wpis w `LootTable` przypisanej bezpośrednio do potwora rangi `worldboss` nigdy się nie wylosuje**. Był to realny bug wykryty 2026-07-28: materiał *Fragment Całunu* (potrzebny do craftu *Zbutwiałej Szaty Licza*) wisiał wyłącznie na worldbossie *Licz Cieni* i był w praktyce nieosiągalny.

Naprawa: `database/seeders/MonsterLootSeeder.php` przenosi teraz materiały/przedmioty każdego world bossa na zwykłego, zabijalnego potwora rangi `boss` z tej samej mapy (worldboss zostaje z pustymi listami `materials`/`items`, z komentarzem wyjaśniającym dlaczego). Zastosowano to dla wszystkich 8 world bossów w grze:

| Mapa | World boss (bez dropu) | Nowy właściciel dropu (rank `boss`) |
|---|---|---|
| Mroczny Las | Król Lasu | Strażnik Puszczy |
| Stare Ruiny | Licz Cieni | Władca Krypty |
| Jaskinia Trolli | Król Trolli | Starożytny Ogr |
| Pustkowia Orków | Wódz Orków | Niszczyciel Pustkowi |
| Bagna Grozy | Moczarowy Behemot | Królowa Wiedźm |
| Góry Cienia | Smok Cienia | Władca Cieni |
| Wieża Magów | Arcymag | Wielki Inkwizytor |
| Skażone Miasto | Pan Zniszczenia | Książę Zniszczenia |

Przy dodawaniu nowego world bossa w przyszłości **nie przypisuj mu unikalnych materiałów/przedmiotów w `MonsterLootSeeder`** — trafi na tę samą martwą ścieżkę. Zamiast tego przypisz je zwykłemu potworowi rangi `boss` tej samej mapy, tak jak w tabeli powyżej.
