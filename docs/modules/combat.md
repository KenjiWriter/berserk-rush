# Moduł Walki (Combat)

Moduł obsługuje asynchroniczną, turową walkę (typu Idle) odbywającą się pomiędzy postacią gracza a potworami na poszczególnych mapach. 

## Implementacja
- Pliki logiki: `app/Application/Combat/EncounterService.php`
- Modele: `Encounter`, `Map`, `Monster`

## Mechaniki

### 1. Rozpoczynanie Walki (`start`)
Walka inicjowana jest na konkretnej `Mapie`. W momencie rozpoczęcia walki:
- Gra losuje potwora (`Monster`) z puli przeciwników dostępnych na wybranej mapie.
- Obliczana jest **inicjatywa**. Na podstawie atrybutu **AGI (Zręczność)** postaci gracza i potwora wyłaniana jest strona, która jako pierwsza wykona ruch (`player_first`).
- Utworzona zostaje encja walki (`Encounter`) w stanie `ongoing`, w której przechowywany jest stan pojedynku przed jego symulacją.

### 2. Symulacja Walki (`simulate`)
Serwis w ułamku sekundy symuluje całe starcie, maksymalnie do 50 tur, wymieniając na przemian uderzenia między postacią gracza a potworem, aż HP jednej ze stron spadnie do 0.

Wewnątrz tury występują 3 stany ataku:
- **Trafienie (`hit`):** Standardowy atak zadający obrażenia.
- **Trafienie Krytyczne (`crit`):** Obrażenia x 1.5. Szansa obliczana jest na bazie własnego atrybutu `AGI` (0.15% per pkt u gracza) oraz ekwipunku i zaklęć (bez redukcji ze strony AGI przeciwnika). Szansa na krytyk jest twardo limitowana (hard cap) do maksymalnie **100%** (gracz) oraz 30% (potwór).
- **Unik / Chybienie (`miss`):** Szansa na całkowity brak obrażeń z racji własnego atrybutu `AGI` obrońcy (0.06% per pkt) oraz obrony z ekwipunku (`dodge_chance`), bez redukcji ze strony AGI atakującego, z twardym sufitem (hard cap) wynoszącym maksymalnie **30%**.

**Kalkulacja Obrażeń i Skalowanie Statystyk Potworów:**
- **Progresywne Skalowanie Potworów (`getScaledStats`):** Statystyki bazowe potworów (`hp`, `atk`, `def`, `agi`) skalują się z poziomem gracza, jeśli poziom gracza jest wyższy niż poziom bazowy potwora. Za każdy poziom różnicy statystyki potwora wzrastają o 10% (`multiplier = 1 + (player_level - monster_level) * 0.10`).
- Obrażenia zadawane przez gracza to suma: `10 + BonusAtrybutówBroni + Poziom + AtakEkwipunku`. Zmniejszane są one następnie o `Obrona * 0.2` przeciwnika (1 pkt obrony = 0,2 dmg redukcji).
  - **Przeliczniki atrybutów broni (`getAttributeAttackBonus`):**
    - **Łuk (`bow`)**: `STR + AGI`
    - **Dzwon (`bell`)**: `STR + INT`
    - **Różdżka (`wand`)**: `INT * 2`
    - **Miecz (`sword`)**: `STR + AGI`
    - **Topór (`axe`)**: `STR * 2`
    - **Sztylet (`dagger`)**: `STR + AGI`
    - **Domyślnie / Pięści (`barehands`)**: `STR * 2`
