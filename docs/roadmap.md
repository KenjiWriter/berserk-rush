# Roadmapa Rozwoju: Berserk Rush

Poniższy dokument przedstawia proponowany plan rozwoju gry z podziałem na etapy (Fazy). Obecnie zrealizowana została pierwsza część funkcjonalności (Faza 1-3), stanowiąca rdzeń gry.

---

## 🟢 Faza 1: Solidne Podstawy i Rdzeń Walki (Zakończone)
W tym etapie zbudowaliśmy fundamentalne moduły umożliwiające "wejście" do gry i odbycie w niej akcji.
- [x] Tworzenie Postaci i system statystyk (STR, INT, VIT, AGI).
- [x] System Doświadczenia (XP) i automatyczny awans na nowe poziomy.
- [x] Turowy silnik walki (PvE) z systemem inicjatywy i szans (krytyki, chybienia).
- [x] Generowanie łupów z tabel (`DropService`) – nagradzanie gracza złotem i gemami poprzez `CurrencyLedger`.
- [x] Tworzenie niezałożonych instancji przedmiotów (`ItemInstance`) lądujących w Inventory (`ItemLedger`).

---

## 🟢 Faza 2: Zarządzanie Ekwipunkiem (Zakończone)
Gra posiada zrzut przedmiotów, ale nie mamy możliwości ich użycia, założenia ani podglądu statystyk z nich wynikających.
- [x] Ekwipunek gracza (Inventory UI): Interfejs pozwalający na przegląd zdobytych łupów (plecak gracza).
- [x] Zakładanie wyposażenia (Equip/Unequip): Mechanika przekładania przedmiotów z plecaka do aktywnych slotów.
- [x] Przeliczanie Statystyk: Kiedy gracz zakłada sprzęt, jego siła ataku i obrona (HP) w module Walki (`EncounterService`) muszą to uwzględnić i skalować się ze sprzętu.

---

## 🟢 Faza 3: Kuźnia i Ulepszenia (Metin2 Style) (Zakończone)
Jeden z najistotniejszych aspektów gry, opierający się na progresji mocy. 
- [x] System Ulepszeń (Upgrades): Akcje (`UpgradeItem`) pozwalające na próbę ulepszenia przedmiotu (od +0 do +9).
- [x] Szansa i Ryzyko: Zastosowanie algorytmu szans (np. wzorcowy `UpgradeStrategy`), który określa jak duże jest ryzyko.
- [x] Statystyki Losowe: Każdy poziom ulepszenia podnosi wartość `roll_stats` w strukturze JSONB, dając zauważalny zastrzyk siły przedmiotu.

---

## 🟢 Faza 4: Czarodziej i Typy Potworów (Zakończone)
Wprowadzenie dodatkowej drogi progresji sprzętu w formie Zaklinania (Enchantowania).
- [x] **Typy potworów:** Dodanie kategorii potworów (zwierzę, demon, nieumarły, ork) i modyfikatorów dmg przeciwko nim.
- [x] **Dodawanie bonusów:** Możliwość dodania do 5 bonusów do przedmiotu u NPC Czarodzieja. Szansa spada z każdym kolejnym bonusem (75%, 50%, 40%, 30%, 20%).
- [x] **Reroll bonusów:** Możliwość resetowania wszystkich wylosowanych dotychczas bonusów naraz, aby wygenerować inne efekty.
- [x] **Koszt operacji:** Gracz ma prawo wybrać, czy płaci za zaklinanie Złotem, czy walutą Premium (Gems).
- [x] **Różnice klasowe:** Bronie i Zbroje posiadają różne dostępne pule bonusów.

---

## 🟢 Faza 5: System Administracyjny (GUI) i Combat Power (Zakończone)
Stworzenie narzędzi dla administratorów/twórców oraz wprowadzenie wizualnej miary mocy przedmiotów.
- [x] **Panel Administratora (GUI):** Interfejs graficzny do swobodnego tworzenia i edycji map, przedmiotów, asortymentu kupców oraz potworów.
- [x] **Zarządzanie Łupami i Mapami:** Możliwość łatwego przypisywania tabel łupów (loot) do potworów oraz przypisywania potworów do map bezpośrednio w GUI.
- [x] **System Combat Power (CP):** Wyliczanie i wizualne odzwierciedlanie całkowitej "mocy" przedmiotu, co pozwala graczom łatwiej oceniać siłę ekwipunku.

