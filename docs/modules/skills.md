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

### 1. Punkty Umiejętności (Skill Points)
- Postać otrzymuje **3 Punkty Umiejętności** za każdy zdobyty poziom (łączna pula punktów = `(poziom_postaci - 1) * 3`).
- Punkty wykorzystuje się u Czarnoksiężnika do odblokowywania nowych skilli (koszt odblokowania zależy od skilla) oraz podnoszenia ich poziomu (1 punkt za poziom).
- **Maksymalny poziom umiejętności wynosi 5 (Max Lv. 5).**
- Synchronizacja punktów (`syncMissingPoints`) automatycznie wylicza pozostałe nieprzydzielone punkty na podstawie zdobytych punktów minus koszty odblokowanych i ulepszonych skilli.
- Opcja resetowania skilli (zwrot Punktów Umiejętności) dostępna jest w Sklepie (ItemShop).

### 2. Deck Umiejętności i Wyposażanie
- Postać może posiadać **maksymalnie 3 aktywne skille** wyposażone jednocześnie w swoim Decku.
- Wyposażone skille są widoczne w profilu postaci pod portretem oraz w infoboxie czatu.
- Kliknięcie wyposażonego skilla pod portretem otwiera dedykowany infobox z informacjami o odnowieniu, czasie trwania, statystykach oraz przyciskiem do zdjęcia skilla.

### 3. Wymagania Broni (Weapon Restrictions)
- Każda umiejętność posiada wymagany typ broni (`required_weapon_type`: `sword`, `axe`, `bow`, `wand`, `bell`, `dagger` lub `all`). 
- Jeśli gracz nie posiada wyposażonej broni wymaganego typu w ręce głównej (main hand), skill jest oznaczany jako nieaktywny dla obecnego ekwipunku i nie zostanie użyty podczas walki (PvE oraz PvP).

### 4. Działanie Skilli w Walce (PvE / PvP)
- Skille **aktywne** (`type = active`) zastępują atak podstawowy, gdy są gotowe (cooldown = 0).
- Na początku walki wszystkie skille aktywne domyślnie są nieaktywne (mają czas odnowienia ustawiony na `base_cooldown - 1`, tzn. gotowe "o jedną turę wcześniej"). Gdy cooldown spadnie do 0, skill aktywuje się automatycznie.
- Skille **pasywne** (`type = passive`) NIE są "rzucane" i nie mają cooldownu - działają stale, przez cały czas trwania walki, o ile spełniony jest wymóg broni (patrz pkt. 3). Nadal zajmują jeden z 3 slotów Decku.
- **Typy efektów (`effect_type`, wartość zapisana w bazie):**
  - **`poison` (Trucizna):** Zadaje % aktualnego HP przeciwnika co turę przez `base_duration` tur.
  - **`fire` (Ogień/Podpalenie):** Zadaje % maksymalnego HP przeciwnika co turę przez `base_duration` tur.
  - **`buff_phys_dmg` (Wzmocnienie):** Zwiększa obrażenia fizyczne o `base_value` (+ `scaling_value` za poziom skilla) na `base_duration` tur.
  - **`direct_dmg` (Atak bezpośredni):** Zadaje obrażenia zwiększone mnożnikiem `base_value` (+ `scaling_value`/poziom) wobec JEDNEGO celu.
  - **`aoe_dmg` (Obrażenia obszarowe):** Jak `direct_dmg`, ale w starciach grupowych (patrz pkt. 6) trafia WSZYSTKICH żywych przeciwników jednocześnie zamiast jednego. Wymaga ustawienia flagi `is_aoe = true` na skillu - patrz pkt. 6.
  - **`heal` (Leczenie):** Zamiast atakować, leczy postać gracza o `base_value` (+ `scaling_value`/poziom) % jej maksymalnego HP. Aktywne DoT-y na przeciwniku nadal tykają tej samej tury.
  - **`freeze` (Zamrożenie):** Zadaje obrażenia jak `direct_dmg` (mnożnik `base_value`) i unieruchamia trafiony cel na `base_duration` tur - przeciwnik traci tyle tur ataku.
  - **`stun` (Ogłuszenie):** Mechanicznie identyczne z `freeze` (dmg + unieruchomienie na `base_duration` tur), używane tematycznie do skilli wojownika (miecz/topór) zamiast magii mrozu.
  - **`passive_aura_dmg` (Pasywna aura obrażeń):** Stały bonus do obrażeń fizycznych o `base_value` (+ `scaling_value`/poziom), aktywny cały czas gdy skill jest wyposażony i wymóg broni spełniony (np. "Aura Miecza"). Sumuje się z aktywnym buffem `buff_phys_dmg`, jeśli oba są aktywne jednocześnie.
  - **`passive_extra_attack` (Pasywna szansa na dodatkowy atak):** Po każdym trafieniu (podstawowym lub skillem) rzucana jest szansa `base_value` (+ `scaling_value`/poziom, capowana na 75%) na natychmiastowy dodatkowy atak tej samej tury (np. "Furia Berserkera" dla topora). Dodatkowy atak nie rzuca ponownie szansy na kolejny dodatkowy atak (brak nieskończonych łańcuchów).
