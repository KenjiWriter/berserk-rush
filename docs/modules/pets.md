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
  `PetLevelCurve.php`, `PetArchetype.php` (Rodzaj peta - patrz sekcja 7).
- Serwisy aplikacyjne: `app/Application/Pets/IncubatorService.php`,
  `PetFeedingService.php`, `PetFusionService.php`, `PetEquipmentService.php`,
  `PetSpeciesPicker.php` (jedno źródło prawdy dla nazwy/ikony/Rodzaju
  wyklutego/zfuzjonowanego peta - patrz sekcja 3).
- Modele: `app/Infrastructure/Persistence/Pet.php`,
  `CharacterIncubator.php`, `PetTemplate.php` (katalog gatunków - patrz
  sekcja 3). Panel admina: `app/Livewire/Admin/PetTemplates.php`
  ("Zarządzanie Zwierzakami").
- Stałe balansu: **`config/pets.php`** - jedyne źródło prawdy dla tierów,
  szans wyklucia, fuzji, karmienia i normy staty. Panel informacyjny w UI
  (przycisk "?" na stronie Petów) czyta te same stałe, więc nigdy nie
  rozjeżdża się z realną logiką.

## 1. Tiery

| Tier | Nazwa | Czas wyklucia | Norma poziomowa | Karmienie od poz. |
|---|---|---|---|---|
| T1 | Pospolity | 1h | 100% | 0+ |
| T2 | Zwykły | 1.5h | 130% | 15+ |
| T3 | Nietypowy | 2h | 160% | 30+ |
| T4 | Rzadki | 2.5h | 190% | 45+ |
| T5 | Epicki | 3h | 220% | 60+ |
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
  `growth_stage=0`, `fusion_count=0`. Nazwa i ikona pochodzą z puli gatunków
  danego tieru - patrz sekcja 3.

## 3. Gatunki Chowańców (`PetTemplate`) i Rodzaje (Archetypy)

Każdy gatunek chowańca należy do jednego z **3 Rodzajów** (`archetype`),
które określają jego pasywkę bojową (patrz sekcja 7). Pula domyślnie
seedowana (`database/seeders/PetSeeder.php`) to **18 gatunków** = 3 Rodzaje ×
6 tierów:

| Tier | Atakujący | Obrony | Wspomagający |
|---|---|---|---|
| T1 Pospolity | Dziki Kot | Żółw | Świetlik |
| T2 Zwykły | Goblin Rozpruwacz | Kamienny Chrząszcz | Leśny Duszek |
| T3 Nietypowy | Cienisty Wilk | Kościany Strażnik | Wróżka Księżycowa |
| T4 Rzadki | Ognisty Gryf | Runiczny Golem | Pegaz |
| T5 Epicki | Demoniczna Mantykora | Nieumarły Behemot | Feniks Odrodzenia |
| T6 Legendarny | Smok Pustki | Tytan Wieczności | Serafin Przeznaczenia |

- Nazwa, ikona i Rodzaj wyklutego/zfuzjonowanego peta losowane są **razem** z
  puli `PetTemplate` **dla wylosowanego/wynikowego tieru**
  (`PetSpeciesPicker::pick()` - jedyne miejsce, którego używają zarówno
  `IncubatorService`, jak i `PetFusionService`, żeby nazwa/ikona/Rodzaj peta
  nigdy się nie rozjechały w trakcie fuzji). Staty chowańca NADAL liczy
  wyłącznie norma poziomowa (sekcja 7) - `base_stats` na szablonie gatunku
  jest obecnie tylko kosmetyczne/nieużywane przy hatchu.
- Zarządzanie gatunkami: panel admina "Zarządzanie Zwierzakami"
  (`app/Livewire/Admin/PetTemplates.php`) - formularz zawiera pole "Rodzaj"
  (select attacker/defense/support/brak). Dodanie gatunku z tierem 1-6
  sprawia, że może on realnie wypaść przy wykluciu/fuzji tego tieru.
