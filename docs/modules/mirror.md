# Moduł Lustra (Mirror)

System Lustra pozwala postaci farmić EXP/złoto/materiały "w tle", bez
faktycznego stoczenia walki, na podstawie realnego tempa zdobywania
zasobów, jakie postać już wcześniej wykazała na danej mapie w trybie
Przygody. To mechanika typu multitasking - podczas gdy Lustro jest
aktywne, gracz może zajmować się innymi rzeczami (Dungeony, Kuźnia,
Arena), a zwykłe Mapy Przygody są zablokowane.

## 1. Odblokowanie i dostęp

- **Poziom 30**: zakładka "Lustro" w Profilu oraz zakładka "Lustro" u
  Wiedźmy są zablokowane dla postaci poniżej 30 poziomu (patrz
  `docs/modules/profile_and_equipment.md` §6 i `docs/modules/tutorial.md`,
  sekcja o etapach 40-43).
- **Zakup dostępu u Wiedźmy**: samo osiągnięcie 30 poziomu NIE wystarcza,
  aby uruchamiać sesje Lustra. Trzeba wykupić czasowy dostęp w Chacie
  Wiedźmy (zakładka `mirror`, `Witch.php`/`witch.blade.php`) za:
  - **5 000 000 złota**, lub
  - **200 gemów**

  Zakup daje **7 dni dostępu** liczone od momentu zakupu. Jeśli dostęp już
  trwa, kolejny zakup **dolicza** kolejne 7 dni do istniejącego terminu,
  zamiast go resetować. Dostęp jest przechowywany per-postać w kolumnie
  `characters.mirror_access_until` (nullable timestamp, analogicznie do
  `users.premium_until` / `User::hasPremium()`), a sprawdzany przez
  `Character::hasMirrorAccess(): bool`.
- Sam zakup dostępu **nie uruchamia** sesji Lustra - jedynie odblokowuje
  możliwość jej wystartowania. Wybór mapy i czasu trwania odbywa się nadal
  w zakładce "Lustro" w Profilu, tak jak wcześniej.

## 2. Model danych

- **`character_map_mirror_stats`** (`CharacterMapMirrorStat`) - dla każdej
  pary postać+mapa przechowuje najlepsze zaobserwowane tempo:
  `max_exp_per_minute`, `max_gold_per_minute`. Aktualizowane po każdej
  walce w trybie Przygody (`MapStub::applyRewards()` →
  `MirrorService::updateMapRates()`), tylko jeśli sesja walki trwała
  minimum 15 sekund.
- **`character_mirror_sessions`** (`CharacterMirrorSession`) - pojedyncza
  sesja Lustra: `map_id`, `duration_hours`, `exp_per_minute`,
  `gold_per_minute` (zamrożone w momencie startu z `character_map_mirror_stats`),
  `started_at`, `ends_at`, `status` (`active` / `claimed` / `cancelled`).
- **`characters.mirror_access_until`** (nullable timestamp) - termin
  ważności wykupionego dostępu do funkcji Lustra (niezależny od
  pojedynczej sesji).

## 3. `MirrorService` (`app/Application/Mirror/MirrorService.php`)

- `getMapRates()` / `updateMapRates()` - odczyt/zapis zaobserwowanego tempa
  na danej mapie.
- `purchaseAccess(Character $character, string $currencyType)` - waliduje
  30 poziom i saldo (`gold` lub `user->gems`), pobiera walutę (transakcja
  DB + wpis w `CurrencyLedger`, `source_type = 'mirror_access'`), i
  ustawia/przedłuża `mirror_access_until` o 7 dni.
- `startMirror(Character $character, Map $map, int $durationHours)` -
  wymaga: wykupionego dostępu (`hasMirrorAccess()`), braku innej aktywnej
  sesji, dostępności mapy dla poziomu postaci, istniejącego rekordu tempa
  na wybranej mapie. Czas trwania: 1-6h, lub 1-10h dla `User::hasPremium()`.
  Zapisuje sesję ze statusem `active` i zamrożonym tempem.
- `stopAndClaim(Character $character)` - liczy upływ czasu (capowany do
  zadeklarowanego `duration_hours`), przyznaje **60% zaobserwowanego
  tempa** jako XP/złoto (z obsługą level-upu przez `LevelUpService`) oraz
  materiały (1 rzut co 15 minut z tabeli łupów mapy lub fallbackowo wg
  poziomu), oznacza sesję jako `claimed`.

## 4. UI

- **Profil → zakładka "Lustro"** (`Profile.php` / `profile.blade.php`):
  zablokowana wizualnie i funkcjonalnie poniżej 30 poziomu; dla postaci
  bez wykupionego dostępu pokazuje komunikat z odnośnikiem do Wiedźmy
  zamiast selektora mapy; dla postaci z dostępem - pełny selektor mapy +
  czasu trwania + start/podgląd/odbiór nagród.
- **Chata Wiedźmy → zakładka "Lustro"** (`Witch.php` / `witch.blade.php`):
  sprzedaje 7-dniowy dostęp za złoto lub gemy (patrz
  `docs/modules/witch_and_crafting.md`).
- **Hub**: baner "Magiczne Lustro Aktywne" z podglądem nagród na żywo, gdy
  `Character::hasActiveMirror()` jest `true`.
- **Nawigacja (desktop/mobile)**: pulsujący badge "LUSTRO" na linku
  "Wyprawy", widoczny wyłącznie gdy sesja jest aktywna (niezależnie od
  wykupionego dostępu czasowego).

## 5. Samouczek

Odblokowanie Lustra na 30 poziomie ma swój wątek fabularny (Kapitan →
Wiedźma) opisany w `docs/modules/tutorial.md` (etapy `game_stage` 40-43).
