# Player vs Player (PvP) & Guild Wars (GvG)

Moduł ten zapewnia w grze asynchroniczną i synchroniczną bezpośrednią rywalizację między graczami.

## Architektura i Koncepcje

### Snapshoty (Widma Graczy)
W walkach z innymi graczami wprowadzono mechanizm snapshotów. Dzięki temu można wyzwać do walki kogoś, kto jest obecnie offline, lub kto właśnie zmienił ekwipunek podczas trwania potyczki.
- Atakujący wyzywając rywala zapisuje bieżące "Widmo" (statystyki i ekwipunek) w encji spotkania,
- Tak samo zapisywane są aktualne statystyki obrońcy, by walka przebiegła na równych i stałych warunkach.

**Zestaw obronny Areny:** snapshot obrońcy (`$defender->createSnapshot('pvp')`
w `PvPEncounterService::startEncounter()`) liczy się z dedykowanego zestawu
"Arena PvP" (patrz `docs/modules/profile_and_equipment.md`, sekcja "Zestawy
Ekwipunku"), a nie z aktualnie fizycznie założonego ekwipunku - z fallbackiem
per-slot na aktualny gear, gdy dany slot zestawu nie jest skonfigurowany.
Atakujący zawsze walczy tym, co ma aktualnie na sobie w momencie ataku
(`$attacker->createSnapshot()` bez argumentu).

### System ELO, Ligi i Pojemność Prób Areny
Arena posiada własny system matchmakingu, rangowania oraz limitowania pojedynków:
- **Wymóg poziomu (Level 15):** Arena Walk, wyzywanie graczy na pojedynki PvP, Sklep Gladiatora, losowanie przeciwników (Matchmaking) oraz ranking "Arena Chwały" są zablokowane dla postaci poniżej 15 poziomu doświadczenia,
- **Pojemność prób Areny:** Gracze mogą gromadzić maksymalnie **3 próby** walk na Arenie jednocześnie (`MAX_DAILY_PVP_FIGHTS = 3`),
- **Regeneracja +1 próba na 1h:** Nowa próba walki regeneruje się automatycznie co 1 godzinę (do limitu 3/3). Użycie **Zwoju Areny Walki** w ekwipunku natychmiastowo przywraca +1 próbę (do zdobycia w Sklepie Premium lub z bossów lochów),
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

### Podgląd Ekwipunku Areny (UI)
Na widoku Areny (`Livewire\City\Arena`), zarówno w zakładce "Dostępni Przeciwnicy",
jak i w "Rankingu Chwały", najechanie (desktop) lub kliknięcie (mobile) na
awatar gracza pokazuje panel z jego 6 slotami ekwipunku:
- Dane pochodzą z `Character::getEquipmentSlotsFor('pvp')` - publicznego wrappera
  na `resolveEffectiveEquipment('pvp')`, czyli **dokładnie tego samego** zestawu
  "Arena PvP" (z fallbackiem per-slot na aktualnie założony gear), którego użyje
  `PvPEncounterService::startEncounter()` przy liczeniu Widma obrońcy - podgląd w
  UI jest więc wiarygodny względem realnej walki.
- Komponent Blade: `x-arena-equipment-preview` (props: `slots` - mapa
  slot => `ItemInstance|null`), każdy slot ma własny zagnieżdżony `x-item-tooltip`
  ze szczegółowymi statystykami po najechaniu.
- Panel jest teleportowany do `<body>` (Alpine `x-teleport` + globalny helper
  `smartTooltip()` z `resources/js/app.js`), by uniknąć przycięcia przez
  `overflow-hidden`/`overflow-x-auto` na kartach przeciwników i w tabeli rankingu.

## Realizacja Techniczna
- `PvPEncounterService` / `GuildWarService`: Główne klasy realizujące symulację mechanik i rzucania kośćmi.
- Zabezpieczenie Kolejkowania: `PvPEncounterService` oraz `EncounterService` weryfikują stan postaci przed rozpoczęciem pojedynku (blokada symultanicznych walk w trakcie trwania innej potyczki oraz 5-sekundowy cooldown między wyzwaniami Areny).
- Baza Danych (Migrations): Tabele `pvp_encounters`, `guild_wars`, `guild_war_fights`. 
- Logi Walki: Skrypt JS `arena-combat.blade.php` odtwarza wygenerowane po stronie serwera JSON-owe tury w klasycznym widoku walki.
