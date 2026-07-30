# Moduł: Gildie

## Opis

System gildii pozwala graczom łączyć się w grupy, współpracować, zbierać surowce (EXP, złoto, klejnoty) do wspólnego skarbca i budować społeczność. Każda gildia posiada własny skarbiec, poziom, system ról oraz wbudowany dedykowany kanał czatu.

### Blokada dostępu (10 poziom postaci) i wątek samouczka

Cały moduł Gildii wymaga **10 poziomu postaci** (`Character::level >= 10`) - próg globalny, niezależny od `Guild::min_level` ustawianego przez lidera dla konkretnej gildii. Blokada jest wyegzekwowana w trzech miejscach, wzorem Tablicy Wyzwań (próg 5 poziomu):
- kafel Gildii w Hubie (`resources/views/livewire/city/hub.blade.php`, desktop i mobile) - szary, z badge `fa-lock` "Zablokowane (Poz. 10)" poniżej progu,
- link "Gildia" w nawigacji bocznej/mobilnej (`desktop-nav.blade.php`, `mobile-nav.blade.php`, zmienna `$isGuildLocked`) - poniżej progu zamieniony na toast błędu zamiast nawigacji,
- `GuildComponent::mount()` - twarda blokada przy bezpośrednim wejściu pod `/guild`.

Odblokowanie modułu jest wpięte w samouczek Kapitana (`User::game_stage` 34→37) - patrz `docs/modules/tutorial.md`, sekcja "Gildia". Gracz, który osiągnie 10 poziom zanim dotrze tu w normalnym toku samouczka, po kliknięciu zablokowanej ikony/kafla Gildii zostaje odbity do Hubu, gdzie automatycznie odpala się odpowiedni etap dialogu Kapitana (ten sam mechanizm anti-softlock co przy Tablicy Wyzwań).

---

## Zakres funkcjonalności

### Zarządzanie Gildią
- **Tworzenie:** Gracz może założyć gildię, jeśli spełnia wymagania (np. koszt w złocie) i nie należy do innej gildii.
- **Role:** W gildii występują role nadające różne uprawnienia:
  - `leader` (Przywódca) - pełna kontrola (edycja nazwy/opisu, awansowanie członków, wyrzucanie, zarządzanie skarbcem, zapraszanie).
  - `commander` (Dowódca) - zapraszanie, awansowanie niższych ról, wyrzucanie nowicjuszy.
  - `elder` (Starszy) - uprawnienia do zapraszania.
  - `member` (Członek) - standardowa rola, możliwość korzystania z chatu gildii i wpłacania dotacji.
  - `novice` (Nowicjusz) - nowo dołączeni członkowie.

### Skarbiec i Magazyn Gildyjny
Gildie posiadają własny skarbiec na:
- **EXP Gildii** (determinuje poziom gildii)
- **Złoto** (limit zależny od poziomu skarbca)
- **Klejnoty** (Gems)

Gracze mogą przekazywać swoje zasoby na rzecz gildii za pomocą specjalnych komend na chacie gildyjnym:
- `/donate exp <ilość>` - przekazuje EXP gracza do gildii
- `/donate gold <ilość>` - wpłaca złoto do skarbca
- `/donate gems <ilość>` - wpłaca klejnoty do skarbca

Ponadto gildia posiada **Magazyn Gildyjny (Guild Stash)** z pojemnością 20 slotów:
- Członkowie gildii mogą deponować nieprzypisane przedmioty z plecaka **oraz materiały ze schowka materiałów** do magazynu gildii za pomocą serwisu `GuildStashService::deposit()`. Serwis rozpoznaje typ przedmiotu (`ItemTemplate::type === 'material'`) i wymaga, aby przedmiot znajdował się w odpowiedniej lokalizacji źródłowej (`inventory` dla zwykłych przedmiotów, `material_stash` dla materiałów) przed zdeponowaniem.
- Każdy członek gildii może wyciągać przedmioty z magazynu gildii (`GuildStashService::withdraw()`), o ile posiada wolne miejsce w docelowej lokalizacji: zwykłe przedmioty trafiają z powrotem do plecaka (wymagane wolne miejsce w plecaku, walidowane przez `Character::isBackpackFull()`), a materiały wracają do schowka materiałów (wymagane wolne miejsce w schowku, walidowane przez `Character::isMaterialStashFull()`). Próba wyciągnięcia przedmiotu bez wolnego miejsca w odpowiednim miejscu docelowym kończy się błędem (`INVENTORY_FULL` lub `MATERIAL_STASH_FULL`).
- Wszystkie operacje deponowania i wyciągania przedmiotów są automatycznie zapisywane w dzienniku zdarzeń gildii (`GuildLog`).