- HP u gracza zależy głównie od `VIT`: `100 + (VIT * 15) + (Poziom * 5) + hp_bonus`. HP u potworów skaluje się z ich poziomem oraz poziomem gracza.
- Mana (MP) u gracza zależy od `INT`: `50 + (INT * 10) + (Poziom * 3) + mana_bonus`. Postać rozpoczyna każdą nową walkę z pełnym stanem MP (`playerMana = playerMaxMana`). Umiejętności aktywne pobierają koszt many przy wywołaniu, natomiast umiejętności pasywne pobierają koszt many co turę/wywołanie (dopóki postać posiada wystarczająco MP, by utrzymać pasyw). W trakcie trwania pojedynczego starcia mana nie regeneruje się co turę, natomiast po rozpoczęciu nowej walki stan many natychmiast wraca do 100%. Wyniki stanu many są przekazywane w każdym kroku tury (`playerMana`, `playerMaxMana`) i animowane w interfejsie walki.

> **Uwaga (itemizacja klasowa, 2026-07-28):** Bronie nadal nie przydzielają bonusów do
> surowych atrybutów (STR/INT/VIT/AGI) - dają wyłącznie obrażenia fizyczne
> (`attack_min`/`attack_max`), obrażenia magiczne (`magic_attack_min`/`max`), obronę
> (`defense`), HP (`hp_bonus`) i szansę na trafienie krytyczne (`crit_chance`).
> `getAttributeAttackBonus` liczy bonus ataku z atrybutów postaci (STR/INT/AGI), a te
> są zasilane przez: ręcznie rozdane punkty, biżuterię (sporadyczny płaski +1..+5) ORAZ
> od dziś także **zestawy zbroi klasowej** (hełm/klatka/buty tematyczne per Wojownik
> `_w`→STR+VIT / Mag `_m`→INT / Skrytobójca `_a`→AGI, skalujące się liniowo z
> poziomem przedmiotu) - to bezpośrednio wpływa na `getAttributeAttackBonus` (np.
> pełny zestaw Maga podbija `INT`, więc mocniej "Różdżka: INT * 2") oraz na inicjatywę/
> unik/krytyk liczone z `AGI` (zestaw Skrytobójcy). Pełne wyjaśnienie balansu (budżet
> punktów, tabela wartości per poziom) - patrz `docs/modules/profile_and_equipment.md`,
> sekcja 4.
>
> **Specjalizacje Klas Broni (Unikalne Mechaniki Bojowe, 2026-08-03):** Każda z 6 typów broni w grze posiada własną, unikalną mechanikę bojową rozliczaną symetrycznie we wszystkich silnikach walki (`EncounterService`, `PvPEncounterService`, `GuildWarService`, `DungeonService`):
> 1. **Dzwon (`bell`) - Rozbłysk Magii:** Broń hybrydowa zadająca atak fizyczny oraz mająca szansę (`magic_burst_chance`, %) na dołożenie osobnych, dodatkowych obrażeń magicznych (`magic_burst_min`-`magic_burst_max`).
> 2. **Topór (`axe`) - Krwawienie:** Szansa (`bleed_chance`, %) na wywołanie krwawienia u celu zadającego obrażenia co turę w oparciu o **% z CURRENT HP (aktualnego HP celu)**.
> 3. **Sztylety (`dagger`) - Otrucie:** Szansa (`poison_chance`, %) na wywołanie otrucia celu zadającego obrażenia co turę w oparciu o **% z MAX HP (maksymalnego HP celu)**.
> 4. **Miecz (`sword`) - Podwójny Cios:** Szansa (`double_strike_chance`, %) na natychmiastowe wyprowadzenie drugiego ataku w tej samej turze o mocy 50% obrażeń głównego ciosu.
> 5. **Łuk (`bow`) - Przebicie Pancerza:** Procentowe przebicie pancerza (`armor_pen_pct`, %) redukujące obronę przeciwnika (`defense`) przed wyliczeniem obrażeń ciosu.
> 6. **Różdżka / Laska (`wand`) - Infuzja Magiczna:** Szansa (`magic_infusion_chance`, %) przy trafieniu na nasycenie ciosu **losowym efektem specjalnym** (Krwawienie, Otrucie, Podwójny Cios lub 50% Przebicie Pancerza).

