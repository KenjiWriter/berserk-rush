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
- **Trafienie Krytyczne (`crit`):** Obrażenia x 1.5. Szansa obliczana jest na bazie atrybutu `AGI`. Maksymalnie może wynosić 30% (gracz) i 20% (potwór).
- **Unik / Chybienie (`miss`):** Szansa na całkowity brak obrażeń (sztywno ustawione na 5%).

**Kalkulacja Obrażeń i Skalowanie Statystyk Potworów:**
- **Progresywne Skalowanie Potworów (`getScaledStats`):** Statystyki bazowe potworów (`hp`, `atk`, `def`, `agi`) skalują się z poziomem gracza, jeśli poziom gracza jest wyższy niż poziom bazowy potwora. Za każdy poziom różnicy statystyki potwora wzrastają o 10% (`multiplier = 1 + (player_level - monster_level) * 0.10`).
- Obrażenia zadawane przez gracza to suma: `10 + BonusAtrybutówBroni + Poziom + AtakEkwipunku`. Zmniejszane są one następnie o `Przeskalowana Obrona / 2` przeciwnika.
  - **Przeliczniki atrybutów broni (`getAttributeAttackBonus`):**
    - **Łuk (`bow`)**: `STR + AGI`
    - **Dzwon (`bell`)**: `STR + INT`
    - **Różdżka (`wand`)**: `INT * 2`
    - **Miecz (`sword`)**: `STR + AGI`
    - **Topór (`axe`)**: `STR * 2`
    - **Sztylet (`dagger`)**: `STR + AGI`
    - **Domyślnie / Pięści (`barehands`)**: `STR * 2`
- HP u gracza zależy głównie od `VIT`: `100 + (VIT * 10) + (Poziom * 5)`. HP u potworów skaluje się z ich poziomem oraz poziomem gracza.

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
> **Dzwon (`bell`) - broń hybrydowa "Magic Burst":** Dzwony zadają normalny atak
> fizyczny jak inna broń walki wręcz (`attack_min`/`attack_max`), ale dodatkowo mają
> szansę (`magic_burst_chance`, %) na dołożenie do trafienia OSOBNYCH, dodatkowych
> obrażeń magicznych (`magic_burst_min`-`magic_burst_max`). Ten dodatkowy komponent
> magiczny jest mitygowany tą samą obroną przeciwnika co reszta obrażeń (nie ma osobnej
> "obrony magicznej" - to celowe uproszczenie) i w pełni uczestniczy w mnożniku trafienia
> krytycznego. Logika: `EncounterService::calculateDamage()` (PvE, zwraca dodatkowy klucz
> `magic` w tablicy wyniku obok `base`/`bonus`/`total`), `PvPEncounterService::performAttack()`
> (Arena 1v1) oraz `GuildWarService::resolveTeamAttack()` (Wojny Gildii 5v5, patrz
> `docs/modules/guilds.md`, sekcja "Wojny Gildii") - wszystkie trzy ścieżki liczą
> "magic burst" niezależnie, ale w ten sam sposób.

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

### 6. Zabezpieczenie Anti-Cheat (Multi-Tab & Rate Limit)
W celu uniemożliwienia podwojonego lub potrojonego zdobywania doświadczenia i złota poprzez otwieranie przygody na tej samej postaci w 2 lub więcej kartach przeglądarki, system stosuje dwupoziomowe zabezpieczenie:
1. **Frontend Session Lock (`MapStub`)**: Każdy zamontowany komponent `MapStub` generuje unikalny token sesji karty i rejestruje go w pamięci Cache (`adventure_active_tab:{character_id}`). W przypadku otwarcia nowej karty lub przełączenia, aktywna staje się tylko ostatnia karta. Nieaktywne karty wstrzymują automatyczne walki i wyświetlają banner z opcją przejęcia aktywnego statusu.
2. **Backend Rate Limit (`EncounterService::start`)**: Serwis waliduje minimalny czas od rozpoczęcia ostatniej walki danej postaci (1300 ms) oraz nakłada blokadę transakcyjną `lockForUpdate()` na model postaci, odrzucając wszelkie próby symultanicznych żądań walki z błędem `COMBAT_IN_PROGRESS`.