---

## 🟢 Faza 6: System Czarownicy i Warzenie Mikstur (✅ Zakończona)
Wprowadzenie systemu craftingu (warzenia) i NPC sklepu z miksturami.
- [x] Utworzenie tabeli `item_recipes` przechowującej przepisy (ingredients JSON, gold_cost) i wynikowy przedmiot.
- [x] Stworzenie seedera `RecipeSeeder` z pierwszymi podstawowymi miksturami.
- [x] Rozbudowa interfejsu u Wiedźmy (`Witch.php`): zakładki "Wywary Specjalne", "Sklep Alchemiczny", "Warzenie Mikstur".
- [x] Zaimplementowanie akcji warzenia (`CraftItemAction`) odejmującej potrzebne surowce.
- [x] Implementacja kupowania podstawowych mikstur oraz specjalnej mikstury EXP (+20%).

---

## 🟢 Faza 7: Gospodarka, Market i Poczta (✅ Zakończona)
Gry typu idle/RPG żyją rynkiem stworzonym przez samych graczy. Mechaniki bazy (transakcje, locks, ledgers) są gotowe by to obsłużyć bezpiecznie.
- [x] **Dom Aukcyjny / Rynek (Market):** Wystawianie ofert (sprzedaż za Gold/Gems). 
- [x] **Filtrowanie i Query Objects:** Implementacja zaawansowanego wyszukiwania przedmiotów na aukcjach (np. "Miecze +5 do poziomu 20").
- [x] **System Poczty (Mail):** Rozliczanie transakcji w tle i przesyłanie wygranych przedmiotów oraz zarobionego złota na wirtualne skrzynki pocztowe graczy.

---

## 🟢 Faza 8: Aspekty Społecznościowe i Czat Globalny (✅ Zakończona)
Gdy gracz już wie jak być silnym i ma co zdobywać, czas pokazać mu innych graczy. Ta faza kładzie nacisk na komunikację w czasie rzeczywistym.
- [x] **Czat Globalny (Laravel Reverb / WebSockets):** Dostępny na dole ekranu, rozwijany/zwijany panel chatu dla wszystkich graczy online.
- [x] **Format Wiadomości:** Zunifikowany format: `<nick_postaci> [<poziom postaci>]: <wiadomosc>`.
- [x] **Inspekcja Graczy (Tooltips):** Po kliknięciu na nick na czacie, wyświetla się tooltip z informacjami o graczu: Nick, Poziom, Combat Power (CP) oraz lista aktualnie założonych przedmiotów z poziomem ulepszenia.
- [ ] **Gildie:** Zrzeszanie się graczy i wspólne wpłacanie złota/klejnotów w celu powiększania bonusów pasywnych dla członków.
- [ ] **Tablice Wyników (Leaderboards):** Redis Sorted Sets do tworzenia szybkich statystyk najlepszych graczy względem XP, Bogactwa, wygranych walk.

---

## 🟢 Faza 9: Rzemiosło i Zbieractwo (Crafting) (✅ Zakończona)
Wykorzystanie zebranych materiałów (które już potrafi zrzucić `DropService`) do tworzenia cennych mikstur i ekwipunku bez konieczności liczenia na łut szczęścia z potworów.
- [x] **Przepisy (Recipes):** Definiowanie rzemiosła – co można połączyć by stworzyć coś nowego. Zarządzanie z poziomu GUI Administratora.
- [x] **Wytwarzanie (CraftingService):** Palenie (niszczenie) materiałów w zamian za stworzenie nowego, potężniejszego `ItemInstance`. Osobne zakładki Rzemiosła u Kowala Broni i Płatnerza.
- [x] **System Rzadkości (Rarities):** Szansa na stworzenie przedmiotu o wyższej wartości nominalnej (Common, Uncommon, Rare, Epic, Legendary). Rzadkość generuje potężniejsze statystyki bonusowe i podnosi CP przedmiotu.

---

## ⚪ Faza 10: Doskonalenie UI, Asynchroniczny Loot i Optymalizacja
Faza końcowa pierwszej wersji produkcyjnej, skupiająca się na płynności i wydajności gry (Core V1).
- **Mikroanimacje / Stylizacja:** Pełne tchnięcie życia w Livewire, dodanie płynnych animacji pasków zdrowia, zrzutów przedmiotów i efektów krytycznych.
- **Asynchroniczny Looting:** Przeniesienie walk w tle (Idle) i generowania przedmiotów do procesów w tle na kolejkach (Redis + Laravel Horizon).
- **Optymalizacja DB:** Indeksy, optymalizacja odpytań i cachowanie często używanych statystyk w Redis.