- **Ikony to nazwy plików obrazów** w `public/assets/items/` (np.
  `pet_wolf`, `pet_dragon` - bez rozszerzenia, bez emoji), renderowane przez
  `route('assets.items', ['filename' => ...])`, tak samo jak ikony
  przedmiotów.
- `/give pet <nazwa>` na czacie globalnym (GM) również korzysta z tej samej
  tabeli `PetTemplate` (dopasowanie po nazwie, tier i Rodzaj brane wprost z
  rekordu) - jawnie ustawia `fusion_count`/`growth_stage`/`stat_profile`, żeby
  uniknąć crasha na `null` przy pierwszym przeliczeniu staty.

## 4. Etapy Wzrostu (Growth Stage)

Każdy pet ma 4 wewnętrzne stopnie dojrzałości (0-3, `growth_stage`),
wyliczane z poziomu peta (`config('pets.growth_stage_thresholds')`):

| Stage | Nazwa | Od poziomu | Mnożnik puli staty |
|---|---|---|---|
| 0 | Pisklak | 1 | ×1.00 |
| 1 | Podrostek | 10 | ×1.10 |
| 2 | Okrzepły | 25 | ×1.22 |
| 3 | Forma Dorosła | 50 | ×1.35 |

Każdy próg to realny "skok" atrybutów (`config('pets.growth_stage_stat_multiplier')`,
`PetGrowthStage::statMultiplier()`), nie tylko zmiana nazwy/grafiki - patrz
wzór w sekcji 8. Wizualnie stopnie mapowane są na 3 warianty grafiki: stage 0
= "baby", stage 1-2 = "medium", stage 3 = "adult" (`Pet::spriteVariant()`).

## 5. Fuzja (dawna "Sokowirówka Dusz")

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
- **Każda próba kosztuje złoto** zależne od tieru wejściowego
  (`config('pets.fusion_cost_gold')`), pobierane niezależnie od wyniku
  (ledger `pet_fusion_cost`):

| Tier wejściowy | T1→T2 | T2→T3 | T3→T4 | T4→T5 | T5→T6 |
|---|---|---|---|---|---|
| Koszt | 100 | 250 | 500 | 1000 | 2000 |

- Sukces: **oba pety wejściowe są zużywane**, powstaje nowy pet (nowy
  gatunek/Rodzaj losowany dla tieru+1, `PetSpeciesPicker`), poziom 1,
  `growth_stage=0`, `fusion_count = max(fusion_count_A, fusion_count_B) + 1`.
- **Porażka nie oznacza już automatycznej utraty obu petów.** Wynik porażki
  jest **losowany** wg wagi z `config('pets.fusion_failure_outcomes')` -
  gracz nigdy nie wybiera wariantu, tylko widzi jego skutek po fakcie:

| Wariant porażki | Szansa | Efekt |
|---|---|---|
| `no_loss` | 43% | Brak utraty petów, tylko koszt fuzji przepada. |
| `devolve_one` | 25% | Jeden (losowy) pet cofa się w rozwoju (`Pet::demoteGrowthStage()` - poziom spada tuż poniżej progu obecnego etapu, EXP resetowany). |
| `devolve_both` | 20% | Oba pety cofają się w rozwoju. |
| `lose_one` | 10% | Jeden (losowy) pet ulega rozproszeniu (usunięty), drugi przetrwa nietknięty. |
| `lose_both` | 2% | Oba pety ulegają rozproszeniu (zachowanie sprzed reworku, teraz najrzadszy wynik). |

  Implementacja: `PetFusionService::rollFailureOutcome()` (kumulatywny
  weighted roll przez `RandomProvider`), `Pet::demoteGrowthStage()`.
- UI: wynik fuzji (sukces / każdy z 5 wariantów porażki, z osobną kolorystyką
  i ikoną) pokazywany jest w dedykowanym modalu (`$fusionResultModal` w
  `PetsComponent`), razem z kosztem i szansą powodzenia - nie tylko jako
  prosty toast sukces/porażka.

