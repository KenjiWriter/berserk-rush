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
- Postać otrzymuje **1 Punkt Umiejętności** za każdy zdobyty poziom.
- Punkty wykorzystuje się u Czarnoksiężnika do odblokowywania nowych skilli oraz podnoszenia ich poziomu.
- Opcja resetowania skilli (zwrot Punktów Umiejętności) dostępna jest w Sklepie (ItemShop).

### 2. Deck Umiejętności i Wyposażanie
- Postać może posiadać **maksymalnie 3 aktywne skille** wyposażone jednocześnie w swoim Decku.
- Wyposażone skille są widoczne w profilu postaci pod portretem oraz w infoboxie czatu.
- Kliknięcie wyposażonego skille pod portretem otwiera dedykowany infobox z informacjami o odnowieniu, czasie trwania, statystykach oraz przyciskiem do zdjęcia skilla.

### 3. Wymagania Broni (Weapon Restrictions)
- Niektóre umiejętności (np. *Trująca Strzała*) wymagają określonego typu broni (np. łuk). 
- Jeśli gracz nie posiada wyposażonej broni wymaganego typu, skill nie zostanie użyty podczas walki.

### 4. Działanie Skilli w Walce (PvE / PvP)
- Skille zastępują atak podstawowy.
- Na początku walki wszystkie skille domyślnie są nieaktywne (mają czas odnowienia). Gdy cooldown spadnie do 0, skill aktywuje się automatycznie.
- **Typy efektów:**
  - **Trucizna (`dot_poison`):** Zadaje % aktualnego HP przeciwnika co turę przez X tur.
  - **Ogień (`dot_fire`):** Zadaje % maksymalnego HP przeciwnika co turę przez X tur.
  - **Wzmocnienie (`buff_damage`):** Zwiększa obrażenia fizyczne o % na X tur.
  - **Atak bezpośredni (`direct_dmg`):** Zadaje zwiększone obrażenia natychmiastowe.

### 5. Panel Administracyjny
- Dostępny pod ścieżką `/admin/combat-skills`.
- Umożliwia tworzenie i edycję skilli, ustawianie minimalnego poziomu odblokowania, kosztu w punktach, przeliczników obrażeń oraz przypisywanie dedykowanych ikon ze ścieżki `/assets/skills/icons/{filename}`.
