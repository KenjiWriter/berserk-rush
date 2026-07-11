# Dungeony (Lochy)

Moduł lochów wprowadza do gry zaawansowaną zawartość PvE typu instancjonowanego. Pozwala graczom na wejście do sekwencyjnie ułożonych wyzwań (etapów), które muszą być pokonane jedno po drugim. 

## Główne założenia mechaniki

1. **Wymagania i Klucze:**
   - Loch może posiadać minimalny próg poziomu doświadczenia (`min_level`).
   - By rozpocząć ekspedycję, gracze muszą często posiadać specyficzny klucz. Kluczem jest przedmiot w ekwipunku określony przez `entry_item_template_id`. Zużywa się on bezpowrotnie podczas inicjalizacji wejścia do lochu.

2. **Struktura i Etapy:**
   - Każdy loch zbudowany jest z określonej liczby etapów (`DungeonStage`).
   - W ramach jednego etapu na gracza czeka potwór o ściśle określonych statystykach, co pozwala na precyzyjne projektowanie poziomu trudności (w przeciwieństwie do losowych walk na mapach przygód).

3. **Walka i Symulacja w Tle:**
   - Walka z potworem na danym etapie opiera się na kolejkach Laravel (Jobs: np. `SimulateDungeonStageJob`).
   - Dzięki temu długie walki i obciążające kalkulacje odbywają się w tle, a asynchroniczne wyliczanie postępu gwarantuje niezacinanie się serwera.
   - Stan starcia jest zapisywany w `CharacterDungeonRun->combat_state`. Po wyliczeniu logów (tur walki), na frontendzie Livewire odtwarza graczowi animacje ciosów "krok po kroku".

4. **System Kumulacji Łupów (Accumulated Loot):**
   - W odróżnieniu od eksploracji na normalnej mapie, nagrody za poszczególne piętra lochu *nie trafiają bezpośrednio* do gracza.
   - Złoto, Doświadczenie i Przedmioty są składowane (kumulowane) w polu `accumulated_loot` dla danego podejścia (`CharacterDungeonRun`).
   - Dopiero po pomyślnym oczyszczeniu **ostatniego etapu** (zabiciu ostatecznego bossa lochu), cały zmagazynowany zysk zostaje automatycznie rozpakowany i dodany do walut oraz ekwipunku postaci (zostają stworzone odpowiednie logi w `CurrencyLedger` i `ItemLedger`).
   - Śmierć postaci lub nieudane przejście lochu skutkuje utratą wszystkich dotychczas zebranych w tym lochu nagród. Wnosi to element ryzyka.

## Baza Danych

- `dungeons`: Tablica główna opisująca dany loch (nazwa, minimalny level, przedmiot-klucz).
- `dungeon_stages`: Rekordy reprezentujące kolejne "piętra". Powiązane z modelem `Monster` (jaki potwór tam stoi).
- `character_dungeon_runs`: Model śledzący aktualne zmagania konkretnego gracza. Zawiera informacje o obecnym etapie (`current_stage`), zdrowiu gracza (`current_hp`), zapisy logów bitewnych (`combat_data`) z zadania w tle, a także zakolejkowane łupy (`accumulated_loot`).

## Logika Aplikacji
Logika instancjonowanych lochów jest sterowana głównie z poziomu **`DungeonService`**, do którego oddelegowano metody startowania ekspedycji, kalkulacji potencjalnego lootu (z mnożnikami) i ostatecznego jego dystrybuowania na koniec. Uzupełniane jest to asynchronicznym jobem **`SimulateDungeonStageJob`** zapewniającym generowanie wyników.
