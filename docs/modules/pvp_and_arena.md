# Player vs Player (PvP) & Guild Wars (GvG)

Moduł ten zapewnia w grze asynchroniczną i synchroniczną bezpośrednią rywalizację między graczami.

## Architektura i Koncepcje

### Snapshoty (Widma Graczy)
W walkach z innymi graczami wprowadzono mechanizm snapshotów. Dzięki temu można wyzwać do walki kogoś, kto jest obecnie offline, lub kto właśnie zmienił ekwipunek podczas trwania potyczki.
- Atakujący wyzywając rywala zapisuje bieżące "Widmo" (statystyki i ekwipunek) w encji spotkania,
- Tak samo zapisywane są aktualne statystyki obrońcy, by walka przebiegła na równych i stałych warunkach.

### System ELO i Ligi
Arena posiada własny system matchmakingu i rangowania:
- Wygrana powoduje kradzież pewnej puli ELO na rzecz Atakującego,
- Gracze rywalizują o jak najwyższe miejsca w Lidze, co generuje stałą rotację rankingową,
- Na podstawie ELO wyznaczana jest aktualna ranga gracza (Brąz, Srebro, Złoto, Platyna).

### Żetony Areny i Sklep Gladiatora
PvP służy nie tylko celom rywalizacyjnym, ale jest też poboczną gałęzią zyskiwania dóbr:
- Waluta `Arena Tokens` wypada z wygranych potyczek (w tym też mniejsze ilości pocieszenia z przegranych),
- Można nimi płacić u nowego NPC: Gladiatora, dostępnego bezpośrednio z widoku Areny (lub u zarządzanego z panelu admina sklepu).

### Wojny Gildii
Rozbudowano system zrzeszania się do walk grupowych:
- Gildia poprzez panel może rzucić bezpośrednie wyzwanie liderowi wrogiej gildii (system zaproszeń mailowych).
- Kiedy lider zaakceptuje, algorytm `GuildWarService` łączy parami członków gildii (najsilniejszy vs najsilniejszy) i w tle symuluje ich walki.
- Gildia z wyższym procentem wygranych "gier" wygrywa potyczkę. 
- Członkowie wygranej ekipy, którzy brali udział (top 5 graczy) otrzymują dodatkowe żetony areny w nagrodę.

## Realizacja Techniczna
- `PvPEncounterService` / `GuildWarService`: Główne klasy realizujące symulację mechanik i rzucania kośćmi.
- Baza Danych (Migrations): Nowe tabele `pvp_encounters`, `guild_wars`, `guild_war_fights`. 
- Logi Walki: Skrypt JS `arena-combat.blade.php` odtwarza asynchronicznie wygenerowane po stronie serwera JSON-owe tury w klasycznym widoku walki.
