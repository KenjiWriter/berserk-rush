# Moduł Umiejętności Bojowych (Combat Skills)

Moduł odpowiada za system umiejętności (skilli) postaci. Gracze odblokowują, ulepszają i wyposażają skille bojowe u Czarnoksiężnika w Mieście, używając zgromadzonych Punktów Umiejętności (Skill Points). Wyposażone skille zastępują podstawowe ataki w walce (PvE oraz PvP).

## Implementacja
- **Modele i Encje:** `App\Infrastructure\Persistence\CombatSkill`, `App\Infrastructure\Persistence\CharacterCombatSkill`
- **Komponenty Livewire:** 
  - `App\Livewire\City\Warlock` (Odblokowywanie i ulepszanie skilli w Mieście)
  - `App\Livewire\Profile\SkillsTab` (Deck Umiejętności i zarządzanie wyposażonymi skillami w profilu)
  - `App\Livewire\Admin\CombatSkills` (Panel administracyjny do dodawania i edycji skilli)
- **Logika Walki:**
  - `App\Application\Combat\EncounterService.php` (PvE)
  - `App\Application\PvP\PvPEncounterService.php` (PvP)

---

## Mechaniki

### 1. Rozwój Umiejętności w Stylu Metin2 (Poziomy 1-17, M1-M10, G1-G10, P)
- Postać otrzymuje **3 Punkty Umiejętności** za każdy zdobyty poziom (łączna pula punktów = `(poziom_postaci - 1) * 3`).
- **Wielopoziomowa Progresja Umiejętności:**
  - **Poziomy Podstawowe (Lv. 1 - 17):** Odblokowanie daje Lv. 1. Gracze inwestują **Punkty Umiejętności (SP)** od Lv. 1 do Lv. 17 (1 SP / poziom, max 17 SP per skill). Reaching Lv. 17 odblokowuje awans na **M1**.
  - **Mistrz / Master (M1 - M10):** Awans z poziomu M1 do M10 wymaga użycia **1x Księga Umiejętności** (`skill_book`) oraz 500 Golda per poziom. Po ukończeniu M10 skill awansuje na **G1**.
  - **Arcymistrz / Grand Master (G1 - G10):** Awans z poziomu G1 do G10 wymaga użycia **1x Kamień Duchowy** (`soul_stone`) oraz 2,500 Golda per poziom. Po ukończeniu G10 skill wchodzi w stan gotowości do awansu na **P**.
  - **Perfekcyjny Mistrz / Perfect (P):** Osiągany poprzez użycie **1x Kamień Duchowy** + 10,000 Golda po opanowaniu G10. Jest to ostateczny, maksymalny poziom umiejętności (poziom 38) z legendarną złotą oprawą wizualną (+65% premii do mocy).
- **Dynamiczne Skracanie Czasu Odnowienia (CD) - 3 Kategorie Szybkości:**
  Od rebalansu 2026-08-06 skille są dzielone na 3 kategorie na podstawie `base_cooldown`
  (wartość na poziomie Normal/Lv.1), każda z własną krzywą CD w `CharacterCombatSkill::getCooldown()`,
  zamiast poprzedniego płaskiego `base_cooldown - 1/-2/-3`. Cel: skille dostępne co ok. 2-6 tur
  na każdym etapie mistrzostwa, konwergujące do okna 3-5 tur na Arcymistrzu/Perfect.
  - **Szybkie (`base_cooldown` 1-2):** CD **rośnie** z mistrzostwem zamiast maleć - rekompensata
    za rosnącą moc (`tierMultiplier` w `getEffectiveValue()`), żeby skill nie stał się jednocześnie
    mocniejszy i częstszy. Normal: bazowe 1-2. Master: `min(base+1, 3)`. Grand Master i Perfect:
    `min(base+2, 4)` (floor 3-4, identyczny na G i P - Perfect nie skraca dalej).
  - **Średnie (`base_cooldown` 3-5):** Normal: bazowe 3-5. Master: `max(3, base-1)`. Grand Master
    i Perfect: floor **3** (identyczny na G i P).
  - **Długie (`base_cooldown` 6+):** Normal: bez zmian (może być np. CD 7-10 dla skilli
    ultimate/legendarnych). Master: `max(6, base-2)`. Grand Master i Perfect: floor **5**
    (identyczny na G i P - osiągnięcie Arcymistrza to ostatnia redukcja CD, dalsza inwestycja
    do Perfect daje tylko +65% mocy, nie krótszy cooldown).
  - Cała logika żyje w jednym miejscu (`CharacterCombatSkill::getCooldown()`) i jest konsumowana
    przez wszystkie 5 silników walki (PvE, Lochy, Eventy Lokacji bezpośrednio przez `$cs->getCooldown()`;
    PvP i Wojna Gildii pośrednio przez `Character::createSnapshot()`, który zapisuje już wyliczone
    `getCooldown()` pod kluczem `base_cooldown` w snapshotcie) - brak duplikacji formuły.