Analogiczne zasady (rozróżnienie `inventory`/`material_stash` przy deponowaniu oraz walidacja wolnego miejsca przy wyciąganiu) obowiązują w **Magazynie Gracza** (`PlayerStashService`), opisanym w module Profilu i Ekwipunku.

Złoto i klejnoty mają określony limit (cap), po przekroczeniu którego dotacje są blokowane, dopóki skarbiec nie zostanie rozbudowany.

### Czat Gildii
- Każda gildia posiada **prywatny kanał komunikacji** (Real-time za pomocą Laravel Reverb).
- Komunikaty o dotacjach (np. "Gracz WojWielki wpłacił 100 złota do skarbca gildii.") są wysyłane automatycznie przez system na chacie gildii w czasie rzeczywistym.

### Zaproszenia (Mail System)
- Rekrutacja odbywa się poprzez wbudowany system poczty w grze.
- Liderzy/Dowódcy/Starsi mogą wysłać zaproszenie do innego gracza bezpośrednio z panelu tooltipa gracza na czacie globalnym.
- Zaproszenie wysyłane jest jako **Wiadomość In-Game z załącznikiem** typu `guild_invite`.
- Gracz po wejściu do skrzynki pocztowej może "odebrać" załącznik, co automatycznie dołącza go do gildii (o ile nadal spełnia warunki i ma miejsce).

### Wojny Gildii (Guild Wars) - Starcie 5v5 (2026-07-28)

Każda gildia może ustawić **drużynę wojenną** - dokładnie 5 postaci (`Guild::war_team`,
`Guild::hasWarTeam()` wymaga równo 5 członków) - i wyzwać inną gildię na wojnę
(`GuildWarService::challengeGuild()`). Po zaakceptowaniu wyzwania
(`GuildWarService::acceptWar()`) obie drużyny zostają "zamrożone" jako rostery
(`GuildWar::challenger_roster` / `defender_roster`, tablice 5 ID postaci), a
gildia broniąca się zostaje zablokowana na ulepszenia (`is_war_locked`) do
czasu rozstrzygnięcia.

**Rozstrzygnięcie wojny (`GuildWarService::processWar()`) to JEDNO starcie
drużynowe 5v5**, a nie 5 osobnych pojedynków 1v1 jak we wcześniejszej wersji
systemu:
- Obie 5-osobowe drużyny wchodzą na tę samą "planszę" jednocześnie -
  mechanicznie jest to bezpośredni odpowiednik starcia grupowego PvE
  (`EncounterService::simulateMultiCombat()`, patrz `docs/modules/combat.md`,
  sekcja 6), tylko że po OBU stronach stoją żywi gracze zamiast potworów.
- **Inicjatywa:** kolejność działania wszystkich 10 postaci ustalana jest raz
  na starcie wg atrybutu AGI (remisy rozstrzygane losowo) i obowiązuje przez
  całą walkę - w każdej rundzie każda żywa postać wykonuje dokładnie jedną
  akcję w tej kolejności.
- **Cel ataku - "focus fire":** każda postać atakuje żywego przeciwnika z
  NAJNIŻSZYM aktualnym HP po przeciwnej stronie (`selectLowestHpAlive()`),
  dzięki czemu drużyny realnie "topnieją" jeden przeciwnik na raz zamiast
  rozkładać obrażenia równo po całej piątce - to celowa decyzja, żeby walki
  5v5 miały wyraźny, czytelny rytm (kto pada pierwszy) zamiast rozmytego
  wyniku.
- **Obrażenia, krytyk, unik, umiejętności bojowe i "magic burst"** liczone są
  identyczną formułą jak na Arenie PvP (`PvPEncounterService::performAttack()`),
  zaimplementowaną osobno jako `GuildWarService::resolveTeamAttack()` (operuje
  na dowolnej parze z 10-osobowej "planszy", a nie na dwóch stałych
  snapshotach jak w PvP) - pełny parytet balansu między Areną i Wojnami
  Gildii.
