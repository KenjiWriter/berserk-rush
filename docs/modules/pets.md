# Moduł Chowańców (Pets)

Moduł ten odpowiada za wykluwanie, transmutację (syntezę), karmienie oraz zarządzanie towarzyszami postaci (chowańcami / petami), którzy wspomagają gracza dodatkowymi atrybutami oraz Combat Power w walce.

## Implementacja
- Pliki logiki i komponenty UI: `app/Livewire/City/PetsComponent.php`
- Logika biznesowa i serwisy: `app/Application/Pets/IncubatorService.php`, `app/Application/Pets/PetService.php`
- Modele danych: `app/Infrastructure/Persistence/Pet.php`, `app/Infrastructure/Persistence/PetTemplate.php`, `app/Infrastructure/Persistence/CharacterIncubator.php`
- Widok: `resources/views/livewire/city/pets.blade.php`

## Mechaniki

### 1. Inkubacja i Wykluwanie (Inkubator)
- Gracz zdobywa jajo chowańca (np. podczas wypraw do lochów lub z bossów) i umieszcza je w Inkubatorze.
- Czas inkubacji zależy od rzadkości jaja (od zwykłego po legendarne).
- Po upływie wyznaczonego czasu jajo można wykluć, uzyskując chowańca z wylosowanymi statystykami bazowymi oraz unikalną nazwą.

### 2. Alchemiczny Syntezator Dusz ("Sokowirówka")
- Pozwala połączyć **3 niezałożone chowańce tej samej rzadkości** (np. 3 x Zwykły lub 3 x Epicki) w celu przeprowadzenia rytuału transmutacji.
- **Szansa na sukces:** **75%**.
- **Efekt sukcesu:** Użyte 3 chowańce zostają połączone, a gracz otrzymuje nowego chowańca z rzędu rzadkości o klasę wyższego (`common` -> `uncommon` -> `rare` -> `epic` -> `legendary`).
- **Efekt porażki (25%):** Esencja 3 chowańców ulega rozproszeniu i chowańce zostają zużyte.

### 3. System Karmienia i Awansowania Poziomu (Leveling & Feeding)
- Każdy chowaniec rozpoczyna z **poziomem 1** oraz **0 EXP**.
- Gracz może przekazać niepotrzebne przedmioty ze swojego plecaka (bronie, zbroje, materiały) jako pożywienie dla chowańca.
- **Ilość przyznawanego EXP:** Wartość EXP z przedmiotu obliczana jest na podstawie jego wymaganego poziomu oraz stopnia rzadkości przedmiotu:
  $$\text{EXP} = \max(1, \text{level\_requirement}) \times \text{rarity\_multiplier}$$
- **Wymagany EXP na Poziom:** Aby awansować z poziomu $L$ na $L+1$, pet potrzebuje $L \times 100$ EXP.
- **Skalowanie Statystyk:** Każdy zdobyty poziom chowańca zwiększa jego bazowe statystyki o **+10%** za każdy poziom powyżej 1:
  $$\text{Statystyka Efektywna} = \text{round}\left(\text{stat\_base} \times \left(1 + (L - 1) \times 0.10\right)\right)$$
- Założony chowaniec przekazuje swoje przeskalowane statystyki bezpośrednio do łącznych atrybutów postaci (`Character::getTotalAttributes()`) oraz przelicza swój Combat Power w `Character::getCombatPower()`.
