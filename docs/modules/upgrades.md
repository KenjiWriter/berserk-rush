# Moduł Kuźni (Upgrades / Forge)

Moduł Kuźni pozwala graczom na ulepszanie siły bazowych przedmiotów (broni, zbroi, biżuterii). Zwiększa ich statystyki, by sprostać silniejszym wyzwaniom.

## Implementacja
- Pliki logiki i akcji: 
  - `app/Application/Items/UpgradeItem.php`
  - `app/Domain/Items/UpgradeStrategy.php`
- Komponenty Livewire: 
  - `app/Livewire/City/Weaponsmith.php`
  - `app/Livewire/City/Armorsmith.php`
- Widoki: 
  - `resources/views/livewire/city/weaponsmith.blade.php`
  - `resources/views/livewire/city/armorsmith.blade.php`

## Mechaniki

### 1. Interfejs Kowala / Płatnerza
- Moduł dostępny w mieście pod postacią lokalnych rzemieślników:
  - **Brońmistrz:** Zajmuje się kupnem, sprzedażą oraz ulepszaniem broni (Slot: `main_hand`).
  - **Płatnerz:** Zajmuje się pancerzami (`head`, `chest`, `feet`) oraz biżuterią (`neck`, `ring`).
- Posiadają zakładki do Kupowania podstawowego sprzętu, Sprzedawania śmieciowych dropów (wypadających potworów) i dedykowaną **Kuźnię Ulepszeń**.

### 2. Proces Ulepszania
- Przedmioty mają swój poziom ulepszenia wyrażony w systemie od `+0` (domyślny) do `+9` (maksymalny).
- Każdy proces podnoszenia poziomu obarczony jest szansą na powodzenie. Szansa ta systematycznie spada im wyższy jest aktualny poziom ulepszenia:
  - `+0 -> +1` to zazwyczaj 95%
  - W okolicach `+5` i wyżej szansa drastycznie maleje, stanowiąc mechanikę "gold sink" dla graczy we wczesnej fazie.
- **Koszt:** Ulepszanie zawsze pochłania Złoto (`gold`). Z czasem do systemu mogą zostać wpięte konkretne materiały ulepszeniowe w postaci innych unikalnych przedmiotów z plecaka.

### 3. Skutki i Porażki
- **Sukces:** Poziom przedmiotu rośnie o +1 (zapisywane w `ItemInstance->upgrade_level`). Przedmiot otrzymuje dodatkowe statystyki kalkulowane w czasie rzeczywistym i dopisywane do statystyk z szablonu. Widoczne jako `( +X )` przy nazwie przedmiotu.
- **Porażka:** W obecnej iteracji systemu gracz traci jedynie zużyte zasoby. W odróżnieniu od gier z gatunku hardcore (np. klasycznego Metin2), system nie niszczy broni i nie redukuje poziomu ulepszenia w przypadku niepowodzenia. 
- Przebieg i zasady są wyraźnie określone na ekranie w interfejsie Kuźni. O sukcesie bądz porażce informuje modal z graficznym komunikatem (ikona ✨ przy sukcesie lub 💥 przy failu).
