# Moduł Profilu i Ekwipunku (Profile & Equipment)

Moduł ten odpowiada za prezentację statystyk gracza, rozwój postaci (rozdawanie atrybutów) oraz zarządzanie posiadanymi przedmiotami (zakładanie, zdejmowanie).

## Implementacja
- Pliki logiki i komponenty UI: `app/Livewire/City/Profile.php`
- Akcje przedmiotu: `app/Application/Items/EquipItem.php`, `app/Application/Items/UnequipItem.php`
- Widoki: `resources/views/livewire/city/profile.blade.php`

## Mechaniki

### 1. Interfejs Użytkownika (UI Profilu)
- **Informacje Podstawowe:** Wyświetla aktualny poziom postaci, nazwę, awatar oraz wizualny pasek postępu (XP Bar) z informacją, ile doświadczenia brakuje do następnego awansu. Gracz posiada również możliwość edycji swojego awatara klikając ikonę ołówka obok portretu. Otwiera to modal z darmowymi avatarami oraz odblokowanymi avatarami premium.
- **Portrety i Sloty:** Graficzna reprezentacja ubranej postaci z 6 slotami na sprzęt: Głowa (Head), Klatka (Chest), Główna Ręka (Main hand), Szyja (Neck), Pierścień (Ring), Stopy (Feet).

### 2. Zarządzanie Przedmiotami (Equip / Unequip)
Mechanika oparta jest o obiekty akcji (Actions) weryfikujące reguły biznesowe:
- **`EquipItem`:** Odpowiada za założenie przedmiotu. Weryfikuje:
  - Czy postać posiada wystarczający poziom (`level_requirement`).
  - Zdejmuje ewentualny przedmiot zajmujący ten sam slot i zamienia go na nowy, przeliczając bonusy.
- **`UnequipItem`:** Odpowiada za zdjęcie przedmiotu i przeniesienie go z powrotem do dostępnego ekwipunku postaci (do wolnego miejsca w plecaku).
- **Obsługa Ekwipunku (Drag & Drop oraz Double-Click):**
  - **Przeciągnij i Upuść (Drag & Drop):** Gracz może przeciągnąć przedmiot z plecaka bezpośrednio na odpowiadający mu slot na postaci (np. broń na slot `main_hand`, pancerz na `chest`). Poprawne sloty są podświetlane na zielono z powiększeniem, a sloty niedozwolone (np. zbyt niski poziom) na czerwono. Przeciągnięcie założonego przedmiotu lub peta z powrotem na siatkę plecaka automatycznie go zdejmuje.
  - **Podwójne Kliknięcie (Double-Click):** Dwukrotne kliknięcie myszką na przedmiot w plecaku zakłada go automatycznie na odpowiedni slot (lub używa w przypadku mikstur / umieszcza jajko w inkubatorze). Dwukrotne kliknięcie na założony przedmiot zdejmuje go do plecaka.

### 3. Limity Plecaka oraz Magazyn Gracza (Player Stash)
- **Limity Pojemności Plecaka:**
  - Zwykły gracz: **32 sloty** w plecaku.
  - Gracz ze statusem VIP (`hasPremium()`): **64 sloty** w plecaku.
  - Przedmioty zdejmujące, odbierane z poczty, kupowane lub wyciągane z magazynu wymagają wolnego miejsca w plecaku.
- **Magazyn Gracza (Konto):**
  - Magazyn kontowy przypisany do konta użytkownika (`User`), wspólny dla wszystkich posiadanych postaci.
  - Domyślnie posiada **2 sloty**.
  - Możliwość powiększenia magazynu w profilu: koszt **50 gemów** za każdy dodatkowy slot (`stash_slots`).
  - Akcje `PlayerStashService`: `deposit()` (przeniesienie przedmiotu z plecaka do magazynu gracza) oraz `withdraw()` (wyciągnięcie przedmiotu z magazynu do plecaka).

### 4. Rozwój Atrybutów
Postać zdobywa punkty postaci (`character_points`) za każdy zdobyty poziom (np. +3 punkty za każdy awans).
Z poziomu widoku Profilu gracz może ręcznie przydzielać zdobyte punkty do swoich głównych statystyk (STR, INT, VIT, AGI):
- Mechanika pozwala na szybkie dodawanie punktów pojedynczo (`+1`) lub po pięć sztuk naraz (`+5`).
- UI używa animowanych wskaźników (pulse) dla nieprzydzielonych punktów i interaktywnych dymków (tooltipów) z informacją o działaniu konkretnej statystyki.
- **Resetowanie Atrybutów:** Gracz ma możliwość zresetowania rozdanych atrybutów dla swoich postaci z poziomu Sklepu Premium (`ItemShopComponent`) za 50 Gemów. Reset zeruje przydzielone statystyki i zwraca całą pulę punktów (`character_points` = 10 + (poziom - 1) * 3) do ponownego rozdysponowania.
