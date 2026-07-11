# World Boss Module

System World Bossów (Światowych Bossów) pozwala na globalne wyzwania, w których cała społeczność serwera walczy wspólnie z potężnym przeciwnikiem o bardzo dużej puli punktów zdrowia (HP). Wydarzenia te angażują graczy, zachęcając ich do rywalizacji i współpracy jednocześnie.

## Kluczowe Cechy

* **Globalna Pula HP**: Boss posiada jedną, współdzieloną przez wszystkich graczy na serwerze pulę zdrowia (np. `current_hp` i `total_hp`).
* **Zadawanie Obrażeń**: Walka z bossem korzysta z rdzennego systemu turowego. Z racji przewagi bossa z reguły wygrywa on potyczkę, a system rejestruje **Zadane Obrażenia (Damage Dealt)** w tabeli logów. Gracze dołączają do walki i próbują przeżyć jak najdłużej, by zmaksymalizować swój DMG.
* **Jedna Próba (Single Attempt)**: Gracz może zaatakować daną instancję World Bossa tylko raz. Aby zapobiec podwójnym wpisom i tzw. race conditions (odświeżanie strony, manipulacja URL), zastosowano twarde blokady transakcyjne na poziomie tworzenia `Encounter` (`EncounterService::start()`).
* **Globalny Ranking**: Po każdym uderzeniu wynik gracza dopisywany jest do logów (`WorldBossDamageLog`). System w czasie rzeczywistym grupuje te logi po `character_id` i sumuje zadany DMG, układając listę Top 10 najlepszych wojowników.
* **Spłaszczone Nagrody (Scaling Rewards)**: Po każdej walce gracz otrzymuje Złoto i Doświadczenie. Wzór nagród używa krzywej potęgowej (np. `pow(damageDealt, 0.7)`), co pozwala uniknąć astronomicznych kwot w przypadku postaci zadających ekstremalnie wysokie obrażenia, jednocześnie solidnie wynagradzając aktywnych graczy.

## Architektura Systemu

### 1. `WorldBossInstance` (Model)
Przechowuje dane o aktualnie żyjącym (lub pokonanym) bossie na mapie. Śledzi `total_hp`, `current_hp` oraz flagę `is_defeated`. Boss nie znika od razu po pokonaniu, aby gracze mogli sprawdzić końcowy ranking.

### 2. `WorldBossDamageLog` (Model)
Rejestruje każde uderzenie zadane przez gracza danej instancji bossa. Powiązuje `world_boss_instance_id`, `character_id` oraz `damage`. 

### 3. Zadania Cykliczne (Cron Jobs)
System opiera się na zadaniach (Jobs) uruchamianych cyklicznie przez konsolę (`WorldBossTick`):
* **Spawn Bossa**: Gdy brakuje aktywnego bossa, system losowo wybiera potwora i przypisuje go do odpowiedniej mapy z ogromnym modyfikatorem HP (np. `hp * 1000`).
* **Rozdawanie Nagród (`WorldBossRewardJob`)**: Kiedy boss zostanie pokonany lub upłynie jego czas ważności, zadanie zlicza całkowite obrażenia, tworzy ranking Top 10 i rozsyła maile z nagrodami (np. Kluczami do Lochów) według zasady: 1. miejsce (5 kluczy), 2. miejsce (4 klucze), 3. miejsce (3 klucze), 4-10. miejsce (1 klucz).

## Cykl Życia World Bossa
1. **Pojawienie się (Spawn)**: Wywołane przez Command `WorldBossTick`. Boss staje się aktywny i widoczny dla wszystkich graczy w Mieście oraz jako przycisk na powiązanej Mapie.
2. **Ataki Graczy**: Z każdym atakiem pula HP bossa topnieje. System asynchronicznie przelicza i dopisuje obrażenia używając `SimulateCombatJob`. 
3. **Zakończenie (Defeat)**: Jeżeli HP spadnie poniżej zera, flaga `is_defeated` ustawiana jest na `true`.
4. **Rozliczenie (Rewards)**: Uruchamia się Job pocztowy, który bezpiecznie przyznaje łupy na skrzynki pocztowe graczy w odpowiednich proporcjach.
