# Moduł: Czarownica i Mikstury (Crafting & Sklep)

## 1. Sklep Alchemiczny i Mikstury Specjalne
Czarownica służy w grze jako punkt handlowy dla przedmiotów typu **consumable** (mikstury użytkowe).
*   **Wywary Specjalne:** Gracz ma możliwość zakupu specjalnej mikstury (+20% doświadczenia) raz na dobę. Ograniczenie realizowane jest przez tabelę `character_cooldowns` (klucz `witch_exp_potion_daily`). Oferta ta jest wyróżniona na samym szczycie asortymentu w zakładce Sklepu.
*   **Sklep Alchemiczny:** Wiedźma sprzedaje mikstury zdefiniowane i przydzielone jej poprzez panel zarządzania **Handlarzami** (model `MerchantItem` dla `merchant_id = 'witch'`). Kupno wymaga odpowiedniej ilości złota, pobiera je z konta gracza (kwota obliczana przez `ShopService`) i umieszcza nowy obiekt w ekwipunku poprzez `ItemInstance`, a także tworzy wpis w `ItemLedger` potwierdzający zakup u NPC.

## 2. System Warzenia i Rzemiosła (Crafting)
System craftingu służy do wytwarzania mikstur, a także broni i zbroi na podstawie zebranych zasobów (materiałów rzemieślniczych). Oparty jest o tabelę receptur i obsługiwany przez uniwersalny `CraftingService`.

### Encja `ItemRecipe`
Przechowuje reguły (przepisy) dla każdego uwarzonego przedmiotu. Zawiera:
-   `id`: ULID
-   `result_item_template_id`: Wskazanie na główny szablon wynikowego przedmiotu (`item_templates`).
-   `ingredients`: Struktura JSON przechowująca mapowanie: `[{"template_id": "material_ulid", "quantity": 2}]`.
-   `gold_cost`: Koszt w złocie, który gracz musi dopłacić za usługę stworzenia mikstury.

### Realizacja (Logika)
Realizacją craftingu zajmuje się wywoływany w akcji interfejsu (Livewire) mechanizm: `CraftingService`. Jego kroki to:
1.  **Weryfikacja bazy surowcowej**: Pobranie ekwipunku gracza, sprawdzenie czy zsumowane *stacki* pokrywają *quantity* ze wszystkich składników wymienionych w JSON receptury.
2.  **Weryfikacja środków finansowych**: Sprawdzenie balansu konta (gold).
3.  **Transakcja Odbioru**: Odjęcie złota z postaci. Zmniejszenie `stack_size` odpowiadających `ItemInstance` u gracza (z usuwaniem przedmiotów jeśli ich *stack* osiągnie 0).
4.  **Mechanika Rzadkości (Rarity)**: Dla sprzętu bojowego (broń, zbroja **i biżuteria**) gra losuje szansę na lepszą jakość (Common 70%, Uncommon 20%, Rare 8%, Epic 1.9%, Legendary 0.1%). Wylosowanie lepszej rzadkości dodaje bonusowe statystyki (`roll_stats`) i zwiększa Combat Power przedmiotu.
    > **Uwaga (rework itemizacji, 2026-07-28):** Pula LOSOWANYCH statystyk (`generateBonusStats`, zapisywanych w `roll_stats` instancji) nie zawiera surowych atrybutów (STR/INT/VIT/AGI) dla broni i zbroi - tylko obrażenia fizyczne/magiczne, obronę, HP i szansę na trafienie krytyczne. Biżuteria (`accessory`) jako jedyna ma 25% szans na dodatkowy, płaski, LOSOWY bonus do jednego atrybutu (+1..+5) - patrz `docs/modules/profile_and_equipment.md`.
    > **Uwaga (itemizacja klasowa, 2026-07-28):** To dotyczy tylko losowych `roll_stats` z rzadkości. Gdy przepis wytwarza przedmiot z zestawu klasowego (hełm/klatka/buty `_w`/`_m`/`_a`), wynikowa instancja i tak dziedziczy STAŁY bonus atrybutu ze `ItemTemplate::base_stats` szablonu (Wojownik→STR+VIT, Mag→INT, Skrytobójca→AGI) - crafting nie musi nic dodatkowo losować, żeby ten bonus zadziałał, bo `Character::getTotalAttributes()` czyta `base_stats` tak samo jak `roll_stats`.
5.  **Generacja**: Utworzenie wynikowego przedmiotu z wpisem w Ekwipunku gracza.
6.  **Rejestracja Historii**: Wpis logów transakcji do `ItemLedger` (`action` => 'crafting', `ref_type` => 'crafting_service') na rzecz Idempotency i celów analitycznych.

