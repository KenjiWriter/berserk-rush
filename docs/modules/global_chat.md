# Moduł: Globalny Czat (Real-time WebSockets)

## Opis

Globalny czat to panel komunikacji w czasie rzeczywistym, dostępny z każdego miejsca w grze (wyłącznie dla graczy z aktywną postacią). Działa w oparciu o **Laravel Reverb** (WebSockets) i **Laravel Echo** na frontendzie. Czat jest **ulotny** — wiadomości nie są zapisywane do bazy danych i znikają po odświeżeniu strony.

---

## Zakres funkcjonalności

### Wyświetlanie i układ
- Panel czatu jest przypięty do dolnego-prawego rogu ekranu (`fixed bottom-0 right-0`).
- Możliwość **minimalizacji** do małego dymka i ponownego rozwinięcia kliknięciem.
- Mroczny, półprzezroczysty design w klimacie fantasy z czcionką **Cinzel**.
- Wyświetlanie maksymalnie **100 ostatnich wiadomości** (tylko w pamięci przeglądarki dla bieżącej sesji).

### Wysyłanie wiadomości
- Dostępne **wyłącznie dla zalogowanego gracza z aktywną postacią** (weryfikacja przez `session('active_character')`).
- **Anty-spam (Rate Limiting):** 1 wiadomość na 2 sekundy per postać (via `Illuminate\Support\Facades\RateLimiter`).
- Maksymalna długość wiadomości: **200 znaków**.

### Format wiadomości
```
NazwaPostaci [Poziom]: Treść wiadomości
```

### Inspekcja gracza (Tooltip)
- Kliknięcie na **nick** dowolnego gracza w czacie otwiera tooltip z jego profilem.
- Dane ładowane są **lazily** (tylko na żądanie kliknięcia) z bazy danych.
- Tooltip zawiera:
  - Nazwa i Poziom postaci
  - **Combat Power (CP)**
  - Lista założonego ekwipunku z poziomem ulepszenia (`+X`) i CP każdego przedmiotu
  - Kolor nazwy przedmiotu zależny od rzadkości (common, uncommon, rare, epic, legendary)

---

## Architektura techniczna

### Backend

| Plik | Rola |
|------|------|
| `app/Domain/Social/Events/MessageSent.php` | Event broadcastowy (`ShouldBroadcastNow`), rozgłaszany na publicznym kanale `global-chat` |
| `app/Livewire/Global/GlobalChatComponent.php` | Komponent Livewire 3 obsługujący wysyłanie, odbieranie i załadowanie danych tooltipa |
| `routes/channels.php` | Definicja publicznego kanału `global-chat` |

### Frontend

| Plik | Rola |
|------|------|
| `resources/js/bootstrap.js` | Inicjalizacja `Laravel Echo` z driverem `reverb` i odczytem kluczy z `VITE_REVERB_*` |
| `resources/views/livewire/global/global-chat-component.blade.php` | Widok komponentu czatu (Alpine.js do auto-scroll i tooltipa) |
| `resources/views/components/layouts/app.blade.php` | Wstrzyknięcie komponentu w layout (warunek: `@auth && session('active_character')`) |

### Kanał broadcastowy

```php
// routes/channels.php
Broadcast::channel('global-chat', fn () => true); // Publiczny — brak autoryzacji
```

### Nasłuchiwanie eventów w Livewire

```php
#[On('echo:global-chat,MessageSent')]
public function onMessageReceived(array $event): void
{
    $this->messages[] = [...];
}
```

---

## Konfiguracja środowiska

Po uruchomieniu `php artisan reverb:install` w pliku `.env` automatycznie pojawiają się:

```dotenv
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=<generated>
REVERB_APP_KEY=<generated>
REVERB_APP_SECRET=<generated>
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

---

## Uruchamianie

Czat wymaga działającego procesu serwera WebSocket. Polecenie `composer dev` uruchamia go automatycznie razem z pozostałymi procesami:

```bash
composer dev
# uruchamia: php artisan serve | php artisan queue:listen | npm run dev | php artisan reverb:start
```

Lub ręcznie w osobnym terminalu:

```bash
php artisan reverb:start
```

---

## Zależności

### PHP (Composer)
- `laravel/reverb` — serwer WebSocket

### JavaScript (npm)
- `laravel-echo` — klient WebSocket dla frontendowych subskrypcji
- `pusher-js` — wymagany przez Echo jako driver transportu

---

## Ograniczenia i znane zachowania

- **Czat jest ulotny** — brak historii po odświeżeniu strony (deliberate design decision).
- Czat jest **niewidoczny na stronie głównej i na stronach przed wyborem postaci** — tylko zalogowani gracze z aktywną sesją postaci widzą panel.
- Reverb musi działać jako osobny proces — w środowisku produkcyjnym należy skonfigurować go jako usługę systemową (np. `supervisor`).