## 6. Licznik Fuzji (`fusion_count`) - "+1/+2/..."

Niezależny od tieru licznik ile razy dany pet powstał z fuzji (0 dla
wyklutych bezpośrednio z jajka). Działa jak ulepszenie +0..+9 u Kowala:
- **+10% do puli staty** za każdy punkt (`PetStatCalculator::fusionMultiplier()`).
- **+10% do wymaganego EXP** na poziom za każdy punkt
  (`PetLevelCurve::requiredExp()`) - fuzjonowany pet jest silniejszy, ale
  drożej go wykarmić, co zniechęca do fuzjonowania świeżo wyklutych,
  niewykarmionych petów zamiast levelowania ich najpierw.

## 7. Pasywka Rodzaju (Archetyp)

Aktywny towarzysz, który powstał z fuzji (`fusion_count > 0`) i ma
przypisany Rodzaj, daje **pasywny bonus bojowy w każdym z 4 silników walki**
(PvE mapa, dungeony, PvP, GvG):

```
bonus% = fusion_count * fusion_count_archetype_bonus_percent(1) * tier
```

tłumiony tym samym mnożnikiem poziomu postaci/peta co reszta staty (sekcja
9). Efekt zależy od Rodzaju gatunku (sekcja 3):

| Rodzaj | Efekt |
|---|---|
| Atakujący | +bonus% do `attack_min/max` i `magic_attack_min/max` (mnożnikowo). |
| Obrony | +bonus% do `defense` i `hp_bonus` (mnożnikowo). |
| Wspomagający | +bonus punktów procentowych do `dodge_chance` ORAZ redukcja kosztu many umiejętności o `bonus%` (addytywnie, capped na 90%). |

Implementacja: `Pet::getArchetypeBonusPercentFor(Character)`, zastosowane w
`Character::getEquipmentStats()` na samym końcu (mnożnikowo/addytywnie do już
zsumowanego wkładu ekwipunku+buffów+tytułu+osiągnięć), więc propaguje się
automatycznie do `getMaxHp()`/`getMaxMana()`/`createSnapshot()` bez osobnych
zmian w każdym silniku walki. Redukcja kosztu many jest odczytywana jawnie z
`equipment_stats['mana_cost_reduction_pct']` w `CharacterCombatSkill::getManaCost()`
(PvE/dungeony) oraz w `PvPEncounterService`/`GuildWarService`
(`applyPetManaReduction()` na zamrożonej migawce postaci).

## 8. Norma Poziomowa i Statystyki

`PetStatCalculator::totalPool(tier, level, fusionCount)`:
```
baza(level) = level * 1.175
etap = PetGrowthStage::forLevel(level)   // patrz sekcja 4
pula = baza(level) * norma_tieru * (1 + fusionCount * 0.10) * mnożnik_etapu(etap)
```
(Wartość bazowa obniżona z `2.35` do `1.175` w ramach rebalansu 2026-08 - pety
dawały nieproporcjonalnie dużo staty względem postaci na tym samym poziomie;
`mnożnik_etapu` dokłada realne "skoki" przy progach etapów wzrostu, patrz
sekcja 4.) Pula rozdzielana na str/agi/int/vit wg `stat_profile` (wag
wylosowanych przy wykluciu/fuzji). Wynik zapisany w `Pet.stats`, przeliczany
wyłącznie przez `Pet::recalculateStats()` (wołane po karmieniu, fuzji,
wykluciu).

## 9. Karmienie (Leveling & Feeding)

- Gracz karmi peta przedmiotami z plecaka (`PetFeedingService::feedPet()`).
- **Każdy przedmiot musi mieć poziom wymagany co najmniej równy minimum
  tieru peta** (patrz tabela w sekcji 1, `PetTier::isItemLevelAccepted()`) -
  **brak górnej granicy**, więc mocniejszy (wyższy poziom) przedmiot zawsze
  można skarmić petu niższego tieru (np. pet T1 może zjeść legendarny
  przedmiot poziomu 99) - inaczej cała operacja karmienia jest odrzucana.