### 3. Wynik Walki i Nagrody
Na sam koniec symulacji:
- Ustalany jest zwycięzca.
- Jeśli wygrywa gracz, losowane są nagrody – złoto oraz doświadczenie w oparciu o poziom potwora (nagrody skalują się z modyfikatorami bazującymi na różnicy poziomów między graczem a potworem).
- Spotkanie (`Encounter`) oznaczane jest jako wygrane lub przegrane. Uruchamiany jest serwis zrzutów z potwora (`DropService`).
- Pełny log (przebieg wszystkich tur, zadane obrażenia, wyniki losowań RNG) kompresowany jest do formatu JSON i zapisany do bazy danych, by móc zostać odtworzony w UI w formie graficznej walki turowej.

### 4. Statystyki Sesji (`Session Tracker`)
Podczas przebywania na mapie komponent `MapStub` śledzi statystyki pojedynczej sesji:
- **Pokonani potwory (`sessionMonstersDefeated`):** Całkowita liczba wygranych walk w ramach sesji.
- **Czas sesji:** Czas przebywania na mapie mierzony od momentu wejścia.
- **Złoto / min (`sessionGoldEarned` & `gold/min`):** Złoto zgromadzone w trakcie sesji przeliczane dynamicznie w czasie rzeczywistym na wskaźnik złota uzyskiwanego na minutę `(sessionGoldEarned / elapsed_seconds) * 60`, pozwalający porównać opłacalność farmowania na różnych mapach.

### 5. Auto-Chain (Automatyczne Powtarzanie Walk) i Kara za Przegraną
Gdy `autoChain` jest włączony (`MapStub::completeBattle`), po zakończeniu walki losowany jest kolejny przeciwnik na mapie i walka startuje automatycznie:
- **Wygrana:** kolejna walka startuje po ok. 700 ms (szybki chain).
- **Przegrana (2026-07-29):** zamiast zatrzymywać automat (co wcześniej wymagało ręcznego kliknięcia "Kolejna Walka"), system czeka **3000 ms jako karę** za przegraną, po czym normalnie losuje nowego przeciwnika i wznawia walkę. Dzięki temu postać można bezpiecznie zostawić na farmie AFK bez ryzyka, że automat "utknie" na ekranie klęski.
- Automat zatrzymuje się na stałe tylko, gdy postać zdobędzie poziom (`levelUps`) - wymaga to ręcznej reakcji gracza (przydział punktów atrybutów).
- Zdarzenie `auto-chain-next-battle` niesie parametr `delay` (ms) konsumowany po stronie JS (`resources/views/livewire/adventure/map-stub.blade.php`), sterujący czasem oczekiwania przed wywołaniem `startBattle()`.
- **Działanie w Tle i Odporność na Throttling (2026-08-04):** Odliczanie czasu przerwy między turami i walkami w JS opiera się na natywnym **Web Worker Timer Blob**, dzięki czemu timery nie są spowalniane przez przeglądarkę po przełączeniu karty lub zminimalizowaniu aplikacji na smartfonie. Dodatkowo, w nieaktywnej karcie (`document.hidden`) system pomija klatkowanie animacji UI i błyskawicznie kończy walkę (`finishAllTurns()`), eliminując opóźnienia i zacięcia w trybie AFK.

### 5b. Prędkość Odtwarzania Walki (Speed x1, x2, x5)
Gracz może dostosować szybkość animacji starć w interfejsie walki:
- **x1**: Standardowe tempo animacji (550 ms przerwy między turami).
- **x2**: Podwójne tempo (200 ms przerwy).
- **x5 (2026-08-04):** Pięciokrotne przyspieszenie (60 ms przerwy). Opcja x5 odblokowuje się automatycznie od **30 poziomu postaci** lub natychmiast dla kont z aktywnym **statusem VIP** (`hasPremium()`). Wcześniej przycisk pozostaje zablokowany z oznaczeniem "Lvl 30+ / VIP".