---

## 🟡 Faza 11: Rozszerzenie PvE i Endgame (World Boss & Dungeons)
Gdy podstawa gry jest solidna, dodajemy wymagający kontent (Endgame) angażujący całą społeczność.
- **Dungeony (Lochy Instancjonowane):** Wymagające lokacje ze stadiami (piętrami), gdzie na końcu czeka unikalny boss ze swoimi tabelami unikatowego dropu. Koszt wejścia to rzadkie klucze z bossów z map.
- **World Bossowie:** Epickie czasowe wydarzenia, w których wszyscy serwerowi gracze atakują jednego bossa z potężną pulą HP. Nagrody są przyznawane proporcjonalnie do zadanego Damage'u po ubiciu bossa.
- **Pety i Towarzysze (Companions):** Małe chowańce podążające za graczem, dające unikalne statystyki pasywne (np. +5% drop rate, auto-looting złota). Możliwość wykluwania ich z rzadkich jaj z dungeona.

---

## 🟠 Faza 12: Player vs Player (PvP) i Wojny Gildii
Wprowadzenie pełnoprawnej rywalizacji bezpośredniej między graczami.
- **Arena PvP (Asynchroniczna):** Gracz może wyzwać "widmo" (zapisany stan ekwipunku i statystyk) innego gracza na arenie. Zaimplementowany System ELO / MMR do rankingowania graczy (liga brązowa, srebrna, złota).
- **Wojny Gildii (GvG):** Masowe starcia między zrzeszeniami graczy. Zwycięska gildia przejmuje czasowo kontrolę nad "kopalniami" gwarantującymi stały przychód Gemsów.
- **Sklep Areny:** Zbieranie specjalnej waluty "PVP Tokens" (za walki z ludźmi), którą wymienia się na niedostępne nigdzie indziej wyposażenie i gladiator-skórki w mieście.

---

## 🔴 Faza 13: System Kolekcji, Osiągnięć i Tytułów
Zatrzymanie graczy (Retencja) przez dawanie długoterminowych celów i powodów do "kolekcjonowania".
- **Bestiariusz i Pokedex Przedmiotów:** Drobne stałe bonusy do ataku za np. pokonanie 10 000 Orków lub znalezienie/wytworzenie wszystkich legendarnych mieczy w grze.
- **Osiągnięcia (Achievements):** Skomplikowane lub rzadkie wydarzenia na koncie gracza (np. "Spal 50 przedmiotów u Kowala z rzędu") nagradzane specjalnymi skrzynkami i punktami osiągnięć.
- **Tytuły (Titles):** Przedrostki przed Nickiem gracza na globalnym czacie (np. `[Zabójca Smoków] koxu [Poz. 99]`), dające drobne pasywne statystyki. Tytuły można dobrowolnie ustawiać w profilu.

---

## 🟤 Faza 14: Ekonomia 2.0 i System Zawodów
Rozbudowa ekonomii w taki sposób, aby gracze ostatecznie sami produkowali surowce i nakręcali popyt, co ożywi mocniej Fazy 7 (Market) i 9 (Rzemiosło).
- **Zawody Zbierackie:** Górnictwo, Zielarstwo, Łowiectwo jako osobne, poboczne akcje (mini-zlecenia), produkujące dedykowane surowce podstawowe w dużych ilościach. Osobne levele profesji.
- **Zawody Wytwórcze:** Gracze specjalizujący się w danej dziedzinie i mający wysoki jej poziom, mogą tworzyć przedmioty z dużo lepszymi losowymi widełkami statystyk. Zwykły gracz na poz. 1 zrobi miksturę leczącą 50 HP, a Mistrz na poz. 50 uwarzy z tych samych składników taką, co leczy 150 HP.
- **Ewolucja Przedmiotów (Tiers):** Połączenie dwóch tych samych przedmiotów +9 u kowala (albo dedykowanego NPC) w ten sam przedmiot, ale podnoszący jego "Tier" (np. z Tier 1 do Tier 2). Poziom ulepszenia zeruje się (+0), ale broń otrzymuje znacznie wyższe bazowe statystyki z opcją na nowy, potężniejszy Enchantment.