- EXP z przedmiotu: `max(1, level_requirement) * mnożnik_rzadkości`
  (common=1.0, uncommon=1.25, rare=1.5, epic=2.0, legendary=3.0).
- Wymagany EXP na poziom: `PetLevelCurve::requiredExp(level, fusionCount)` =
  `level * 100 * (1 + fusionCount * 0.10)`.
- Po awansie poziomu `Pet::recalculateStats()` przelicza staty i
  `growth_stage`.

## 10. Tłumienie Mocy wg Poziomu Postaci

Jeśli pet ma **wyższy poziom niż postać**, jego wkład do statystyk
(str/agi/int/vit), pasywki Rodzaju (sekcja 7) i Combat Power postaci jest
**tłumiony proporcjonalnie**: `mnożnik = min(1, poziom_postaci / poziom_peta)`
(np. pet 100 lvl + postać 10 lvl = 10% mocy; postać 30 lvl = 30% mocy). Pet
zawsze można założyć, karmić, fuzjonować i sprzedać **niezależnie od
poziomu** - ograniczenie dotyczy wyłącznie wkładu do statystyk
(`Pet::getEffectiveStatsFor()`, `getCombatPowerFor()`,
`getArchetypeBonusPercentFor()`). Ekwipunek peta (obroża/charm) NIE jest
tłumiony - dolicza się w pełni.

## 11. Ekwipunek Peta (Obroża / Charm)

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

## 12. Handel Chowańcami (Rynek)

Pety można wystawiać na tym samym **Rynku** co przedmioty
(`docs/modules/economy.md`) - nowa kolumna `market_listings.pet_id`:
- Warunki wystawienia: pet niezałożony jako aktywny towarzysz, oba sloty
  ekwipunku puste (`CreateMarketListingAction::executeForPet()`).
- Kupno/anulowanie: `BuyMarketListingAction`/`CancelMarketListingAction`
  rozgałęziają się na `pet_id`/`item_instance_id` - pet nigdy fizycznie nie
  "leży" na rynku, zmienia się tylko `character_id` przy zakupie.
- Brak ograniczenia poziomem przy zakupie/sprzedaży (patrz sekcja 10) - to
  świadoma decyzja, by pety wyższego tieru miały wartość rynkową jako
  długoterminowa inwestycja/"hodowla", a tłumienie mocy chroni balans PvE/PvP.
- UI: zakładka "Pety" na stronie Rynku (`resources/views/livewire/economy/market.blade.php`)
  z osobnymi filtrami (tier/poziom) zamiast filtrów statystyk itemowych.

## 13. Aktywny Towarzysz

Tylko **1 pet może być aktywnym towarzyszem** naraz
(`Pet.is_equipped`/`Character::activePet()` relacja) -
`PetEquipmentService::toggleActiveCompanion()` automatycznie zdejmuje
poprzedniego towarzysza. Tylko aktywny towarzysz dolicza staty/CP do postaci
(`Character::getTotalAttributes()`/`getTotalCombatPower()`).

## Rzeczy świadomie odłożone (kolejny etap)

- **Skille petów** (aktywne umiejętności użyteczne w walce, nie tylko
  pasywka Rodzaju) - brak w tym reworku.
- **Kitsune** (specjalny pet łagodzący skutki nieudanej fuzji, 5/10/15%
  poziomu → 10/20/30% szansy na złagodzenie) - świadomie odłożone, poza
  zakresem obecnego reworku (tylko 3 archetypy × 6 tierów).
- **Unikalne staty per gatunek** - `PetTemplate.base_stats` na razie nie
  wpływa na realne staty wyklutego peta (te liczy wyłącznie norma poziomowa,
  sekcja 8) - gatunek wpływa tylko na nazwę/ikonę/Rodzaj (pasywkę), nie na
  bazowe staty.