- **Przełącznik obrażeń magicznych (`is_magic`, boolean):** Umiejętności Różdżki i Dzwonu (klasy magiczne) oznaczane są jako `is_magic = true`. Obrażenia takiego skilla (typu `direct_dmg`/`aoe_dmg`/`freeze`/`stun`) pokazywane są w logu walki jako `magicDamage` zamiast `baseDamage`/`bonusDamage`. To wyłącznie reklasyfikacja do UI - mitygacja (obrona przeciwnika) liczona jest identycznie jak dla obrażeń fizycznych, zgodnie z opisanym w `combat.md` celowym uproszczeniem (brak osobnej "obrony magicznej" w grze).

### 5. Panel Administracyjny
- Dostępny pod ścieżką `/admin/combat-skills`.
- Umożliwia tworzenie i edycję skilli, ustawianie minimalnego poziomu odblokowania, kosztu w punktach, przeliczników obrażeń, wymaganej broni (`sword`/`axe`/`bow`/`wand`/`bell`/`dagger`/`all`) oraz przypisywanie dedykowanych ikon ze ścieżki `/assets/skills/icons/{filename}`.
- Formularz zawiera dwa przełączniki: **Obrażenia magiczne** (`is_magic`) oraz **Bije obszarowo** (`is_aoe`) - patrz pkt. 4 i 6.
- Dla skilli pasywnych pole "Rodzaj Efektu" powinno mieć wartość `passive_aura_dmg` lub `passive_extra_attack`, a "Typ" ustawiony na "Pasywna".

### 6. Umiejętności Obszarowe (AoE) w Starciach Grupowych
- Starcia grupowe (2-4 potworów, patrz `combat.md` sekcja o skalowaniu over-level) domyślnie pozwalają graczowi atakować tylko JEDEN cel na turę, wybrany wg taktyki (`target_strategy`: `random`, `highest_hp`, `lowest_hp`, `highest_att`, `highest_def`).
- Umiejętność aktywna oznaczona `is_aoe = true` (i `effect_type` z grupy `direct_dmg`/`aoe_dmg`/`freeze`/`stun`) omija ten wybór i trafia WSZYSTKICH żywych przeciwników w starciu jednocześnie, generując po jednej wpisie w logu walki na każdego trafionego potwora (oznaczonej `aoe: true`).
- W starciach 1 na 1 (standardowe PvE, PvP, World Boss) flaga `is_aoe` nie ma dodatkowego efektu - skill działa jak zwykły atak na jedynego dostępnego przeciwnika.
- Przykłady: "Rozproszenie Strzał" (Łuk), "Ognisty Grad" (Różdżka), "Wir Ostrzy" (Sztylet), a także retrofitowane klasyki jak "Rozłupanie" (Topór), "Wirujący Miecz" (Miecz) czy "Grad Strzał" (Łuk).

### 7. Zamrożenie / Ogłuszenie (Crowd Control)
- Skille o `effect_type` = `freeze` lub `stun` po trafieniu odbierają celowi `base_duration` tur ataku (potwór/przeciwnik PvP traci swoje kolejki, ale jego cooldowny skilli nadal tykają).
- W starciach grupowych unieruchomienie liczone jest osobno dla każdego potwora (per-monster), więc można np. zamrozić tylko jednego z kilku przeciwników.
- Podobnie jak inne skille aktywne, `freeze`/`stun` nie podlegają szansie na unik (dodge) - w przeciwieństwie do zwykłego ataku podstawowego, skille zawsze trafiają, o ile są gotowe (cooldown = 0) i wymagana broń jest założona.