- **Źródła Przedmiotów:**
  - **Skrzynia Ksiąg Umiejętności:** Po otwarciu przyznaje Księgę Umiejętności. Wypada ze WSZYSTKICH bossów na mapach (T1-T5+) oraz we wszystkich lochach (D1+).
  - **Kamień Duchowy:** Wypada z bossów na mapach od **Tier 5** wzwyż oraz z lochów od **Dungeon 3** wzwyż.
  - **Zwój Egzorcyzmu:** Wypada z tej samej puli bossów T5 oraz lochów D3+ co Kamień Duchowy, ale ze znacznie niższą wagą (rzadszy - patrz niżej).
  - **Klucze do Lochów:** Znacznie podniesiona szansa dropu ze wszystkich bossów map.
- **Zwój Egzorcyzmu (consumable, `sub_type = 'exorcism_scroll'`):** Użyty u Czarnoksiężnika podczas ulepszania skilla na etapie Mistrza (M1-M10, poziomy 6-15) gwarantuje **100% szans powodzenia** dla tej JEDNEJ próby, zamiast standardowych 50% (`MASTER_SUCCESS_CHANCE`). Wymagana Księga Umiejętności i 500 Gold nadal są zużywane normalnie - zwój usuwa wyłącznie ryzyko porażki, nie zastępuje kosztu. Sterowane flagą `$useExorcismScroll` w `UpgradeSkill::execute()`, przełączaną w widoku Czarnoksiężnika (`Warlock::toggleExorcismScroll()`) i zużywaną automatycznie przy najbliższej próbie w tym etapie. Nie działa na etapach Podstawowym (Lv. 1-17) ani Arcymistrza/Perfect (G1-G10/P).
- Synchronizacja punktów (`syncMissingPoints`) wylicza zużyte SP z uwzględnieniem limitu inwestycji 17 SP per skill.
- Opcja resetowania skilli (zwrot Punktów Umiejętności) dostępna jest poprzez użycie w ekwipunku **Zwoju Resetu Umiejętności** lub **Zwoju Pełnego Resetu**.

### 2. Deck Umiejętności i Wyposażanie
- Postać może posiadać **maksymalnie 3 aktywne skille** wyposażone jednocześnie w swoim Decku.
- Wyposażone skille są widoczne w profilu postaci pod portretem oraz w infoboxie czatu.
- Kliknięcie wyposażonego skilla pod portretem otwiera dedykowany infobox z informacjami o odnowieniu, czasie trwania, statystykach oraz przyciskiem do zdjęcia skilla.

