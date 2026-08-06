# Moduł Mistrzostwa (Champion Level)

Moduł odpowiada za endgame'ową progresję postaci, która osiągnęła maksymalny (99) poziom. Gracz zdobywa **poziomy championa** (wyświetlane jako `99(X)`) łącząc dwa jednoczesne wymogi - pełny pasek expa (ten sam pasek co zwykłe levelowanie) oraz dostarczenie losowo wymaganych materiałów Czarnoksiężnikowi - i wydaje zdobyte **Punkty Mistrzostwa (PK)** w uniwersalne drzewko 10 pasywnych statystyk.

## Implementacja
- **Modele i Encje:** `App\Infrastructure\Persistence\ChampionSkill`, `App\Infrastructure\Persistence\CharacterChampionSkill`.
- **Serwis:** `App\Application\Mastery\ChampionService` - cała logika (próg expa, losowanie ulepszaczy, donacje, awans, inwestowanie punktów, reset).
- **Zdarzenie:** `App\Domain\Mastery\Events\ChampionLeveledUp`.
- **Komponenty Livewire:**
  - `App\Livewire\City\Warlock` (zakładka "Mistrzostwo", widoczna obok "Umiejętności" tylko gdy `character.level >= 99`)
  - `App\Livewire\Admin\ChampionSkills` (panel administracyjny `/admin/champion-skills`)
- **Kolumny na `characters`:** `champion_level` (int, cap 50), `champion_points` (int, niewydane PK), `champion_material_progress` (jsonb, aktualnie wymagany zestaw ulepszaczy), `last_champion_reset_at` (timestamp). **Brak osobnej kolumny na expa championa** - używana jest istniejąca kolumna `xp` (patrz pkt 2).
- **Kolumna na `item_templates`:** `source_map_tier` - nullable, backfillowana jednorazowo w migracji `2026_08_06_122000_add_source_map_tier_to_item_templates_table.php` przez join `loot_table_entries -> monsters -> maps`, mówi z jakiego tieru mapy pochodzi dany materiał.
- **Pasek EXP w UI:** Wszystkie miejsca liczące "ile expa do następnego progu" muszą być świadome Mistrzostwa (poniżej 99 poziomu - zwykłe `xpToNext()`, na 99 poziomie - `ChampionService::xpTarget()`): `resources/views/components/desktop-nav.blade.php` i `mobile-nav.blade.php` (boczny/dolny pasek postaci, PHP + fallback JS przy generycznych `stats-updated` bez `experience_required`), `resources/views/livewire/city/profile.blade.php` (pasek w profilu), `App\Livewire\Global\RewardInfobox.php` (toast nagrody).

---

## Mechaniki

### 1. Odblokowanie
Zakładka "Mistrzostwo" u Czarnoksiężnika pojawia się automatycznie, gdy `character.level >= 99` - nie wymaga osobnego questa/akcji odblokowującej.

### 2. Pasek Expa - JEDEN wspólny licznik
Zamiast osobnego pola na expa championa, gracz na 99 poziomie dalej odmierza postęp tym samym `character.xp`, co przy zwykłym levelowaniu (ten sam pasek widoczny w bocznym panelu i profilu) - żadne expa nie przepadają, po prostu zmienia się próg "100%":
- **Poniżej 99 poziomu:** limit `xp` to zwykły `xpToNext(poziom)` z krzywej XP (bez zmian).
- **Na 99 poziomie:** limit `xp` to `ChampionService::xpTarget()` - suma CAŁEGO expa wymaganego na poziomy 1-99 (`sum(LevelUpService::xpToNext(1..98))`), znacznie wyższa niż zwykły `xpToNext(99)`. Ten próg jest **STAŁY** dla każdego kolejnego poziomu championa (99(0)->99(1), 99(1)->99(2), itd.), liczony w locie z aktualnej krzywej XP (bez hardkodowania, automatycznie podąża za rebalansami).
- `LevelUpService::getMaxLevelXpCap()` zwraca właściwy limit w zależności od poziomu; egzekwowany jest centralnie w `LevelUpService::checkAndApply()` (przez które przechodzą wszystkie ścieżki nagradzania expem - PvE, Dungeony, Questy, Osiągnięcia, Lustro...) oraz defensywnie w `Character::booted()` (`saving` hook) dla innych ścieżek zapisu. **Bez "-1"** w limicie (w przeciwieństwie do zwykłych poziomów) - licznik musi móc dotrzeć DOKŁADNIE do progu, bo awans championa to ręczna akcja gracza (`attemptLevelUp()`), nie automatyczna pętla jak przy zwykłym levelowaniu.
- Po awansie championa `xp` resetuje się do 0 (dokładnie jak przy zwykłym poziomie).

