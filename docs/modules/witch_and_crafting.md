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
4.  **Mechanika Rzadkości (Rarity)**: Dla sprzętu bojowego (broń, zbroja) gra losuje szansę na lepszą jakość (Common 70%, Uncommon 20%, Rare 8%, Epic 1.9%, Legendary 0.1%). Wylosowanie lepszej rzadkości dodaje bonusowe statystyki (`roll_stats`) i zwiększa Combat Power przedmiotu.
5.  **Generacja**: Utworzenie wynikowego przedmiotu z wpisem w Ekwipunku gracza.
6.  **Rejestracja Historii**: Wpis logów transakcji do `ItemLedger` (`action` => 'crafting', `ref_type` => 'crafting_service') na rzecz Idempotency i celów analitycznych.

### Panel Administratora
Gra posiada pełnoprawny widok graficzny zarządzania przepisami w zakładce Administracji (`ItemRecipes.php`). Administrator może ustalać dowolne przedmioty wynikowe, koszty złota, oraz dynamicznie dodawać i usuwać potrzebne materiały. Asortyment sklepu wiedźmy konfiguruje się natomiast w zakładce Handlarzy (`MerchantItems.php`).

### Elementy UI
Widok Czarownicy (`Witch.php`), Kowala Broni (`Weaponsmith.php`) oraz Płatnerza (`Armorsmith.php`) posiadają dedykowane zakładki.
Wiedźma posiada podział na dwie główne zakładki:
*   `shop`: Wylistowuje dostępne mikstury z modelu `MerchantItem` oraz wyróżnioną ofertę na miksturę doświadczenia (limitowaną dziennie opartą o `character_cooldowns`).
*   `crafting`: Lista przepisów alchemicznych. Komponent sam przeszukuje ekwipunek i renderuje postęp zebrania składników wizualnie, kontrolując aktywność przycisku "Uwarz".