### 3. Filtrowanie Listy Skilli u Czarnoksiężnika
- Widok Czarnoksiężnika (`Warlock.php` / `city.warlock`) pozwala przefiltrować `allSkills` po trzech niezależnych wymiarach, sterowanych publicznymi właściwościami `weaponFilter`, `typeFilter` i `categoryFilter` (metody `filterByWeapon()`/`filterByType()`/`filterByCategory()`):
  - **Typ Broni** (`required_weapon_type`): `all`/`sword`/`axe`/`bow`/`wand`/`bell`/`dagger`.
  - **Typ Umiejętności** (`type`): `all`/`active`/`passive`.
  - **Kategoria** (`categoryFilter`): `all`/`poison`/`fire`/`aoe`/`heal`/`defense`/`dmg` - wyliczana z `effect_type` (oraz `is_aoe` dla kategorii `aoe`), bez osobnej kolumny w bazie. Kategoria `dmg` to zbiorczy "wszystko inne" (obrażenia bezpośrednie/obszarowe, wzmocnienia, CC, pasywy) - dopełnienie pozostałych kategorii. `aoe` nie wyklucza się z pozostałymi (skill może być jednocześnie ogniowy i obszarowy, np. "Ognisty Grad").
- Filtry łączą się (AND) i można je stosować jednocześnie; wartość `all` pomija dany warunek `where()` w zapytaniu.

### 4. Wymagania Broni (Weapon Restrictions)
- Każda umiejętność posiada wymagany typ broni (`required_weapon_type`: `sword`, `axe`, `bow`, `wand`, `bell`, `dagger` lub `all`). 
- Jeśli gracz nie posiada wyposażonej broni wymaganego typu w ręce głównej (main hand), skill jest oznaczany jako nieaktywny dla obecnego ekwipunku i nie zostanie użyty podczas walki (PvE oraz PvP).

### 5. Działanie Skilli w Walce (PvE / PvP)
- Skille **aktywne** (`type = active`) zastępują atak podstawowy, gdy są gotowe (cooldown = 0) oraz gdy postać posiada wystarczającą ilość **Many (MP)** (`playerMana >= skillManaCost`).
- **Koszt Many (`base_mana_cost`, `scaling_mana_cost`):** Wszystkie umiejętności (zarówno aktywne, jak i pasywne) posiadają bazowy koszt many oraz przyrost kosztu na poziom skilla: `Koszt = base_mana_cost + (level - 1) * scaling_mana_cost`.
- Na początku walki wszystkie skille aktywne domyślnie są nieaktywne (mają czas odnowienia ustawiony na `base_cooldown - 1`, tzn. gotowe "o jedną turę wcześniej"). Gdy cooldown spadnie do 0 i postać posiada wystarczająco MP, skill aktywuje się automatycznie i pobiera manę.
- Skille **pasywne** (`type = passive`) NIE są "rzucane" i nie mają czasu odnowienia, ale **pobierają manę co turę / wywołanie**. W każdej turze walki (PvE, PvP, Lochy, Wojny Gildii), jeśli postać posiada wymaganą broń oraz wystarczającą ilość MP (`playerMana >= skillManaCost`), z jej puli many potrącany jest koszt pasywa, a jego efekt (`passive_aura_dmg`, `passive_extra_attack`) aktywuje się na tę turę. Jeśli postaci zabraknie MP, efekt pasywny w danej turze zostaje pominięty.
- **Informacje o Skillach w Interfejsie Walki (Tooltip / Modal):** W widokach walki (`map-stub`, `dungeon-run`, `arena-combat`) najechanie lub kliknięcie na ikony wyposażonych umiejętności wyświetla interaktywny modal/popover ze szczegółowymi informacjami: nazwą, ikoną, poziomem, typem (`Aktywna` / `Pasywna`), drenażem MP co turę/użycie, czasem odnowienia, wymaganą bronią oraz dokładnym przelicznikiem i opisem efektu.
- **Typy efektów (`effect_type`, wartość zapisana w bazie):**
  - **`poison` (Trucizna):** Zadaje % aktualnego HP przeciwnika co turę przez `base_duration` tur.
  - **`fire` (Ogień/Podpalenie):** Zadaje % maksymalnego HP przeciwnika co turę przez `base_duration` tur.
  - **`buff_phys_dmg` (Wzmocnienie):** Zwiększa obrażenia fizyczne o `base_value` (+ `scaling_value` za poziom skilla) na `base_duration` tur.
  - **`direct_dmg` (Atak bezpośredni):** Zadaje obrażenia zwiększone mnożnikiem `base_value` (+ `scaling_value`/poziom) wobec JEDNEGO celu.
  - **`aoe_dmg` (Obrażenia obszarowe):** Jak `direct_dmg`, ale w starciach grupowych (patrz pkt. 7) trafia WSZYSTKICH żywych przeciwników jednocześnie zamiast jednego. Wymaga ustawienia flagi `is_aoe = true` na skillu - patrz pkt. 7.
  - **`heal` (Leczenie):** Zamiast atakować, leczy postać gracza o `base_value` (+ `scaling_value`/poziom) % jej maksymalnego HP. Aktywne DoT-y na przeciwniku nadal tykają tej samej tury.
  - **`freeze` (Zamrożenie):** Zadaje obrażenia jak `direct_dmg` (mnożnik `base_value`) i unieruchamia trafiony cel na `base_duration` tur - przeciwnik traci tyle tur ataku.
  - **`stun` (Ogłuszenie):** Mechanicznie identyczne z `freeze` (dmg + unieruchomienie na `base_duration` tur), używane tematycznie do skilli wojownika (miecz/topór) zamiast magii mrozu.
  - **`passive_aura_dmg` (Pasywna aura obrażeń):** Stały bonus do obrażeń fizycznych o `base_value` (+ `scaling_value`/poziom), aktywny cały czas gdy skill jest wyposażony i wymóg broni spełniony (np. "Aura Miecza"). Sumuje się z aktywnym buffem `buff_phys_dmg`, jeśli oba są aktywne jednocześnie.
  - **`passive_extra_attack` (Pasywna szansa na dodatkowy atak):** Po każdym trafieniu (podstawowym lub skillem) rzucana jest szansa `base_value` (+ `scaling_value`/poziom, capowana na 75%) na natychmiastowy dodatkowy atak tej samej tury (np. "Furia Berserkera" dla topora). Dodatkowy atak nie rzuca ponownie szansy na kolejny dodatkowy atak (brak nieskończonych łańcuchów).
  - **`buff_defense` (Obrona):** Redukuje obrażenia przychodzące o `base_value` (+ `scaling_value`/poziom, capowane na 75%) na `base_duration` tur - lustrzane odbicie `buff_phys_dmg`, ale po stronie obrony zamiast ofensywy (np. "Postawa Tarczy"). Podobnie jak `buff_phys_dmg`, użycie skilla obronnego NIE zastępuje ataku tej tury - aktor jednocześnie zakłada buff i wykonuje zwykły atak.
