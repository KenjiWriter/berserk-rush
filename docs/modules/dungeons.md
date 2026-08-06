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

5. **Drop Zwojów Użytkowych (Scrolls):**
   - Z bossów lochów wypadać mogą Zwoje Użytkowe (Zwój Resetu Umiejętności, Zwój Resetu Atrybutów, Zwój Pełnego Resetu oraz Zwój Areny Walki).
   - Szansa (waga) na wylosowanie Zwoju rośnie proporcjonalnie do trudności i wymaganego poziomu lochu (Zapomniane Katakumby: niska szansa -> Otchłań Zniszczenia: najwyższa szansa).

6. **Gwarantowany Drop Skrzyń z Bossów (100% Drop Rate):**
   - Pokonanie ostatecznego bossa w lochu (`boss` stage) gwarantuje w **100% drop skrzyń z łupami** (tych samych skrzyń, które wypadają z bossów map, np. Skrzynia Starych Ruin, Skrzynia Jaskini Trolli itp.).
   - Jedynym losowanym parametrem jest ilość skrzyń: od **1 do 3 sztuk** (`mt_rand(1, 3)`), co zapewnia graczom pewny i satysfakcjonujący loot z każdego pomyślnie ukończonego lochu.

7. **Licznik Kluczy oraz Multi-Dungeony (Mnożniki Wypraw):**
   - Interfejs lochów wyświetla w czasie rzeczywistym liczbę posiadanych przez postać kluczy (z ekwipunku i magazynu materiałów).
   - Gracze z większym zasobem kluczy mogą skorzystać z opcji **Multi-Dungeonów** (`1x`, `3x`, `5x` kluczy na raz).
   - Przejście lochu w trybie Multi zużywa wielokrotność kluczy (np. 3 lub 5 sztuk), zmniejszając czas wymagany do farmedowania.
   - **Skalowanie Trudności (2026-08-06 - zmiana podejścia):** Aby zrównoważyć dużą oszczędność czasu, wyprawy Multi są trudniejsze - ale sposób skalowania różni się w zależności od typu etapu (`DungeonStage::stage_type`):
     - **Etapy "zwykłe" (`single_mob`, `group_mob`) - skalowanie LICZBĄ przeciwników:** zamiast winglować statystyki, gracz walczy z grupą kopii potwora o BAZOWYCH (nieskalowanych) statystykach - "jak w klasycznym lochu jest 1 potwór, tak tu walczysz z grupą naraz". Liczba przeciwników = (projektowana liczba ze `stage->monster_count`, domyślnie 1 dla `single_mob`) * mnożnik kluczy (`1x` = bez zmian, `3x` = x3 przeciwników, `5x` = x5 przeciwników). Implementacja re-używa istniejącą pętlę walki grupowej (ta sama, która obsługuje projektowo zdefiniowane `group_mob` etapy) - potwory w grupie NIE rzucają własnych skilli/DoT-ów na gracza (tak samo jak przy walkach grupowych over-level na mapach, zasada "AOE/grupa bez DoT", patrz `docs/modules/combat.md` pkt 7b).
     - **Boss/Miniboss/Gate - skalowanie STATYSTYKAMI (bez zmian):** pojedynek pozostaje 1v1, ale przeciwnik jest silniejszy (`diffMultiplier` na HP/ATK/DEF): `1x` = 100%, `3x` = 135%, `5x` = 170%.
   - **Skalowanie Nagród:** Wygrana w wyprawie Multi mnoży przyznawane Złoto, Doświadczenie (XP), liczbę gwarantowanych Skrzyń z Bossa (np. 5x = od 5 do 15 skrzyń) oraz ilość przedmiotów w tabeli dropów przez wybrany mnożnik (`key_multiplier`) - bez zmian, ten mnożnik nagród nie zależy od tego, czy trudność etapu skaluje się liczbą przeciwników czy statystykami.
   - **UI (`dungeon-run.blade.php`):** pasek Życia przeciwnika oraz kafelek "MAX HP" muszą liczyć maksimum tą samą formułą co `DungeonService::simulateStage()` (suma bazowego HP * liczba przeciwników dla etapów "zwykłych", albo HP * `diffMultiplier` dla boss/miniboss/gate) - inaczej licznik pokazuje aktualne (poprawnie przeskalowane z backendu) HP na tle złego maksimum. Etapy z grupą >1 przeciwnika pokazują odznakę `xN` przy nazwie potwora.

## Baza Danych

- `dungeons`: Tablica główna opisująca dany loch (nazwa, minimalny level, przedmiot-klucz).
- `dungeon_stages`: Rekordy reprezentujące kolejne "piętra". Powiązane z modelem `Monster` (jaki potwór tam stoi).
- `character_dungeon_runs`: Model śledzący aktualne zmagania konkretnego gracza. Zawiera informacje o obecnym etapie (`current_stage`), używanym mnożniku wyprawy (`key_multiplier`), zdrowiu gracza (`current_hp`), zapisy logów bitewnych (`combat_data`) z zadania w tle, a także zakolejkowane łupy (`accumulated_loot`).

## Logika Aplikacji
Logika instancjonowanych lochów jest sterowana głównie z poziomu **`DungeonService`**, do którego oddelegowano metody startowania ekspedycji (z walidacją i zużyciem odpowiedniej liczby kluczy), kalkulacji potencjalnego lootu (z uwzględnieniem mnożnika wyprawy) i ostatecznego jego dystrybuowania na koniec. Uzupełniane jest to asynchronicznym jobem **`SimulateDungeonStageJob`** zapewniającym generowanie wyników.

