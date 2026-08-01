# Moduł Chowańców (Pets)

Domena **Pety** (`city.pets`, dostępna z Hubu i nawigacji, niezależnie od
Profilu) odpowiada za wykluwanie, fuzję, karmienie, ekwipunek oraz handel
chowańcami wspomagającymi postać dodatkowymi atrybutami i Combat Power w
walce. System oparty jest o **6 tierów** zamiast dawnych 5 "rzadkości" i
zastępuje starą "Sokowirówkę Dusz" (3 pety → 1) mechaniką **Fuzji** (2 pety →
1).

## Implementacja
- Komponent UI: `app/Livewire/City/PetsComponent.php`, widok
  `resources/views/livewire/city/pets.blade.php`.
- Logika domenowa (czyste reguły/formuły): `app/Domain/Pets/PetTier.php`,
  `PetGrowthStage.php`, `PetFusionRules.php`, `PetStatCalculator.php`,
  `PetLevelCurve.php`.
- Serwisy aplikacyjne: `app/Application/Pets/IncubatorService.php`,
  `PetFeedingService.php`, `PetFusionService.php`, `PetEquipmentService.php`.
- Modele: `app/Infrastructure/Persistence/Pet.php`,
  `CharacterIncubator.php`. `PetTemplate.php` pozostaje jako martwa/nieużywana
  tabela wykorzystywana wyłącznie przez GM-owe `/give pet <nazwa>` na czacie
  globalnym - hatch NIE korzysta z niej (patrz sekcja "Rzeczy odłożone").
- Stałe balansu: **`config/pets.php`** - jedyne źródło prawdy dla tierów,
  szans wyklucia, fuzji, karmienia i normy staty. Panel informacyjny w UI
  (przycisk "?" na stronie Petów) czyta te same stałe, więc nigdy nie
  rozjeżdża się z realną logiką.

## 1. Tiery

| Tier | Nazwa | Czas wyklucia | Norma poziomowa | Przedział lvl karmienia |
|---|---|---|---|---|
| T1 | Pospolity | 1h | 100% | 0-20 |
| T2 | Zwykły | 1.5h | 130% | 15-35 |
| T3 | Nietypowy | 2h | 160% | 30-50 |
| T4 | Rzadki | 2.5h | 190% | 45-65 |
| T5 | Epicki | 3h | 220% | 60-80 |
| T6 | Legendarny | 4h | 250% | 75+ |

## 2. Inkubacja i Wykluwanie

- Gracz umieszcza jajko chowańca (`ItemTemplate.egg_tier` = tier 1-6) w
  Inkubatorze (1 jajko naraz na postać, `CharacterIncubator`).
- Czas inkubacji = `PetTier::hatchHours(egg_tier)`.
- Po wykluciu **wynikowy tier peta jest losowany** z macierzy szans
  (`config('pets.hatch_matrix')`), NIE jest to proste kopiowanie tieru jajka:

| Jajko \ Pet | T1 | T2 | T3 | T4 | T5 | T6 |
|---|---|---|---|---|---|---|
| T1 | 100% | - | - | - | - | - |
| T2 | 25% | 75% | - | - | - | - |
| T3 | 10% | 25% | 65% | - | - | - |
| T4 | - | 10% | 45% | 45% | - | - |
| T5 | - | - | 20% | 60% | 20% | - |
| T6 | - | - | - | 35% | 60% | 5% |

- Wyklucie tworzy `Pet` z losowym profilem wag staty (`stat_profile`,
  losowanym raz, `PetStatCalculator::rollStatProfile()`), poziomem 1,
  `growth_stage=0`, `fusion_count=0`.

## 3. Etapy Wzrostu (Growth Stage)

Każdy pet ma 4 wewnętrzne stopnie dojrzałości (0-3, `growth_stage`),
wyliczane z poziomu peta (`config('pets.growth_stage_thresholds')`: stage 0
od poz. 1, stage 1 od poz. 25, stage 2 od poz. 50, stage 3 "Forma Dorosła" od
poz. 75). Wizualnie mapowane na 3 warianty grafiki: stage 0 = "baby", stage
1-2 = "medium", stage 3 = "adult" (`Pet::spriteVariant()`).

## 4. Fuzja (dawna "Sokowirówka Dusz")

- Łączy **dokładnie 2 pety tego samego tieru** (nie 3) w 1 peta o tier
  wyższy. Oba pety niezałożone, bez aktywnej oferty na Rynku.
- Szansa sukcesu = baza per tier + bonus za dojrzałość obu petów:

| Fuzja | Baza | + oba "Forma Dorosła" (max bonus) |
|---|---|---|
| T1+T1→T2 | 80% | 100% |
| T2+T2→T3 | 65% | 85% |
| T3+T3→T4 | 50% | 70% |
| T4+T4→T5 | 35% | 55% |
| T5+T5→T6 | 20% | 40% |

  Bonus = `(growth_stage_A + growth_stage_B) * 3.3333%` (max 6 stopni razem
  = +20%). T6 nie może być już dalej fuzjonowany.
- **Oba pety wejściowe są zawsze zużywane**, niezależnie od wyniku.
- Sukces: nowy pet, tier+1, poziom 1, `growth_stage=0`,
  `fusion_count = max(fusion_count_A, fusion_count_B) + 1`.

## 5. Licznik Fuzji (`fusion_count`) - "+1/+2/..."

Niezależny od tieru licznik ile razy dany pet powstał z fuzji (0 dla
wyklutych bezpośrednio z jajka). Działa jak ulepszenie +0..+9 u Kowala:
- **+10% do puli staty** za każdy punkt (`PetStatCalculator::fusionMultiplier()`).
- **+10% do wymaganego EXP** na poziom za każdy punkt
  (`PetLevelCurve::requiredExp()`) - fuzjonowany pet jest silniejszy, ale
  drożej go wykarmić, co zniechęca do fuzjonowania świeżo wyklutych,
  niewykarmionych petów zamiast levelowania ich najpierw.

