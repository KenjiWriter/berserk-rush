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
System opiera się na dwóch niezależnych zadaniach hourly zarejestrowanych w `routes/console.php`:
* **`app:world-boss-tick` → `WorldBossService::tickHourly()`**: JEDYNE zadanie tego joba to dosianie brakujących instancji bossów (`ensureBossesSpawned()`) — np. dla map, które aktualnie nie mają żywej instancji, w tym tych, które właśnie usunął `WorldBossRewardJob` po pokonaniu. Nie rusza walk w toku i nie rozdaje nagród.
* **Rozdawanie Nagród (`WorldBossRewardJob`)**: JEDYNY autorytet od nagród. Reaguje wyłącznie na instancje z `is_defeated = true` (czyli faktycznie pokonane, patrz niżej), zlicza całkowite obrażenia, tworzy ranking Top 10 i rozsyła maile z nagrodami (Kluczami do Lochów, `location = 'mail'`) według zasady: 1. miejsce (5 kluczy), 2. miejsce (4 klucze), 3. miejsce (3 klucze), 4-10. miejsce (1 klucz). Po rozdaniu nagród usuwa logi obrażeń oraz samą instancję (nie tylko flagę), żeby kolejny `ensureBossesSpawned()` mógł stworzyć świeżą instancję z pełnym HP.

> **UWAGA (fix 2026-07-28, historyczny bug — "world bossa nie da się zabić")**: Wcześniej `WorldBossService::tickHourly()` co godzinę BEZWARUNKOWO rozdawał nagrody i kasował WSZYSTKIE instancje (niezależnie od tego, czy boss faktycznie padł), a `WorldBossRewardJob` filtrował po `is_defeated = false` — czyli reagował na bossów WCIĄŻ ŻYWYCH, a nie pokonanych. Efekt: nawet gdy społeczność realnie wyzerowała wspólną pulę HP, oba zadania i tak co godzinę resetowały walkę od nowa, więc pokonanie bossa nigdy nie "domykało się" w bazie. Naprawiono usuwając zduplikowaną logikę nagród z `WorldBossService` i odwracając warunek w `WorldBossRewardJob` na `is_defeated = true`.

## Cykl Życia World Bossa
1. **Pojawienie się (Spawn)**: `ensureBossesSpawned()` (wywoływane przez `app:world-boss-tick` co godzinę oraz przez `WorldBossRewardJob` po rozdaniu nagród) tworzy instancję dla każdego potwora rangi `worldboss`, który nie ma aktualnie żywej instancji na swojej mapie. Boss staje się aktywny i widoczny dla wszystkich graczy w Mieście oraz jako przycisk na powiązanej Mapie.
2. **Ataki Graczy**: Z każdym atakiem pula HP bossa topnieje (`EncounterService`, patrz `WorldBossDamageLog`). Każdy atak gracza jest w ramach walki rozstrzygany na jego niekorzyść (`$winner = 'enemy'`) — to celowe, bo world boss to wspólny licznik obrażeń, a nie walka 1:1 do wygrania; obrażenia zadane liczą się jednak realnie do `current_hp`.
3. **Zakończenie (Defeat)**: Gdy `current_hp` spadnie do zera lub poniżej, flaga `is_defeated` ustawiana jest na `true` bezpośrednio w `EncounterService` — to jedyne miejsce, gdzie boss jest oznaczany jako realnie pokonany.
4. **Rozliczenie (Rewards)**: `WorldBossRewardJob` (hourly) znajduje instancje z `is_defeated = true`, rozdaje nagrody pocztą, usuwa logi obrażeń i instancję, po czym wywołuje `ensureBossesSpawned()`, żeby mapa od razu dostała świeżego bossa.

## Uwagi dot. dropów z world bossów
Starcia ze światowym bossem NIGDY nie kończą się `$winner = 'player'` (patrz punkt 2 powyżej), więc `DropService` — który uruchamia się tylko przy zwycięstwie gracza — nigdy nie jest wywoływany dla tych walk. Jakikolwiek wpis w `LootTable` przypisany bezpośrednio do potwora rangi `worldboss` jest więc martwy i nieosiągalny. Unikalne materiały/przedmioty każdego world bossa zostały dlatego przeniesione (w `MonsterLootSeeder`) na zwykłego, zabijalnego potwora rangi `boss` z tej samej mapy — patrz `docs/modules/loot.md`. Nagrody za samo pokonanie world bossa (klucze do lochów) idą całkowicie osobnym torem opisanym wyżej.