### 6. Rasy Potworów (`MonsterType`)
Każdy potwór (`Monster::type`) ma przypisaną jedną z **6 głównych ras** (rework ras,
2026-07-29 - wcześniej istniało 13 drobnych typów, z których większość nie miała
żadnego odpowiednika w systemie zaklęć i była czystym rozdrobnieniem):
- **Nieumarły (`undead`)**, **Demon (`demon`)**, **Zwierzę (`animal`)**, **Ork (`orc`)**,
  **Troll (`troll`)** - rasy z dedykowanym bonusem `strong_vs_*`/`resist_*` w puli
  zaklęć Czarodzieja (patrz `docs/modules/wizard.md`).
- **Mistyczny (`mystical`)** - nowa, zbiorcza rasa łącząca wszystko, co wcześniej było
  rozdzielone na rośliny, gobliny, ogry, golemy, uogólnione "potwory", ludzi, smoki i
  żywiołaki. Nie ma (na razie) własnego bonusu `strong_vs_mystical`/`resist_mystical`.

Rasa wpływa wyłącznie na bonusy `strong_vs_<rasa>` (broń) i `resist_<rasa>` (pancerz)
wylosowane w zaklęciach - patrz `Character::calculateDamage()` /
`calculateMonsterDamage()`, które dopasowują `monster->type` do klucza bonusu na
ekwipunku gracza.

### 7. Otrucie i Ogłuszenie z Ekwipunku (Procki)
Niezależnie od umiejętności bojowych (skille `poison`/`stun`/`freeze` - patrz
`docs/modules/skills.md`), sam ekwipunek może dawać **pasywną szansę na dołożenie tego
samego typu efektu przy każdym wylądowanym trafieniu** (nie tylko przy użyciu skilla),
dzięki nowym afiksom zaklęć `poison_chance`/`stun_chance` (bronie, 1-7%) oraz
`resist_poison`/`resist_stun` (pancerze, 1-7% - patrz `docs/modules/wizard.md`).

- **Szansa efektywna:** `max(0, SzansaAtakującego - OdpornośćCelu)`. W PvE potwory nie
  mają odporności (traktowana jako 0) - w PvP Arenie i Wojnie Gildii obie strony mogą
  redukować szansę przeciwnika swoim pancerzem.
- **Otrucie z procka:** 3 tury, 3% aktualnego HP celu na turę - słabsze niż najsłabszy
  skill otrucia (który zaczyna się od ok. 2-5%), bo to darmowy, pasywny efekt, a nie
  świadomie odpalona umiejętność. Współdzieli tę samą strukturę danych co DoT ze skilli
  (`activeDots`/`effects[type=poison]`), więc UI walki renderuje go identycznie.
- **Ogłuszenie z procka:** 1 tura (tak jak wszystkie skille `stun`/`freeze`) - ofiara
  traci turę ataku. Współdzieli mechanizm `cc_applied`/`cc_turns` ze skillami.
- **Gdzie działa:** `EncounterService` (PvE, w tym starcia grupowe over-level - AOE bije
  tylko szansą na ogłuszenie, bez otrucia, zgodnie z zasadą "AOE bez DoT"),
  `PvPEncounterService` (Arena 1v1, obie strony), `GuildWarService` (Wojna Gildii 5v5,
  obie strony - przy tej okazji dodano tam też samą **infrastrukturę ogłuszenia**
  (`cc_turns` per uczestnik), której wcześniej brakowało w starciach 5v5).

### 8. "Silny Przeciwko Bohaterom" (`strong_vs_hero`)
Nowy afiks broni (5-20%, ten sam zakres co `strong_vs_<rasa>` - patrz
`docs/modules/wizard.md`) działający
**wyłącznie w PvP Arenie i Wojnie Gildii** - w przeciwieństwie do `strong_vs_<rasa>`,
który dotyczy tylko potworów w PvE, ten bonus dolicza się bezwarunkowo w starciach
gracz-vs-gracz, bo tam przeciwnik zawsze jest "bohaterem" (inną postacią gracza).
Liczony analogicznie do `strong_vs_<rasa>` - jako % dokładany do obrażeń już po
redukcji obrony przeciwnika.

