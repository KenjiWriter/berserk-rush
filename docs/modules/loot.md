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
- **Przedmioty i Materiały (Item / Material):** Z potworów wypadają materiały rzemieślnicze (do schowka materiałów). **Bezpośredni drop unikalnego ekwipunku przypisanego do gatunku potwora (`reward_type = 'item'`) jest od 2026-07-29 wyzerowany globalnie na mapach przygód (waga 0 na każdym wpisie, patrz balans niżej)** — cały ekwipunek gracze zdobywają wyłącznie przez system rzemiosła z zebranych surowców (patrz `docs/modules/witch_and_crafting.md`). Jedynymi wyjątkami od typu `item` na tabelach zrzutów są jajka chowańców oraz Zwoje Użytkowe (`scroll_reset_skills`, `scroll_reset_attributes`, `scroll_reset_full`, `scroll_arena_attempt`) wypadające z bossów instancjonowanych lochów z szansą skalowaną trudnością lochu. Gdy przedmiot/materiał zostanie wylosowany, serwis tworzy fizyczną instancję w bazie (`ItemInstance`), ustala ilość (`stack_size`) oraz rejestruje fakt zdobyczy w `ItemLedger`.

### 1a. Balans Ekonomii Łupów (fix 2026-07-30)
W odpowiedzi na zgłoszenie dotyczące szansy na drop ekwipunku na mapach:
- **Materiały (`reward_type = 'material'`): waga x5 (+400%)** — ułatwia zbieranie surowców pod crafting.
- **Przedmioty ekwipunku (`reward_type = 'item'`): waga 2 (szansa ~0.6%)** — ustawiono bezpośredni drop ekwipunku z potworów na mapach przygód na stałe 0.6% szansy.

### 3. Zabezpieczenia Ekonomiczne
Aby zapobiec dublowaniu łupów z jednej i tej samej walki wskutek problemów z siecią lub ataków typu *Replay*, serwis przed wygenerowaniem zasobów weryfikuje istnienie `idempotency_key` zbudowanego na bazie ID spotkania `encounter:{encounter_id}:drop`. Wszystko przebiega we wspólnej transakcji bazodanowej, z naciskiem na zachowanie pełnej historii ekonomii (Ledgerów) celem łatwiejszego wykrywania exploitów u graczy.

### 4. Potwory rangi `worldboss` NIE mogą mieć własnego drop-u (ograniczenie architektury)
`DropService` uruchamia się wyłącznie po zwycięstwie gracza (`$winner === 'player'`). Starcia ze światowym bossem w `EncounterService` zawsze rozstrzygają się jako `$winner = 'enemy'` (to celowe — world boss to wspólny licznik obrażeń, patrz `docs/modules/world_boss.md`), więc **żaden wpis w `LootTable` przypisanej bezpośrednio do potwora rangi `worldboss` nigdy się nie wylosuje**. Był to realny bug wykryty 2026-07-28: materiał *Fragment Całunu* (potrzebny do craftu *Zbutwiałej Szaty Licza*) wisiał wyłącznie na worldbossie *Licz Cieni* i był w praktyce nieosiągalny.

Naprawa: `database/seeders/MonsterLootSeeder.php` przenosi teraz materiały/przedmioty każdego world bossa na zwykłego, zabijalnego potwora rangi `boss` z tej samej mapy (worldboss zostaje z pustymi listami `materials`/`items`, z komentarzem wyjaśniającym dlaczego). Zastosowano to dla wszystkich 8 world bossów w grze:

> **Aktualizacja (2026-07-29):** worldboss nie dostaje już żadnej `LootTable` w ogóle — pętla seedera dla `$monsterRank === 'worldboss'` czyści ewentualne stare wpisy i ustawia `loot_table_id = null`, zamiast zostawiać martwą tabelę z ogólnymi materiałami mapy (`general`/`boss_general`), które i tak nigdy się nie wylosują. Ten sam pool materiałów trafia automatycznie do bossa lokacji tej samej mapy (patrz tabela niżej), więc żaden łup nie ginie — usuwana jest tylko myląca, nieosiągalna lista widoczna wcześniej w UI mapy.

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

### 5. Walka Grupowa (over-level) - Łup per Potwór (fix 2026-07-30)
W starciach over-level (3-4 potwory naraz, patrz `docs/modules/combat.md`, sekcja
"Rozpoczynanie Walki") `DropService::rollLoot()` losował łup **tylko raz dla całego
starcia** - z tabeli zrzutów jednego, przypadkowego "reprezentanta" grupy, z karą 66%
szansy na całkowity brak dropu. Efekt: pokonanie 3-4 potworów dawało dokładnie tyle samo
łupu (średnio) co pokonanie jednego pojedynczego potwora - kara miała sens tylko przy
założeniu, że i tak nastąpi mnożenie przez liczbę potworów w grupie, a to mnożenie nigdy
nie miało miejsca.

Naprawa: `DropService::rollLoot()` wykrywa walkę grupową (`combat_data.is_overlevel` +
niepusta `combat_data.monsters`) i deleguje do `rollGroupLoot()`, które losuje **osobno
dla każdego z 3-4 pokonanych potworów** z jego WŁASNEJ tabeli zrzutów (`rollForMonster()`)
- każda sztuka ma niezależną karę 66% szansy na brak dropu (ta sama wartość co
poprzednio dla całego starcia, teraz per potwór), po czym wyniki (złoto, gemy, materiały,
przedmioty) sumowane są w jeden `DropResult` dla encountera. Walka 1 na 1 (i world boss)
korzystają z tej samej logiki roll'a (`rollForMonster()`), więc zachowanie pojedynczych
starć się nie zmieniło.