- **Przełącznik obrażeń magicznych (`is_magic`, boolean):** Umiejętności Różdżki i Dzwonu (klasy magiczne) oznaczane są jako `is_magic = true`. Obrażenia takiego skilla (typu `direct_dmg`/`aoe_dmg`/`freeze`/`stun`) pokazywane są w logu walki jako `magicDamage` zamiast `baseDamage`/`bonusDamage`. To wyłącznie reklasyfikacja do UI - mitygacja (obrona przeciwnika) liczona jest identycznie jak dla obrażeń fizycznych, zgodnie z opisanym w `combat.md` celowym uproszczeniem (brak osobnej "obrony magicznej" w grze).

### 6. Panel Administracyjny
- Dostępny pod ścieżką `/admin/combat-skills`.
- Umożliwia tworzenie i edycję skilli, ustawianie minimalnego poziomu odblokowania, kosztu w punktach, przeliczników obrażeń, wymaganej broni (`sword`/`axe`/`bow`/`wand`/`bell`/`dagger`/`all`) oraz przypisywanie dedykowanych ikon ze ścieżki `/assets/skills/icons/{filename}`.
- Formularz zawiera dwa przełączniki: **Obrażenia magiczne** (`is_magic`) oraz **Bije obszarowo** (`is_aoe`) - patrz pkt. 5 i 7.
- Dla skilli pasywnych pole "Rodzaj Efektu" powinno mieć wartość `passive_aura_dmg` lub `passive_extra_attack`, a "Typ" ustawiony na "Pasywna".