## 6. Norma Poziomowa i Statystyki

`PetStatCalculator::totalPool(tier, level, fusionCount)`:
```
baza(level) = level * 2.35
pula = baza(level) * norma_tieru * (1 + fusionCount * 0.10)
```
Pula rozdzielana na str/agi/int/vit wg `stat_profile` (wag wylosowanych przy
wykluciu/fuzji). Wynik zapisany w `Pet.stats`, przeliczany wyłącznie przez
`Pet::recalculateStats()` (wołane po karmieniu, fuzji, wykluciu).

## 7. Karmienie (Leveling & Feeding)

- Gracz karmi peta przedmiotami z plecaka (`PetFeedingService::feedPet()`).
- **Każdy przedmiot musi mieścić się w przedziale poziomowym tieru peta**
  (patrz tabela w sekcji 1, `PetTier::isItemLevelAccepted()`) - inaczej cała
  operacja jest odrzucana. Np. pet T5 (Epicki) przyjmuje wyłącznie przedmioty
  poziomu 60-80.
- EXP z przedmiotu: `max(1, level_requirement) * mnożnik_rzadkości`
  (common=1.0, uncommon=1.25, rare=1.5, epic=2.0, legendary=3.0).
- Wymagany EXP na poziom: `PetLevelCurve::requiredExp(level, fusionCount)` =
  `level * 100 * (1 + fusionCount * 0.10)`.
- Po awansie poziomu `Pet::recalculateStats()` przelicza staty i
  `growth_stage`.

## 8. Tłumienie Mocy wg Poziomu Postaci

Jeśli pet ma **wyższy poziom niż postać**, jego wkład do statystyk
(str/agi/int/vit) i Combat Power postaci jest **tłumiony proporcjonalnie**:
`mnożnik = min(1, poziom_postaci / poziom_peta)` (np. pet 100 lvl + postać 10
lvl = 10% mocy; postać 30 lvl = 30% mocy). Pet zawsze można założyć, karmić,
fuzjonować i sprzedać **niezależnie od poziomu** - ograniczenie dotyczy
wyłącznie wkładu do statystyk (`Pet::getEffectiveStatsFor()`,
`getCombatPowerFor()`). Ekwipunek peta (obroża/charm) NIE jest tłumiony -
dolicza się w pełni.

## 9. Ekwipunek Peta (Obroża / Charm)

Aktywny towarzysz ma **2 sloty ekwipunku**, niezależne od 6 slotów postaci:
- **`collar`** (obroża) - płaski bonus do staty.
- **`charm`** (charm/artefakt) - płaski bonus do staty ALBO powiększenie
  "stajni" (limitu posiadanych petów), zależnie od konkretnego przedmiotu.

Realizowane jako zwykłe `ItemInstance`/`ItemTemplate` (typy `pet_collar` /
`pet_charm`), sprzedawane u **Handlarza** jak reszta ekwipunku
(`database/seeders/PetSeeder.php`). Zakładanie/zdejmowanie:
`PetEquipmentService::equipGear()`/`unequipGear()` - zajęty slot jest
automatycznie zwalniany do plecaka przy podmianie. Przedmiotów startowych:
- `Skórzana Obroża` (+2 wszystkie staty), `Posrebrzana Obroża` (+5).
- `Amulet Feralnej Mocy` (+8 wszystkie staty), `Sakwa Chowańców` (+10 do
  limitu stajni zamiast staty, `Character::getPetStableCapacity()`).

## 10. Handel Chowańcami (Rynek)

Pety można wystawiać na tym samym **Rynku** co przedmioty
(`docs/modules/economy.md`) - nowa kolumna `market_listings.pet_id`:
- Warunki wystawienia: pet niezałożony jako aktywny towarzysz, oba sloty
  ekwipunku puste (`CreateMarketListingAction::executeForPet()`).
- Kupno/anulowanie: `BuyMarketListingAction`/`CancelMarketListingAction`
  rozgałęziają się na `pet_id`/`item_instance_id` - pet nigdy fizycznie nie
  "leży" na rynku, zmienia się tylko `character_id` przy zakupie.
- Brak ograniczenia poziomem przy zakupie/sprzedaży (patrz sekcja 8) - to
  świadoma decyzja, by pety wyższego tieru miały wartość rynkową jako
  długoterminowa inwestycja/"hodowla", a tłumienie mocy chroni balans PvE/PvP.
- UI: zakładka "Pety" na stronie Rynku (`resources/views/livewire/economy/market.blade.php`)
  z osobnymi filtrami (tier/poziom) zamiast filtrów statystyk itemowych.

## 11. Aktywny Towarzysz

Tylko **1 pet może być aktywnym towarzyszem** naraz
(`Pet.is_equipped`/`Character::activePet()` relacja) -
`PetEquipmentService::toggleActiveCompanion()` automatycznie zdejmuje
poprzedniego towarzysza. Tylko aktywny towarzysz dolicza staty/CP do postaci
(`Character::getTotalAttributes()`/`getTotalCombatPower()`).

## Rzeczy świadomie odłożone (kolejny etap)

- **Skille petów** - brak w tym reworku.
- **Rodzaje/gatunki petów** - `PetTemplate` zostaje jako nieużywana tabela;
  hatch generuje pety z losową nazwą/ikoną per tier, bez unikalnych gatunków
  (np. "Feniks" jako osobny, kolekcjonowalny gatunek).
