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

### 3a. Prezentacja Statystyk w Tooltipie Przedmiotu (Info Box)
- **Prezentacja Ataku Broni:** W info boxie przedmiotu (`x-item-tooltip`) statystyki broni (fizyczne `attack_min`/`attack_max`, magiczne `magic_attack_min`/`magic_attack_max` oraz `magic_burst_min`/`magic_burst_max`) są skonsolidowane w pojedynczy wiersz: `Atak: <min>-<max>` (np. `120-180`).
- **Bonus z Ulepszeń (Kowal):** Gdy przedmiot posiada poziom ulepszenia (`+1` do `+9`), dodatkowy bonus z ulepszenia dla wartości min/max jest wyświetlany w osobnej linii pod spodem w formacie `(+<up_min>-+<up_max>)` w kolorze bursztynowym.
- **Porównywanie Sprzętu (Compare):** W trybie porównywania z założoną bronią wyświetlany jest zakres różnicy statystyk `(+<diff_min>-+<diff_max>)` lub straty, a prawa strona podglądu założonego przedmiotu stosuje ten sam spójny format.
- **Pochodzenie Materiałów w Plecaku/Magazynie:** Tooltip przedmiotu (`x-item-tooltip`) dla materiałów (`type === 'material'`) wyświetlanych w plecaku oraz w magazynie materiałów pokazuje sekcję "Wypada z" z listą potworów i krain, z których dany surowiec można zdobyć (np. "Wilk Leśny · Mroczny Las"). Źródła łupów są zbierane zbiorczo w `Profile::render()` (`$materialDropSources`, zapytanie do `LootTableEntry` z eager-loadem `lootTable.monsters.map`) i przekazywane do komponentu jako opcjonalny prop `dropSources`.


### 4. Rozwój Atrybutów
Postać zdobywa punkty postaci (`character_points`) za każdy zdobyty poziom (np. +3 punkty za każdy awans).
Z poziomu widoku Profilu gracz może ręcznie przydzielać zdobyte punkty do swoich głównych statystyk (STR, INT, VIT, AGI):
- Mechanika pozwala na szybkie dodawanie punktów pojedynczo (`+1`) lub po pięć sztuk naraz (`+5`).
- UI używa animowanych wskaźników (pulse) dla nieprzydzielonych punktów i interaktywnych dymków (tooltipów) z informacją o działaniu konkretnej statystyki.
- **Resetowanie Atrybutów:** Gracz ma możliwość zresetowania rozdanych atrybutów dla swoich postaci z poziomu Sklepu Premium (`ItemShopComponent`) za 50 Gemów. Reset zeruje przydzielone statystyki i zwraca całą pulę punktów (`character_points` = 10 + (poziom - 1) * 3) do ponownego rozdysponowania.

> **Uwaga (rework itemizacji, 2026-07-28, poranek):** STR/INT/VIT/AGI były przez
> chwilę wyłącznie statystykami rozdawanymi ręcznie przez gracza (oraz bonusami z
> tytułów/osiągnięć) - broń i zbroja nie dodawały już do nich żadnych bonusów, dając
> zamiast tego wyłącznie obrażenia fizyczne/magiczne, obronę, HP i szansę na
> trafienie krytyczne. Jedynym wyjątkiem była biżuteria (patrz niżej). **Ta decyzja
> została częściowo cofnięta tego samego dnia** - patrz kolejna notatka.
>
> **Uwaga (itemizacja klasowa, 2026-07-28, popołudnie):** Zestawy zbroi (hełm/klatka/
> buty) z dropu/craftingu (`ItemTemplateSeeder.php`, sufiksy `_w`/`_m`/`_a`) ZNOWU
> dają surowe atrybuty, tym razem tematycznie per "klasa" ekwipunku:
> - **Wojownik** (`_w` - miecz/topór/łuk, zbroja płytowa) -> **STR + VIT** (50/50).
> - **Mag** (`_m` - różdżka/dzwon, szaty) -> **INT**.
> - **Skrytobójca / Ninja** (`_a` - sztylet, skóry) -> **AGI**.
>
> Bronie NADAL nie dają atrybutów (tylko obrażenia/crit, jak w reworku z rana) -
> tylko 3-częściowy zestaw zbroi per klasa. Skalowanie jest **liniowe względem
> poziomu przedmiotu** (nie multiplikatywne jak `scale` przy obrażeniach): od 12 pkt
> danego atrybutu na tier `poziom 5`, do 200 pkt na tier `poziom 85` (najlepszy
> zestaw możliwy do założenia na poziomie postaci 90, bo kolejny tier wymaga
> poziomu 95), aż do 233 pkt na endgame'owym tierze `poziom 99`.
>
> Budżet ręcznych punktów postaci (`character_points`) na max poziomie (99) wynosi
> **10 + 98 * 3 = 304** (nie zaokrąglone "307" - patrz wzór w sekcji 4 wyżej). Pełny
> zestaw klasowy na poziomie 90 (tier 85) daje więc ok. **40% tyle, co cała ręczna
> pula** (200 z 200+304), a na endgame'owym tierze 99 - ok. **43%** (233 z 233+304).
> Ręczne rozdawanie punktów zostaje więc zawsze głównym źródłem atrybutów, a
> ekwipunek klasowy - wyraźnym, ale nie dominującym bonusem, przez co warto zarówno
> levelować/rozdawać punkty, jak i kompletować pasujący zestaw klasowy (np. zestaw
> Skrytobójcy realnie podbija AGI, więc unik/krytyk/inicjatywę w walce - patrz
> `docs/modules/combat.md`). Ulepszanie (+0..+9 w Kowalu) dolicza swoje standardowe
> +10%/poziom do KAŻDEGO dodatniego stata w `base_stats`, więc w pełni wykuty zestaw
> daje dodatkowo do +90% ponad te wartości - identycznie jak przy innych statach.
>
> **Jedynym wyjątkiem POZA systemem klasowym** pozostaje biżuteria (naszyjnik/
> pierścień w slotach `neck`/`ring`) - część egzemplarzy, zwłaszcza tych
> wytworzonych w rzemiośle lub zaklętych u Wiedźmy, sporadycznie nadal daje
> niewielki, płaski bonus do LOSOWEGO jednego atrybutu (+1 do +5, niezależnie od
> klasy/poziomu/rzadkości przedmiotu - to nie skaluje się jak zestawy klasowe).
> Zestawy sklepowe (`ShopEquipmentSeeder.php`) pozostają uniwersalne/bezklasowe i
> nadal nie dają żadnych atrybutów.
>
> `Character::getTotalAttributes()` nadal generycznie sumuje klucze `*_bonus` z
> ekwipunku, więc cały ten mechanizm działa "za darmo" - nie wymagał zmian w kodzie
> postaci, tylko w tym, co seeder faktycznie przydziela przedmiotom
> (`$classArmorAttributes` w `ItemTemplateSeeder.php`).

