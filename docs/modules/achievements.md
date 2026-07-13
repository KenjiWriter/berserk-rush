# Achievements & Collections System

Ten moduł obejmuje zaawansowany system Kolekcji, Osiągnięć, Bestiariusza oraz Tytułów. Został stworzony w celu zapewnienia graczom długoterminowych celów (endgame retention) i zachęcania do eksploracji różnych systemów w grze.

## Architektura

System składa się z kilku powiązanych ze sobą logik zbierających zdarzenia z gry i przydzielających nagrody.

### 1. Wydarzenia (Events) i Nasłuchiwanie (Listeners)
Mechanika opiera się w całości na asynchronicznych eventach:
- Kiedy gracz zabija potwora (`EncounterService`), system emituje event `MonsterDefeated`.
- Listener `MonsterDefeatedListener` wywołuje kod w `CollectionService`, który m.in. inkrementuje liczbę zabić w `CharacterBestiary`.
- Następnie wysyłany jest uniwersalny event `AchievementProgressed` z typem m.in. `monsters_killed`.
- Listener `AchievementProgressedListener` wywołuje kod w `AchievementService`, który sprawdza czy event z podanym typem i kontekstem odpowiada wymogom aktywnego osiągnięcia.

### 2. Tablice w Bazie Danych
*   `achievements`: Słownik osiągnięć utworzonych przez administratorów. Posiada kluczowe pole `conditions` (JSON), gdzie ustala się filtry, np. by osiągnięcie dotyczyło jedynie potwora o rangu "boss".
*   `character_achievements`: Zapisy postępów graczy w poszczególnych osiągnięciach, powiązania many-to-many między `characters` a `achievements`. Zawiera `progress`, `completed_at` oraz `rewarded`.
*   `titles`: Lista wszystkich tytułów możliwych do zdobycia, dających pasywne bonusy i unikalny przedrostek na czacie.
*   `character_titles`: Przypisanie tytułów (wraz ze statusem ich używania, np. `is_active`) do postaci.

## Główne Funkcje

### Księga Osiągnięć (Achievement Book)
- Tablica wyzwań współdzieli widok Osiągnięć (Livewire Component: `Quests`).
- Osiągnięcia mogą mieć ustawiony "Parent ID", co pozwala tworzyć łańcuchy lub stopnie trudności, gdzie dany "Tier II" ukaże się graczowi dopiero po zaliczeniu "Tier I".
- Za ukończenie osiągnięcia gracz może otrzymać: Punkty Osiągnięć (Achievement Points), Złoto, Exp, unikalny Tytuł, czy fizyczny Przedmiot do Ekwipunku, a także statystyki pasywne dla postaci.

### Tytuły i Reprezentacja
- Gracz po zdobyciu tytułu może wejść w profil i wybrać jeden z nich.
- Wybrany tytuł jest przypisany do niego na stałe (aż go nie zmieni) i wyświetlany w profilu oraz na Globalnym Czacie.
- Poszczególne tytuły mogą nieść ze sobą unikalne, pasywne bonusy przypisywane do statystyk bazowych (`StatsCache`).

### Zbieractwo Kontekstowe i Enumy
Do perfekcyjnej synchronizacji stworzyliśmy Enumy dla systemu walki:
- `MonsterType` (np. Zwierzę, Goblin, Nieumarły).
- `MonsterRank` (np. Zwykły, Boss, Worldboss).
Przy każdym evencie (np. zabicie wroga), do `AchievementService` przekazywany jest kontekst, dzięki czemu osiągnięcie z warunkiem `{"monster_type": "undead"}` zaktualizuje pasek postępu TYLKO w momencie ubicia nieumarłego.

## Zarządzanie z Poziomu Admina
Cały system zintegrowany jest w jednym GUI Administracyjnym (Livewire: `Admin\Achievements`), gdzie bez pisania kodu Game Master może stworzyć osiągnięcia, ustalić listę ich warunków i przypisać Tytuł jako nagrodę za pomyślne ukończenie danego zadania.
