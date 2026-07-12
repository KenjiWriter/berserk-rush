# System Questów (Tablica Wyzwań)

System questów to jedna z fundamentalnych mechanik progresji, obok zabijania potworów i rozbudowy rzemiosła. Gracz ma możliwość podjęcia wyzwań w Miastowym Hubie (Tablica Wyzwań), które urozmaicają rozgrywkę i oferują cenne nagrody.

## Rodzaje Misji

Obecny system obsługuje trzy podstawowe typy misji:

1. **Hunting (Polowanie)**
   Zadaniem gracza jest pokonanie określonej liczby przeciwników. 
   - Cel może być konkretnym gatunkiem potwora (np. `target_id = 1` -> Zabij 10 Wilków).
   - Cel może dotyczyć ogólnie potworów na wybranej mapie (np. `target_id = 2` -> Zabij 50 potworów w Mrocznym Lesie).

2. **Gathering (Zbieractwo)**
   Zadaniem gracza jest zgromadzenie odpowiedniej ilości przedmiotów i oddanie ich.
   - Gracz udaje się na tereny, na których z odpowiednich potworów wypada potrzebny łup (drop table questowych itemów aktywuje się tylko podczas trwania misji).
   - Oddając zadanie, przedmioty zostają automatycznie usunięte z ekwipunku gracza.

3. **Action (Czynność) - w planach**
   Wymaga wykonania interakcji z konkretnym panelem w grze (np. ulepszenie przedmiotu u kowala na +5). Obecnie moduł ten jest jeszcze w fazie projektowej i wymaga sprzężenia systemu questów z eventami domenowymi pozostałych systemów.

## Cykl Życia Questu (State Machine)

1. **Dostępne (Available)**
   Questy są przypisywane do gracza, gdy ten wbiję odpowiedni poziom (warunek `required_level`). Mogą mieć również `max_level`, po przekroczeniu którego misja nie może zostać już zaakceptowana.

2. **Aktywne (Active)**
   Gracz musi ręcznie przyjść do Tablicy Wyzwań i zaakceptować zadanie (`acceptQuest`). Wtedy w bazie danych w tabeli `character_quests` powstaje wpis o statusie `active`.

3. **Ukończone, gotowe do odbioru (Completed)**
   Gdy licznik progresu misji (np. ilość zabitych potworów) osiągnie `target_amount`, status w `character_quests` zmienia się z `active` na `completed`. Od tego momentu zadanie oczekuje na "oddanie".

4. **Odebrane (Rewarded)**
   Gdy gracz wróci do Tablicy Wyzwań i kliknie przycisk odbioru nagrody, zasoby (Złoto, Doświadczenie) są transferowane na postać. Następnie status misji zostaje zmieniony na ostateczny – `rewarded`. Rekord misji pozostaje w historii gracza, blokując ponowne wzięcie tego samego zadania.

## Architektura i Baza Danych

- **Tabela `quests`:** Tabela słownikowa/konfiguracyjna. Zawiera wszystkie definicje misji, poziom trudności, wymagania, typ oraz wielkości nagród (EXP, Gold). Modelem Eloquent zarządzającym misjami z poziomu kodu bazowego jest `Quest`. Tworzone są w panelu administracyjnym.
- **Tabela `character_quests`:** Tabela obrotowa (Pivot) trzymająca progres wyzwania gracza (`progress` INT), oraz obecny stan wyzwania (`status` ENUM).
- **Service Class:** `App\Application\Quests\QuestService` odpowiada za weryfikację logiki biznesowej – sprawdzanie możliwości rozpoczęcia i odebrania zadania, a także manipulację procesem pobierania przedmiotów z ekwipunku dla misji typu Gathering.

## Nasłuchiwanie Postępów
Aktualizacja statusów działa asynchronicznie i bezinwazyjnie. Wykorzystano architekturę opartą na Event Sourcing / Event Listeners. Na przykład w momencie zakończenia walki z potworem rzucany jest event `EncounterFinished`, który nasłuchiwany jest przez Listenery należące do modułu Quests. To one analizują, co zostało zabite i w razie konieczności odpowiednio inkrementują wartość `progress` u gracza.