### 5. Zestawy Ekwipunku (Arena PvP / Wojna Gildii / Set 1-2-3)

Postać może zapisać do **5 niezależnych, wirtualnych zestawów** ekwipunku,
niezależnych od tego, co jest aktualnie fizycznie założone:
- **`pvp`** ("Arena PvP") - używany wyłącznie do wyliczenia snapshotu OBROŃCY,
  gdy ktoś wyzywa naszą postać na Arenie (patrz `docs/modules/pvp_and_arena.md`).
  Atakujący zawsze walczy swoim aktualnie założonym gearem.
- **`guild_war`** ("Wojna Gildii") - używany dla OBU stron (atak i obrona)
  podczas rozstrzygania starcia 5v5 (patrz `docs/modules/guilds.md`).
- **`set_1`** - dostępny dla każdego gracza.
- **`set_2`** / **`set_3`** - wymagają Premium (`User::hasPremium()`), analogicznie
  do limitu plecaka.

**Model danych:** tabela `character_equipment_set_items` (`character_id`,
`set_type`, `slot`, `item_instance_id`, unikalny indeks na trójce
`character_id`+`set_type`+`slot`) - model `CharacterEquipmentSetItem`. Zestawy
NIE poruszają fizycznie przedmiotów (poza jawnym "Załóż zestaw" dla
set_1/2/3) - to czysto wirtualny zapis "który przedmiot w którym slocie".

**Zapisywanie / zakładanie (`EquipmentSetService`):**
- `saveCurrentAsSet()` - zapisuje to, co postać ma AKTUALNIE założone, jako
  dany zestaw (nadpisuje poprzednią zawartość).
- `applySet()` - **tylko dla `set_1`/`set_2`/`set_3`** - fizycznie zamienia
  bieżący ekwipunek na zapisany (swap slot-po-slocie, jak przy zwykłym
  zakładaniu). `pvp`/`guild_war` nigdy nie są "zakładane" (błąd `NOT_WEARABLE`).

**Fallback per-slot:** gdy wyliczenia bojowe pytają o zestaw (`pvp`/`guild_war`),
a dany slot nie jest skonfigurowany, albo zapisany przedmiot już nie należy do
postaci (sprzedany, przekazany dalej) - TYLKO ten pojedynczy slot spada na
aktualnie założony przedmiot, reszta zestawu działa normalnie
(`Character::resolveEffectiveEquipment()`). Jeśli cały zestaw jest pusty,
efektywnie odpowiada to aktualnie założonemu ekwipunkowi.

**UI:** pasek 5 przycisków nad slotami w `profile.blade.php` (Arena PvP /
Wojna Gildii / Set 1 / Set 2 / Set 3, kłódka gdy brak Premium) - rozwijane
menu z podglądem zapisanych przedmiotów oraz przyciskami "Zapisz aktualny
ekwipunek" i (tylko dla setów do noszenia) "Załóż ten zestaw".