### 7. Umiejętności Obszarowe (AoE) w Starciach Grupowych
- Starcia grupowe (2-4 potworów, patrz `combat.md` sekcja o skalowaniu over-level) domyślnie pozwalają graczowi atakować tylko JEDEN cel na turę, wybrany wg taktyki (`target_strategy`: `random`, `highest_hp`, `lowest_hp`, `highest_att`, `highest_def`).
- Umiejętność aktywna oznaczona `is_aoe = true` (i `effect_type` z grupy `direct_dmg`/`aoe_dmg`/`freeze`/`stun`) omija ten wybór i trafia WSZYSTKICH żywych przeciwników w starciu jednocześnie, generując po jednej wpisie w logu walki na każdego trafionego potwora (oznaczonej `aoe: true`).
- W starciach 1 na 1 (standardowe PvE, PvP, World Boss) flaga `is_aoe` nie ma dodatkowego efektu - skill działa jak zwykły atak na jedynego dostępnego przeciwnika.
- Przykłady: "Rozproszenie Strzał" (Łuk), "Ognisty Grad" (Różdżka), "Wir Ostrzy" (Sztylet), a także retrofitowane klasyki jak "Rozłupanie" (Topór), "Wirujący Miecz" (Miecz) czy "Grad Strzał" (Łuk).

### 8. Zamrożenie / Ogłuszenie (Crowd Control)
- Skille o `effect_type` = `freeze` lub `stun` po trafieniu odbierają celowi `base_duration` tur ataku (potwór/przeciwnik PvP traci swoje kolejki, ale jego cooldowny skilli nadal tykają).
- W starciach grupowych unieruchomienie liczone jest osobno dla każdego potwora (per-monster), więc można np. zamrozić tylko jednego z kilku przeciwników.
- Podobnie jak inne skille aktywne, `freeze`/`stun` nie podlegają szansie na unik (dodge) - w przeciwieństwie do zwykłego ataku podstawowego, skille zawsze trafiają, o ile są gotowe (cooldown = 0) i wymagana broń jest założona.
- **Wyjątek:** w PvP i Wojnie Gildii (5v5) skille - podobnie jak zwykłe ataki - MOGĄ zostać uniknięte (obie strony to gracze), w przeciwieństwie do PvE, gdzie skille zawsze trafiają. To świadoma różnica między silnikami, opisana też w `PvPEncounterService::performAttack()`.

### 9. Parytet Mechanik między PvE, PvP i Wojną Gildii (5v5)
- `EncounterService` (PvE), `PvPEncounterService` (Arena 1v1) i `GuildWarService` (Wojna Gildii 5v5) to trzy **osobne, zduplikowane** implementacje tej samej logiki walki (patrz komentarze w każdej z nich) - zmiana balansu w jednej wymaga ręcznej synchronizacji w pozostałych.
- Od 2026-07-29 wszystkie trzy silniki obsługują pełny zestaw `effect_type` (`heal`, `freeze`/`stun`, `buff_phys_dmg`, `buff_defense`, `poison`/`fire`, `direct_dmg`/`aoe_dmg`) oraz pasywy (`passive_aura_dmg`/`passive_extra_attack`). Wcześniej `GuildWarService` nie rozpoznawał `heal`/`aoe_dmg`/`freeze`/`stun` ani pasywów - skille tych typów wyekwipowane w Wojnie Gildii wykonywały zwykły, niezmodyfikowany atak zamiast swojego właściwego efektu (np. skill leczący nie leczył). To był realny powód, dla którego leczenie i taktyki wspierające nie miały znaczenia w starciach 5v5.
- W `GuildWarService` skille obszarowe (`is_aoe = true`) trafiają WSZYSTKICH żywych przeciwników z wrogiej drużyny (analogicznie do pkt. 7 dla PvE) - bez tykania DoT-ów na trafionych celach (ten sam wyjątek co w PvE).
- Pasywna szansa na dodatkowy atak (`passive_extra_attack`) w `GuildWarService` wybiera na nowo najsłabszego (najniższe HP) żywego przeciwnika wrogiej drużyny jako cel bonusowego ataku, ponieważ pierwotny cel mógł już zginąć od głównego ataku lub trafienia AOE.
