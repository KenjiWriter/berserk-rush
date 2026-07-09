# Moduł: Czarownica i Mikstury (Crafting & Sklep)

## 1. Sklep Alchemiczny i Mikstury Specjalne
Czarownica służy w grze jako punkt handlowy dla przedmiotów typu **consumable** (mikstury użytkowe).
*   **Wywary Specjalne:** Gracz ma możliwość zakupu specjalnej mikstury (+20% doświadczenia) raz na dobę. Ograniczenie realizowane jest przez tabelę `character_cooldowns` (klucz `witch_exp_potion_daily`).
*   **Sklep Alchemiczny:** Wiedźma sprzedaje standardowe mikstury zdefiniowane w `item_templates`. Kupno wymaga odpowiedniej ilości złota, pobiera je z konta gracza i umieszcza nowy obiekt w ekwipunku poprzez `ItemInstance`, a także tworzy wpis w `ItemLedger` potwierdzający zakup u NPC.

## 2. System Warzenia (Crafting)
System craftingu służy do wytwarzania mikstur na podstawie zebranych zasobów (materiałów rzemieślniczych). Oparty jest o tabelę receptur.

### Encja `ItemRecipe`
Przechowuje reguły (przepisy) dla każdego uwarzonego przedmiotu. Zawiera:
-   `id`: ULID
-   `result_item_template_id`: Wskazanie na główny szablon wynikowego przedmiotu (`item_templates`).
-   `ingredients`: Struktura JSON przechowująca mapowanie: `[{"template_id": "material_ulid", "quantity": 2}]`.
-   `gold_cost`: Koszt w złocie, który gracz musi dopłacić za usługę stworzenia mikstury.

### Realizacja (Logika)
Realizacją craftingu zajmuje się wywoływany w akcji interfejsu (Livewire) mechanizm: `CraftItemAction`. Jego kroki to:
1.  **Weryfikacja bazy surowcowej**: Pobranie ekwipunku gracza, sprawdzenie czy zsumowane *stacki* pokrywają *quantity* ze wszystkich składników wymienionych w JSON receptury.
2.  **Weryfikacja środków finansowych**: Sprawdzenie balansu konta (gold).
3.  **Transakcja Odbioru**: Odjęcie złota z postaci. Zmniejszenie `stack_size` odpowiadających `ItemInstance` u gracza (z usuwaniem przedmiotów jeśli ich *stack* osiągnie 0).
4.  **Generacja**: Utworzenie wynikowej mikstury z wpisem w Ekwipunku.
5.  **Rejestracja Historii**: Wpis logów transakcji do `ItemLedger` (`action` => 'crafting', `ref_type` => 'witch_cauldron') na rzecz Idempotency i celów analitycznych.

### Elementy UI
Widok Czarownicy to pełny, ustrukturyzowany panel Livewire z podziałem na zakładki:
*   `special`: Wyświetla dedykowaną potkę lub czas oczekiwania w formie odliczania Alpine.js, opartym o timestamp zapisany w `character_cooldowns`.
*   `shop`: Wylistowuje dostępne mikstury i ich ceny kupna.
*   `crafting`: Lista przepisów. Komponent sam przeszukuje ekwipunek i renderuje brakujące materiały na czerwono, lub gotowe na zielono (kontrolując atrybut `disabled` na przycisku).
