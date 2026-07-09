# Roadmapa Rozwoju: Berserk Rush

Poniższy dokument przedstawia proponowany plan rozwoju gry z podziałem na etapy (Fazy). Obecnie zrealizowana została pierwsza część funkcjonalności (Faza 1), stanowiąca rdzeń gry.

---

## 🟢 Faza 1: Solidne Podstawy i Rdzeń Walki (Zakończone / W trakcie)
W tym etapie zbudowaliśmy fundamentalne moduły umożliwiające "wejście" do gry i odbycie w niej akcji.
- [x] Tworzenie Postaci i system statystyk (STR, INT, VIT, AGI).
- [x] System Doświadczenia (XP) i automatyczny awans na nowe poziomy.
- [x] Turowy silnik walki (PvE) z systemem inicjatywy i szans (krytyki, chybienia).
- [x] Generowanie łupów z tabel (`DropService`) – nagradzanie gracza złotem i gemami poprzez `CurrencyLedger`.
- [x] Tworzenie niezałożonych instancji przedmiotów (`ItemInstance`) lądujących w Inventory (`ItemLedger`).

---

## 🟡 Faza 2: Zarządzanie Ekwipunkiem (Najbliższy Cel)
Gra posiada zrzut przedmiotów, ale nie mamy możliwości ich użycia, założenia ani podglądu statystyk z nich wynikających.
- **Ekwipunek gracza (Inventory UI):** Interfejs pozwalający na przegląd zdobytych łupów (plecak gracza).
- **Zakładanie wyposażenia (Equip/Unequip):** Mechanika przekładania przedmiotów z plecaka do aktywnych slotów (np. Broń, Zbroja, Hełm, Buty, Pierścienie).
- **Przeliczanie Statystyk:** Kiedy gracz zakłada sprzęt, jego siła ataku i obrona (HP) w module Walki (`EncounterService`) muszą to uwzględnić i skalować się ze sprzętu, a nie tylko bazowych statystyk postaci.

---

## 🟠 Faza 3: Kuźnia i Ulepszenia (Metin2 Style)
Jeden z najistotniejszych aspektów gry, opierający się na progresji mocy. 
- **System Ulepszeń (Upgrades):** Akcje (`UpgradeItem`) pozwalające na próbę ulepszenia przedmiotu (od +0 do +9).
- **Szansa i Ryzyko:** Zastosowanie algorytmu szans (np. wzorcowy `UpgradeStrategy`), który określa jak duże jest ryzyko, że przy +6 przedmiot się zniszczy, spadnie poziom ulepszenia, albo powiedzie.
- **Statystyki Losowe:** Każdy poziom ulepszenia podnosi wartość `roll_stats` w strukturze JSONB, dając zauważalny zastrzyk siły przedmiotu.

---

## 🔴 Faza 4: Gospodarka, Market i Poczta (Player-driven Economy)
Gry typu idle/RPG żyją rynkiem stworzonym przez samych graczy. Mechaniki bazy (transakcje, locks, ledgers) są gotowe by to obsłużyć bezpiecznie.
- **Dom Aukcyjny / Rynek (Market):** Wystawianie ofert (sprzedaż za Gold/Gems). 
- **Filtrowanie i Query Objects:** Implementacja zaawansowanego wyszukiwania przedmiotów na aukcjach (np. "Miecze +5 do poziomu 20").
- **System Poczty (Mail):** Rozliczanie transakcji w tle i przesyłanie wygranych przedmiotów oraz zarobionego złota na wirtualne skrzynki pocztowe graczy.

---

## 🟣 Faza 5: Rzemiosło i Zbieractwo (Crafting)
Wykorzystanie zebranych materiałów (które już potrafi zrzucić `DropService`) do tworzenia cennych mikstur i ekwipunku bez konieczności liczenia na łut szczęścia z potworów.
- **Przepisy (Recipes):** Definiowanie rzemiosła – co można połączyć by stworzyć coś nowego.
- **Wytwarzanie (CraftingService):** Palenie (niszczenie) materiałów w zamian za stworzenie nowego, potężniejszego `ItemInstance`.
- **System Rzadkości (Rarities):** Szansa na stworzenie przedmiotu o wyższej wartości nominalnej (Common, Uncommon, Rare, Epic, Legendary).

---

## 🔵 Faza 6: Aspekty Społecznościowe i Interakcja
Gdy gracz już wie jak być silnym i ma co zdobywać, czas pokazać mu innych graczy.
- **Gildie:** Zrzeszanie się graczy i wspólne wpłacanie złota/klejnotów w celu powiększania bonusów pasywnych dla członków.
- **Tablice Wyników (Leaderboards):** Redis Sorted Sets do tworzenia szybkich statystyk najlepszych graczy względem XP, Bogactwa, wygranych walk.
- **Czat Globalny (Laravel Reverb / WebSockets):** Podpięcie natychmiastowej, asynchronicznej komunikacji między graczami.

---

## ⚪ Faza 7: Doskonalenie UI, Eventy Specjalne i Skalowanie
Faza końcowa przygotowująca do oficjalnego release'u.
- **Mikroanimacje / Stylizacja:** Pełne tchnięcie życia w Livewire, dodanie płynnych animacji pasków zdrowia, zrzutów przedmiotów i efektów krytycznych (Tailwind / CSS).
- **Asynchroniczny Looting:** Przeniesienie symulacji tysięcy walk do procesów tle na kolejkach Redis i Laravel Horizon (Joby).
- **Bossowie Map / World Bossowie:** Czasowe wydarzenia dla wszystkich graczy jednocześnie.