### 3. Ulepszacze (Tribute Materials)
- Po odblokowaniu Mistrzostwa (i po każdym kolejnym awansie championa, dopóki `champion_level < 50`) `ChampionService::rollMaterialRequirements()` losuje nowy zestaw materiałów sumujący się do **1000 sztuk**:
  1. Losuje 3-5 unikalnych tierów map spośród dostępnych (`ItemTemplate.source_map_tier`, obecnie 1-8).
  2. Waga tieru = `maxTier + 1 - tier` - niższy tier (łatwiej dostępny) dostaje wyższą wagę = więcej sztuk wymaganych, tak by materiały z niższych map miały znaczenie w endgame.
  3. Dla każdego wylosowanego tieru wybiera losowo 1 materiał z puli tego tieru (materiały typu `key`/klucze do lochów są wykluczone z puli).
- Gracz wpłaca materiały przyciskiem "Wpłać" w UI (`Warlock::donateMaterial()` -> `ChampionService::donateMaterial()`), który konsumuje przedmioty z ekwipunku/skrzyni materiałów (analogicznie do `UpgradeSkill::consumeStackedItems()`) i aktualizuje `deposited` w `champion_material_progress`.

### 4. Awans Championa
`ChampionService::attemptLevelUp()` wymaga JEDNOCZEŚNIE:
- `character.xp >= xpTarget()`, ORAZ
- wszystkich wpisów w `champion_material_progress` z `deposited >= required`.

Po spełnieniu obu warunków gracz klika "Awansuj Championa": `champion_level++`, `champion_points++` (1 PK), `xp` resetuje się do 0, a jeśli `champion_level < LEVEL_CAP` losowany jest nowy zestaw ulepszaczy na kolejny poziom. Emitowane jest zdarzenie `ChampionLeveledUp`.

**Cap: poziom championa maksymalnie 50** (`ChampionService::LEVEL_CAP`) - odpowiada dokładnie puli 50 PK możliwych do wydania w drzewku (10 umiejętności x max 10 pkt każda, ale tylko 50 PK do rozdania łącznie - gracz musi wybrać, które umiejętności maksować).

### 5. Drzewko Rozwoju (10 Umiejętności)
Zaseedowane w `database/seeders/ChampionSkillSeeder.php`, edytowalne w `/admin/champion-skills`:

| Umiejętność | Bonus/PKT | Wpięcie w kod |
|---|---|---|
| Siła | +1% obrażeń fizycznych | `calculateDamage()`/`resolveHitAgainstTarget()` w każdym z 4 silników walki |
| Mądrość | +1% obrażeń magicznych | jw. (gated na `is_magic`/broń Różdżka) |
| Wytrzymałość | +2% max HP | `Character::getMaxHp()` (jedno miejsce, używane wszędzie) |
| Koncentracja | +1% regeneracji many | wszystkie miejsca regeneracji many/turę w 4 silnikach |
| Celność | +1% szansy trafienia | kontruje unik przeciwnika przy ataku gracza (`rollDodge()` / odpowiedniki) |
| Zwinność | +1% uniku | własny unik gracza przy obronie (`rollDodge()` / odpowiedniki) |
| Twardziel | -1% otrzymywanych obrażeń | scalone z `defenseBuffValue` (`buff_defense`) w 4 silnikach |
| Fortuna | +2% złota z potworów | `RewardMultiplierService::getGoldMultiplier()` |
| Łowca Skarbów | +1% szansy na lepszy łup | `DropService::rollForMonster()` (próg dropu) |
| Wiedza | +2% doświadczenia | `RewardMultiplierService::getExpMultiplier()` |

Punkty inwestowane są przez `Character::getChampionSkillPoints()`/`getChampionBonusPercent()` (cache 1h, czyszczony w `clearChampionCache()`/`clearStatsCache()`). Bonusy naliczane są WYŁĄCZNIE dla postaci na poziomie 99 (`getChampionBonusPercent()` zwraca 0 poniżej tego poziomu), więc nie trzeba osobno pilnować cap-u przy odczycie.

**Synchronizacja między silnikami walki:** Podobnie jak inne mechaniki bojowe w tym projekcie (patrz `docs/modules/skills.md` pkt 9), `EncounterService` (PvE), `PvPEncounterService` (Arena), `GuildWarService` (5v5) i `DungeonService` (Lochy) to osobne, zduplikowane implementacje - bonusy z Mistrzostwa zostały wpięte we wszystkie 4 na raz. `PvPEncounterService`/`GuildWarService` operują na snapshotach (`Character::createSnapshot()`), więc bonusy doliczane są RAZ przed symulacją walki (`buildChampionBonuses()`) zamiast odpytywać bazę co turę.

### 6. Reset Drzewka
`ChampionService::resetSkills()` - koszt **100 000 000 (100kk) złota**, dostępny **raz na miesiąc** (`last_champion_reset_at`, blokada `diffInMonths(now()) < 1`). Zwraca wszystkie zainwestowane punkty do ponownego rozdania (`champion_points = champion_level`).
