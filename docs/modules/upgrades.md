# Moduł Kuźni (Upgrades / Forge)

Moduł Kuźni pozwala graczom na ulepszanie siły bazowych przedmiotów (broni i zbroi). Zwiększa ich statystyki, by sprostać silniejszym wyzwaniom.

> **Uwaga (refaktor):** Ulepszanie zostało wydzielone z Brońmistrza (`Weaponsmith`) i Zbrojmistrza (`Armorsmith`) do osobnej domeny **Kowal** (`Blacksmith`), wspólnej dla broni i zbroi. Pełny opis: `docs/modules/blacksmith.md`. Brońmistrz i Zbrojmistrz od teraz odpowiadają wyłącznie za kupno i sprzedaż.

## Implementacja
- Pliki logiki i akcji:
  - `app/Application/Items/UpgradeService.php`
  - `app/Infrastructure/Persistence/UpgradeRule.php`
- Komponent Livewire:
  - `app/Livewire/City/Blacksmith.php`
- Widok:
  - `resources/views/livewire/city/blacksmith.blade.php`

## Mechaniki

### 1. Interfejs Kowala
- Moduł dostępny w mieście pod postacią Kowala, dostępnego z Hubu (kafelek "Kowal") oraz z poziomu Brońmistrza/Zbrojmistrza (przycisk "Kowal (Ulepszanie)").
- Kowal obsługuje ulepszanie zarówno broni (Slot: `main_hand`), jak i zbroi (`head`, `chest`, `feet`) w jednym, ogólnym widoku - bez podziału na osobne postaci NPC.
- Widok Kowala zawiera wyłącznie Kuźnię Ulepszeń (jedna zakładka, bez rzemiosła). Wytwarzanie (crafting) przedmiotów - w tym broni i zbroi - obsługuje Wiedźma, patrz `docs/modules/witch_and_crafting.md`.

### 2. Proces Ulepszania
- Przedmioty mają swój poziom ulepszenia wyrażony w systemie od `+0` (domyślny) do `+9` (maksymalny).
- Każdy proces podnoszenia poziomu obarczony jest szansą na powodzenie. Szansa ta systematycznie spada im wyższy jest aktualny poziom ulepszenia:
  - `+0 -> +1` to zazwyczaj 95%
  - W okolicach `+5` i wyżej szansa drastycznie maleje, stanowiąc mechanikę "gold sink" dla graczy we wczesnej fazie.
- **Koszt:** Ulepszanie pochłania Złoto (`gold`) oraz zdefiniowane materiały rzemieślnicze z dedykowanego schowka na materiały (`material_stash`).

### 3. Skutki i Porażki
- **Sukces:** Poziom przedmiotu rośnie o +1 (zapisywane w `ItemInstance->upgrade_level`). Przedmiot otrzymuje dodatkowe statystyki kalkulowane w czasie rzeczywistym (10% bazowych statystyk przedmiotu na każdy poziom ulepszenia, min. +1 dla statystyk dodatnich) i dopisywane do statystyk z szablonu. Widoczne jako `( +X )` przy nazwie przedmiotu.
- **Porażka:** W obecnej iteracji systemu gracz traci jedynie zużyte zasoby. W odróżnieniu od gier z gatunku hardcore (np. klasycznego Metin2), system nie niszczy broni i nie redukuje poziomu ulepszenia w przypadku niepowodzenia.
- Przebieg i zasady są wyraźnie określone na ekranie w interfejsie Kuźni. O sukcesie bądz porażce informuje modal z graficznym komunikatem (ikona ✨ przy sukcesie lub 💥 przy failu).
