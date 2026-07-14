# Instrukcje dla Agenta AI: Gdzie i jak modyfikować moduły

Ten plik stanowi globalną regułę dla agentów AI pracujących nad projektem Berserk Rush. Określa on, w jakich katalogach znajdują się poszczególne warstwy logiki i jak poprawnie wprowadzać zmiany w modułach.

## 1. Architektura i Lokalizacja Plików (Gdzie modyfikować?)

Projekt stosuje koncepcje Domain-Driven Design (DDD) i CQRS-lite. Każdy moduł (np. `Characters`, `Combat`, `Loot`, `Economy`) jest podzielony na następujące warstwy:

- **`app/Application/{NazwaModulu}/`** (np. `app/Application/Combat/`)
  - **Akcje/Serwisy (Actions/Services):** Tutaj dodawaj klasy wykonujące główne operacje biznesowe (np. `EncounterService`, `UpgradeItem`).
  - **DTO (Data Transfer Objects):** Używaj ich do przekazywania ustrukturyzowanych danych do akcji.
  - Zmiany: Modyfikuj, gdy zmieniasz przepływ procesu (kroki wykonywane po sobie, wywoływanie transakcji bazy danych).

- **`app/Domain/{NazwaModulu}/`**
  - **Logika Biznesowa (Reguły, Value Objects):** Czysta logika, która nie zależy od frameworka (wzory na obrażenia, szanse, walidacje dziedzinowe).
  - **Zdarzenia (Events):** Klasy zdarzeń dziedzinowych (np. `CharacterLeveledUp`, `ItemUpgraded`). 
  - Zmiany: Modyfikuj, gdy dodajesz nową regułę biznesową lub gdy system musi emitować nowy typ zdarzenia.

- **`app/Infrastructure/Persistence/`** (lub ogólnie podkatalogi infrastruktury, jeśli stosowane)
  - **Modele Eloquent:** Modele bazy danych odpowiadające tabelom. W grze używamy ULID dla kluczy głównych oraz `jsonb` do zapisywania statystyk.
  - Zmiany: Modyfikuj przy dodawaniu nowych relacji w bazie danych, dodawaniu scope'ów czy modyfikacji castowania dla kolumn bazodanowych (np. dla atrybutów JSONB).
  
- **`app/Livewire/`** i **`resources/views/livewire/`**
  - **Komponenty UI:** Klasy PHP oraz widoki Blade obsługujące responsywny i dynamiczny interfejs użytkownika.
  - Zmiany: Modyfikuj przy implementowaniu front-endu (nowe widoki, integracja z wprowadzonymi Serwisami).

## 2. Jak modyfikować moduł? (Zasady modyfikacji)

1. **Wzorzec Result / Unikanie Wyjątków:** 
   - Nie rzucaj wyjątków dla błędów biznesowych (np. "Brak złota", "Zbyt niski poziom", "Brak wolnego miejsca w ekwipunku").
   - Akcje muszą zawsze zwracać obiekt opakowujący sukces/błąd (np. `Result::ok($data)` lub `Result::error('wiadomosc_bledu')`).
   - Komponenty Livewire lub Kontrolery interpretują ten obiekt w celu wyświetlenia odpowiednich powiadomień użytkownikowi.

2. **Idempotentność i Bezpieczeństwo Zmian (Transakcje):**
   - Każdą operację modyfikującą kluczowe dane, w szczególności zasoby (złoto, klejnoty, przedmioty), należy obejmować transakcją bazodanową (`DB::transaction`).
   - Przestrzegaj założeń wpisywania danych audytowych (`CurrencyLedger`, `ItemLedger`), aby zapobiec double-spendingowi lub problemom z siecią. Wykorzystuj do tego zdefiniowane klucze idempotentności (`idempotency_key`), gdy przewiduje to logika.

3. **Struktury Danych JSONB:**
   - Modele takie jak postać (bohater) czy przedmioty trzymają złożone statystyki w kolumnach JSONB (np. `attributes`, `roll_stats`). Zawsze weryfikuj format i klucze zawarte w tych strukturach zgodnie z opisem domeny, aby nie nadpisać istniejących parametrów błędem literowym (np. `STR` vs `str`).

4. **Event-Driven Side Effects (Zdarzenia Uboczne):**
   - Jeśli po zakończeniu walki ma stać się coś pobocznego (np. wysłanie maila w grze, zaktualizowanie rankingu najlepszych graczy), nie dodawaj tego bezpośrednio w usłudze (`EncounterService`). 
   - Wyemituj zdarzenie (np. `EncounterFinished`), a dedykowany nasłuchiwacz (Listener) na to zdarzenie powinien zareagować i asynchronicznie (w kolejce) wykonać zadanie.

5. **Powiadomienia i Śledzenie Postępu (NotificationTracker):**
   - Aby pokazać graczowi progres misji lub osiągnięcia (szczególnie podczas walk rozstrzyganych w tle przez Joby na kolejkach), używaj `app(\App\Application\Shared\NotificationTracker::class)`.
   - `NotificationTracker` opiera się na **właściwościach statycznych (`static`)**, co pozwala uniknąć błędów nieodświeżania cyklu pamięci przez worker procesujący kolejki.
   - Proces walki (np. `EncounterService`) pobiera zapamiętane powiadomienia wywołując `flush()` i dołącza je do `combat_data`. Frontend (Livewire) odczytuje te dane i dispatchuje UI toasty (`dispatch('notify')`).

6. **Animacje UI i Okienka (Eventy Frontendowe):**
   - Aplikacja posiada bogate mechanizmy Alpine.js na frontendzie (np. `rewardInfobox`).
   - Gdy dodajesz graczowi walutę, wyślij zdarzenie, by uruchomić spójną animację lotu monet: `$this->dispatch('trigger-reward-animation', type: 'gold', amount: $amount)`.
   - Przy osiąganiu nowego poziomu używaj globalnego modala (np. z poziomu mapy): `$this->dispatch('open-level-up-modal', level: $newLevel)`. Dodawaj również odpowiedni dźwięk (`$this->dispatch('play-audio', type: 'levelup')`).

7. **Aktualizacja Dokumentacji:**
   - W przypadku dodania nowej mechaniki (np. System Rzemiosła), odnotuj to, dodając nowy plik dokumentacji do katalogu `docs/modules/`. Sprawdź również plik `roadmap.md` by zaznaczyć ewentualny postęp.

## 3. Podsumowanie Typowego Cyklu Pracy Agenta
1. **Analiza Zamiaru:** Upewnij się, z jakim modułem (np. `Loot`, `Combat`) masz do czynienia. Sprawdź ewentualne instrukcje specyficzne dla modułu w `docs/modules/`.
2. **Warstwa Danych:** W razie potrzeby utwórz migrację i zaktualizuj odpowiedni Model Eloquent.
3. **Logika Dziedzinowa i Zdarzenia:** Utwórz Value Objects lub zdarzenia dziedzinowe w `app/Domain/...`.
4. **Serwisy (Aplikacja):** Zaimplementuj główną logikę w nowym lub istniejącym serwisie (Action) wewnątrz `app/Application/...`, dbając o zwracanie obiektów `Result` oraz obwijanie zmian w `DB::transaction`.
5. **Integracja (UI):** Podłącz serwis w odpowiednim komponencie w `app/Livewire/...`.
