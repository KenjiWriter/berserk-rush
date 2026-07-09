# Moduł Profilu i Ekwipunku (Profile & Equipment)

Moduł ten odpowiada za prezentację statystyk gracza, rozwój postaci (rozdawanie atrybutów) oraz zarządzanie posiadanymi przedmiotami (zakładanie, zdejmowanie).

## Implementacja
- Pliki logiki i komponenty UI: `app/Livewire/City/Profile.php`
- Akcje przedmiotu: `app/Application/Items/EquipItem.php`, `app/Application/Items/UnequipItem.php`
- Widoki: `resources/views/livewire/city/profile.blade.php`

## Mechaniki

### 1. Interfejs Użytkownika (UI Profilu)
- **Informacje Podstawowe:** Wyświetla aktualny poziom postaci, nazwę, awatar oraz wizualny pasek postępu (XP Bar) z informacją, ile doświadczenia brakuje do następnego awansu.
- **Portrety i Sloty:** Graficzna reprezentacja ubranej postaci z 6 slotami na sprzęt: Głowa (Head), Klatka (Chest), Główna Ręka (Main hand), Szyja (Neck), Pierścień (Ring), Stopy (Feet).

### 2. Zarządzanie Przedmiotami (Equip / Unequip)
Mechanika oparta jest o obiekty akcji (Actions) weryfikujące reguły biznesowe:
- **`EquipItem`:** Odpowiada za założenie przedmiotu. Weryfikuje:
  - Czy postać posiada wystarczający poziom (`level_requirement`).
  - Zdejmuje ewentualny przedmiot zajmujący ten sam slot i zamienia go na nowy, przeliczając bonusy.
- **`UnequipItem`:** Odpowiada za zdjęcie przedmiotu i przeniesienie go z powrotem do dostępnego ekwipunku postaci (do wolnego miejsca w plecaku).

### 3. Rozwój Atrybutów
Postać zdobywa punkty postaci (`character_points`) za każdy zdobyty poziom (np. +3 punkty za każdy awans).
Z poziomu widoku Profilu gracz może ręcznie przydzielać zdobyte punkty do swoich głównych statystyk (STR, INT, VIT, AGI):
- Mechanika pozwala na szybkie dodawanie punktów pojedynczo (`+1`) lub po pięć sztuk naraz (`+5`).
- Zwiększone statystyki od razu aktualizują całkowitą pulę atrybutów postaci używanych w walce.
- UI używa animowanych wskaźników (pulse) dla nieprzydzielonych punktów i interaktywnych dymków (tooltipów) z informacją o działaniu konkretnej statystyki.