### Panel Administratora
Gra posiada pełnoprawny widok graficzny zarządzania przepisami w zakładce Administracji (`ItemRecipes.php`). Administrator może ustalać dowolne przedmioty wynikowe, koszty złota, oraz dynamicznie dodawać i usuwać potrzebne materiały. Asortyment sklepu wiedźmy konfiguruje się natomiast w zakładce Handlarzy (`MerchantItems.php`).

### Elementy UI
Widok Czarownicy (`Witch.php`) posiada dedykowane zakładki:
*   `shop`: Wylistowuje dostępne mikstury z modelu `MerchantItem` oraz wyróżnioną ofertę na miksturę doświadczenia (limitowaną dziennie opartą o `character_cooldowns`).
*   `crafting`: Lista przepisów alchemicznych. Komponent sam przeszukuje ekwipunek i renderuje postęp zebrania składników wizualnie, kontrolując aktywność przycisku "Uwarz".
*   `enchant`: Zakładka Zaczarowania przedmiotów (przeniesiona od dawnego Czarodzieja/Sklepu Magicznego). Gracz wybiera broń, zbroję lub biżuterię z ekwipunku i nasyca ją magicznymi bonusami za złoto lub klejnoty (`App\Application\Wizard\EnchantItem`), z możliwością przelosowania istniejących bonusów (`App\Application\Wizard\RerollEnchantments`). Logika i encje pozostały w przestrzeni nazw `Wizard` (bez zmian), zmieniło się wyłącznie miejsce w UI oraz nawigacji.
*   `mirror`: Zakładka zakupu dostępu do systemu Lustra (patrz `docs/modules/mirror.md`). Widoczna dopiero po osiągnięciu 30 poziomu postaci (poniżej tego progu wyświetla wyłącznie komunikat blokady). Wiedźma sprzedaje tu 7-dniowy dostęp czasowy do Lustra za **5 000 000 złota** lub **200 gemów** (`MirrorService::purchaseAccess()`, wywoływane przez `Witch::buyMirrorAccess()`). Zakup nie uruchamia sesji sam w sobie - jedynie odblokowuje możliwość startowania sesji (wybór mapy + godzin) w zakładce "Lustro" w Profilu. Kolejny zakup przed wygaśnięciem obecnego okresu dolicza kolejne 7 dni do istniejącego terminu, zamiast go resetować.

> **Uwaga (refaktor):** Rzemiosło broni i zbroi zostało wydzielone z Brońmistrza (`Weaponsmith`) i Zbrojmistrza (`Armorsmith`) do osobnej domeny **Kowal** (`Blacksmith.php`) - patrz `docs/modules/blacksmith.md`. Wiedźma nadal obsługuje wyłącznie crafting mikstur (`consumable`).

> **Uwaga (refaktor, 2026-07-28):** Zaczarowanie przedmiotów zostało przeniesione od Czarodzieja (`Wizard.php` / `city.wizard`) do Wiedźmy jako trzecia zakładka (`enchant`). Strona Czarodzieja i trasa `city.wizard` pozostały w kodzie (bez zmian funkcjonalnych), ale zostały odlinkowane ze wszystkich elementów nawigacji (desktop-nav, mobile-nav, kafelki Hub) - "Sklep Magiczny" nie jest już dostępny z poziomu UI. Dodatkowo czas trwania buffu z Eliksiru Wiedzy Absolutnej (+20% exp) wydłużono z 10 minut do 60 minut - zmiana zrealizowana migracją danych `database/migrations/2026_07_28_120000_increase_exp_potion_duration.php`, aktualizującą pole `base_stats.duration_minutes` szablonu `potion-exp-special`.

> **Uwaga (rework mikstur, 2026-08-02):** System mikstur u alchemika/wiedźmy przeszedł pełny rework:
> 1. Wszystkie mikstury dają bonusy procentowe zamiast płaskich wartości: Mała (S) = **5%**, Średnia (M) = **10%**, Duża (L) = **15%**.
> 2. Czasy trwania mikstur wydłużono do długich okresów: Mała (S) = **1 godzina** (60 min), Średnia (M) = **3 godziny** (180 min), Duża (L) = **6 godzin** (360 min).
> 3. Dodano nową serię **Mikstur Łowcy Potworów** (Silne przeciwko potworom): zwiększają obrażenia zadawane wszystkim stworom o 5% / 10% / 15% na 1h / 3h / 6h.
> 4. Mikstury HP i Many w lochach leczą natychmiastowo 5% / 10% / 15% maksymalnego zdrowia lub many, a użyte jako buff zwiększają maksymalny poziom HP i MP o ten sam procent.