### 9. Zabezpieczenie Anti-Cheat (Multi-Tab & Rate Limit)
W celu uniemożliwienia podwojonego lub potrojonego zdobywania doświadczenia i złota poprzez otwieranie przygody na tej samej postaci w 2 lub więcej kartach przeglądarki, system stosuje dwupoziomowe zabezpieczenie:
1. **Frontend Session Lock (`MapStub`)**: Każdy zamontowany komponent `MapStub` generuje unikalny token sesji karty i rejestruje go w pamięci Cache (`adventure_active_tab:{character_id}`). W przypadku otwarcia nowej karty lub przełączenia, aktywna staje się tylko ostatnia karta. Nieaktywne karty wstrzymują automatyczne walki i wyświetlają banner z opcją przejęcia aktywnego statusu.
2. **Backend Rate Limit (`EncounterService::start`)**: Serwis waliduje minimalny czas od rozpoczęcia ostatniej walki danej postaci (1300 ms) oraz nakłada blokadę transakcyjną `lockForUpdate()` na model postaci, odrzucając wszelkie próby symultanicznych żądań walki z błędem `COMBAT_IN_PROGRESS`.

### 10. Nagrody i Postęp za Walkę Grupową (fix 2026-07-30)
Zgłoszony bug: po wygraniu walki grupowej (over-level, 3-4 potworów naraz - patrz
sekcja 6/pkt "Rasy Potworów" wyżej i `Map::isOverLevel()`) gracz dostawał złoto, exp,
postęp Bestiariusza/Osiągnięć oraz łup **tak, jakby pokonał tylko jednego, pojedynczego
potwora** - `EncounterService::simulate()` liczył nagrodę tylko dla jednego
"reprezentanta" grupy (`$monster` wylosowany w `start()`), zamiast sumować nagrodę za
każdą z 3-4 faktycznie pokonanych sztuk. Kara -66% (`0.33x`) za over-level była więc
stosowana do nagrody za JEDNEGO potwora, a nie do sumy nagród za całą grupę - efektywnie
walka grupowa opłacała się kilkukrotnie gorzej niż zwykła walka 1 na 1, mimo że wymagała
pokonania kilku przeciwników naraz.

Naprawa (`EncounterService::simulate()`):
- Gold/XP liczone jest teraz osobno dla **każdego** potwora w grupie (`combat_data.monsters`),
  sumowane, i dopiero na sumie stosowana jest kara -66%.
- Event `MonsterDefeated` (Bestiariusz, patrz `docs/modules/achievements.md`) oraz progres
  questa `hunting` (patrz `docs/modules/quests.md`) emitowane są raz **na każdego**
  pokonanego potwora w grupie, a nie raz na całe starcie - zabicie 3 potworów faktycznie
  liczy się jako 3 zabicia w Bestiariuszu/Osiągnięciach.
- Łup (`DropService::rollLoot()`) losowany jest niezależnie dla każdego z 3-4 potworów z
  jego WŁASNEJ tabeli zrzutów (z tą samą karą 66% szansy na brak dropu per sztuka jak
  wcześniej dla całego starcia) - patrz `docs/modules/loot.md`, sekcja "Walka Grupowa".

### 11. Mikstury Łowcy Potworów (`bonus_vs_monsters`)
Gracz może używać mikstur alchemicznych z nowej kategorii **Łowca Potworów** (S = 5% / 1h, M = 10% / 3h, L = 15% / 6h). Po wypiciu mikstury jej flaga `bonus_vs_monsters` automatycznie zwiększa bazowe obrażenia zadawane **każdemu** potworowi na dowolnej mapie oraz w lochach (`EncounterService::calculateDamage()` and `DungeonService::calculateDamage()`). Bonus sumuje się z ewentualnymi rasowymi premiami broni (`strong_vs_<rasa>`).
