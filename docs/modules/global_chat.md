# Moduł: Globalny Czat (Real-time WebSockets)

## Opis

Czat to wielokanałowy panel komunikacji w czasie rzeczywistym, dostępny z każdego miejsca w grze (wyłącznie dla graczy z aktywną postacią). Działa w oparciu o **Laravel Reverb** (WebSockets) i **Laravel Echo** na frontendzie. Czat jest **ulotny** — wiadomości nie są zapisywane do bazy danych i znikają po odświeżeniu strony, ale stan samego panelu (zwinięty/rozwinięty) jest zapamiętywany pomiędzy zapytaniami.

---

## Zakres funkcjonalności

### Wyświetlanie, układ i sesja
- Panel czatu jest przypięty do dolnego-prawego rogu ekranu (`fixed bottom-0 right-0`).
- Możliwość **minimalizacji** do małego przycisku. Stan zwinięcia/rozwinięcia jest **zapamiętywany w sesji** dzięki Livewire 3 `#[Session]`.
- Jeśli czat jest zwinięty, na przycisku pojawiają się **kolorowe liczniki nieprzeczytanych wiadomości** osobno dla kanału globalnego i gildii.
- Wyświetlanie maksymalnie **100 ostatnich wiadomości** (tylko w pamięci przeglądarki dla bieżącej sesji).

### Kanały komunikacji
- **Globalny:** Widoczny dla wszystkich graczy w grze.
- **Gildia:** Widoczny wyłącznie dla członków tej samej gildii. System automatycznie przełącza graczy między kanałami na ich żądanie.

### Wysyłanie wiadomości i komendy
- Dostępne **wyłącznie dla zalogowanego gracza z aktywną postacią** (weryfikacja przez `session('active_character')`).
- **Anty-spam (Rate Limiting):** 1 wiadomość na 2 sekundy per postać.
- Maksymalna długość wiadomości: **200 znaków**.
- **System komend (autocomplete):** Wpisanie `/` na kanale gildii sugeruje listę dostępnych komend (np. dotacje do skarbca).
  - `/donate exp <ilość>`
  - `/donate gold <ilość>`
  - `/donate gems <ilość>`

### Format wiadomości
```
12:34 [System]: Gracz WojWielki przekazał 100 EXP na rozwój gildii.
12:35 NazwaPostaci [Poziom]: Treść wiadomości
```

### Inspekcja gracza i zaproszenia (Tooltip)
- Kliknięcie na **nick** dowolnego gracza w czacie otwiera tooltip z jego profilem.
- Dane ładowane są **lazily** (tylko na żądanie kliknięcia) z bazy danych za pomocą Livewire.
- Tooltip zawiera:
  - Nazwa, Poziom postaci oraz jej Combat Power (CP)
  - Lista założonego ekwipunku z poziomem ulepszenia (`+X`) i CP każdego przedmiotu.
  - Przycisk **"Wyślij zaproszenie do gildii"** (widoczny i klikalny dla Liderów i Dowódców), wysyłający pocztą w grze paczkę z zaproszeniem do odpowiedniego gracza.

---

## Architektura techniczna

### Backend

| Plik | Rola |
|------|------|
| `app/Domain/Social/Events/MessageSent.php` | Event broadcastowy, rozgłaszany na publicznym kanale `global-chat` |
| `app/Domain/Social/Events/GuildMessageSent.php` | Event broadcastowy na prywatnym kanale `guild-chat.{id}` |
| `app/Livewire/Global/GlobalChatComponent.php` | Komponent Livewire obsługujący odbiór, wysyłanie, komendy, stany tooltipów, liczniki powiadomień |

### Nasłuchiwanie eventów w Livewire

Do nasłuchiwania dynamicznych kanałów (takich jak `guild-chat.{id}`) używamy metody `getListeners()` (zamiast samej adnotacji `#[On]`):

```php
public function getListeners()
{
    $listeners = [
        'echo:global-chat,.App\\Domain\\Social\\Events\\MessageSent' => 'onMessageReceived',
    ];

    $characterId = session('active_character');
    // ... jeśli character ma gildie:
    // $listeners["echo:guild-chat.{$guild_id},.App\\Domain\\Social\\Events\\GuildMessageSent"] = 'onGuildMessageReceived';

    return $listeners;
}
```

Zwróć uwagę, że zdefiniowane w Laravelu eventy używają domyślnie pełnej ścieżki klasy (`FQCN`), stąd podczas nasłuchiwania dodawana jest kropka (`.App\Domain...`), która powiadamia Laravel Echo, aby pominąć doklejanie domyślnego namespace'u.

---

## Uruchamianie i Zależności

Czat wymaga działającego procesu serwera WebSocket. Polecenie `composer dev` uruchamia go automatycznie:

```bash
composer dev
# lub ręcznie: php artisan reverb:start
```