- **Koniec starcia:** wojna kończy się, gdy jedna drużyna zostanie całkowicie
  pokonana (0 żywych), lub po osiągnięciu limitu 40 rund - wtedy o zwycięstwie
  decyduje wyższy ŁĄCZNY procent pozostałego HP drużyny (suma HP / suma maxHP
  wszystkich członków, żywych i poległych).
- **Nagrody:** zwycięska gildia przejmuje CAŁY skarbiec (złoto + klejnoty)
  przegranej gildii, a cała zwycięska drużyna wojenna (do 5 postaci) dostaje
  50 żetonów areny (`arena_tokens`). Obie gildie zostają odblokowane
  (`is_war_locked = false`) niezależnie od wyniku.
- Pełny log starcia (wszystkie rundy, ataki, trafienia/krytyki/unik,
  stan HP obu drużyn po każdej akcji) zapisywany jest jako JEDEN wiersz w
  `guild_war_fights` (kolumny `challenger_snapshot`/`defender_snapshot`
  przechowują TABLICĘ snapshotów do 5 postaci, nie pojedynczy obiekt jak w
  poprzedniej wersji z 5 osobnymi pojedynkami).
- **Zestaw wojenny:** snapshoty wszystkich 10 postaci (`processWar()`) liczone
  są z dedykowanego zestawu "Wojna Gildii" (`createSnapshot('guild_war')`,
  patrz `docs/modules/profile_and_equipment.md`, sekcja "Zestawy Ekwipunku"),
  niezależnie od tego czy dana postać jest w drużynie atakującej czy broniącej
  - z fallbackiem per-slot na aktualny ekwipunek, gdy dany slot zestawu nie
  jest skonfigurowany.

> **Status wdrożenia (2026-07-29):** `GuildWarService` jest w pełni podpięty
> pod UI gildii (`GuildComponent`/`guild-component.blade.php`). Wyzwanie
> (`challengeGuild()`) wysyła się z ekranu przeglądania gildii. Zakładka
> "Wojny Gildii" w panelu gildii pokazuje wszystkie wojny; dla wojny w
> statusie `pending`, w której nasza gildia jest obrońcą, lider widzi
> przyciski "Zaakceptuj wojnę" / "Odrzuć" (`acceptWarChallenge()`/
> `declineWarChallenge()`). Akceptacja wywołuje `acceptWar()`, a następnie od
> razu `ProcessGuildWarJob::dispatchSync()`, które synchronicznie rozgrywa
> starcie przez `processWar()` - w tym środowisku kolejka używa sterownika
> `database` bez działającego workera, więc odroczone dispatch-owanie joba
> nigdy by się nie wykonało i wojna zostawałaby trwale w statusie
> `in_progress` bez rozegranych rund. Wynik (zwycięstwo/porażka, liczba rund,
> ocalali) jest widoczny od razu w zakładce "Wojny Gildii" z linkiem do
> podglądu pełnego starcia (`city.arena.combat.gvg`).

---

## Architektura techniczna

### Modele i Tabele
- `Guild`: Główna tabela gildii (nazwa, opis, skarbiec, poziom, max_members, `war_team`, `is_war_locked`).
- `GuildMember`: Tabela łącząca `Guild` i `Character`, przechowująca rolę gracza i datę dołączenia.
- `GuildLog`: Historia akcji (dotacje, dołączenie, opuszczenie).
- `GuildWar`: Pojedyncza wojna między dwiema gildiami (status, rostery, nagrody, zwycięzca).
- `GuildWarFight`: Log starcia drużynowego 5v5 danej wojny (jeden wiersz na wojnę, `fight_order = 1`) - snapshoty obu drużyn, pełny log rund, liczba ocalałych po każdej stronie.

### Serwisy i Akcje
Zarządzanie gildią opiera się o mechanizmy transakcji by zachować spójność danych:
- Przekazywanie zasobów odbywa się wewnątrz `DB::transaction()`.
- Autoryzacja i operacje na czacie (jak wpisywanie `/donate`) wykonują logikę na żywo w kontrolerach Livewire i rozgłaszają eventy.
- `GuildWarService`: wyzwanie na wojnę, akceptacja/odrzucenie, oraz symulacja starcia drużynowego 5v5 (patrz sekcja "Wojny Gildii" wyżej) - całość również w `DB::transaction()`.
