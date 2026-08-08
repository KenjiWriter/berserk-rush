# Moduł: Globalny Czat (Real-time WebSockets)

## Opis

Czat to wielokanałowy panel komunikacji w czasie rzeczywistym, dostępny z każdego miejsca w grze (wyłącznie dla graczy z aktywną postacią). Działa w oparciu o **Laravel Reverb** (WebSockets) i **Laravel Echo** na frontendzie. Czat jest **ulotny** — wiadomości nie są zapisywane do bazy danych i znikają po odświeżeniu strony, ale stan samego panelu (zwinięty/rozwinięty) jest zapamiętywany pomiędzy zapytaniami.

---

## Zakres funkcjonalności

### Widoczność (ukrywanie) czatu
Niezależnie od stanu zwinięcia/rozwinięcia, gracz może całkowicie ukryć dok czatu z poziomu zakładki Ustawień Gry (`city.settings`) — na stałe lub tymczasowo (10/20/30/60 min). Mechanizm jest w pełni kliencki (`localStorage`, brak zapisu w bazie) — patrz `docs/modules/settings.md#widoczność-czatu` po szczegóły kluczy i eventów. W stanie ukrytym cały dok jest niewidoczny (`x-show`), ale komponent Livewire i subskrypcja WebSocket działają dalej w tle, więc liczniki nieprzeczytanych wiadomości są aktualne po ponownym pokazaniu.

### Wyświetlanie, układ i sesja
- Panel czatu jest przypięty przy dolnej krawędzi ekranu (`fixed bottom-16 lg:bottom-0 right-2 sm:right-4`) w formie eleganckiego prostokątnego doku z zaokrąglonymi górnymi rogami, wyeliminowując problem zasłaniania UI gry przez dawny dymek.
- Możliwość **minimalizacji/rozsuwania**: w stanie zwiniętym czat stanowi zwartą, prostokątną beleczkę nagłówkową na dole ekranu. Kliknięcie w dowolne miejsce w belce powoduje płynne rozsuwanie się panelu wiadomości do góry (efekt slide-up). Stan zwinięcia/rozwinięcia oraz **historia wiadomości (z ostatnich 10 minut)** są zapamiętywane w sesji dzięki Livewire 3 `#[Session]`.
- **Autoukrywanie bezczynności:** Gdy czat jest zwinięty, po 2 sekundach braku interakcji (dotknięć, ruchów myszą, skrolowania) beleczka czatu płynnie wygasza się (`opacity-0 pointer-events-none`), aby nie przysłaniać interfejsu ani przycisków dolnego menu. Zdarzenia dotyku (`touchstart`/`pointerdown`) przy ukrytym czacie nie przywracają natychmiast widoczności w trakcie trwania kliknięcia — dzięki temu pierwsze kliknięcie w przycisk gry znajdujący się pod wygaszonym czatem wykonywane jest natychmiastowo bez konieczności "wybudzania" interfejsu drugim kliknięciem. Widoczność przywracana jest bezkolizyjnie po 400 ms, przy skrolowaniu, nadejściu nowej wiadomości lub ruchu myszą.
- **Hierarchia warstw (Z-Index):** Gdy czat jest zwinięty, jego wskaźnik z-index wynosi `z-[9910]`, dzięki czemu wysuwany arkusz menu mobilnego (`z-[9945]`) nakłada się czysto nad czatem i nie blokuje dolnych przycisków nawigacji (np. przycisku "Powrót do Lobby"). Po rozwinięciu czat otrzymuje `z-[9960]`.
- Jeśli czat jest zwinięty, na prostokątnej belce nagłówkowej wyświetlają się **kolorowe liczniki nieprzeczytanych wiadomości** osobno dla kanału globalnego i gildii oraz ikona rozsuwania (`fa-chevron-up`).
- Wyświetlanie maksymalnie **100 ostatnich wiadomości**. Wiadomości starsze niż 10 minut są automatycznie usuwane przy ładowaniu strony.

### Kanały komunikacji
- **Globalny:** Widoczny dla wszystkich graczy w grze.
- **Gildia:** Widoczny wyłącznie dla członków tej samej gildii. System automatycznie przełącza graczy między kanałami na ich żądanie.

### Wysyłanie wiadomości i komendy
- Dostępne **wyłącznie dla zalogowanego gracza z aktywną postacią** (weryfikacja przez `session('active_character')`).
- **Wymóg Akceptacji Regulaminu Czatu (Wyłączenie Odpowiedzialności / UGC):** Przed pierwszym wysłaniem wiadomości gracz musi zaakceptować Regulamin Czatu (`chat_terms_accepted_at` w tabeli `users`). Dopóki regulamin nie zostanie zaakceptowany, pole wpisywania wiadomości jest zablokowane i wyświetla przycisk akceptacji/podglądu regulaminu (`/regulamin-czatu`).
- **Anty-spam (Rate Limiting):** 1 wiadomość na 2 sekundy per postać.
- Maksymalna długość wiadomości: **200 znaków**.
- **System komend (autocomplete):** Wpisanie `/` na kanale gildii sugeruje listę dostępnych komend (np. dotacje do skarbca).
  - `/donate exp <ilość>`
  - `/donate gold <ilość>`
  - `/donate gems <ilość>`
- **Komendy administratora (Game Master):** (wymagany `permission_level == 9`)
  - `/give pet <nazwa_peta>` — Precyzyjnie generuje wskazanego chowańca (np. `/give pet Mroczny Smok`). System wyszukuje odpowiedni wzorzec w `PetTemplate` po nazwie, po czym automatycznie przypisuje nowego zwierzaka na 1. poziomie wprost do postaci gracza (z jego bazowymi statystykami i stopniem rzadkości).
  - `/give <item_id> <ilość>` — Dodaje określoną ilość wskazanego przedmiotu do ekwipunku postaci.
  - `/give gold <ilość>`, `/give gems <ilość>` — Dodaje waluty.
  - `/exp <ilość>` — Dodaje punkty doświadczenia.
  - `/set level <poziom>`, `/set sp <ilość>` — Ustawia poziom postaci lub dodaje punkty atrybutów.
- **Integracja z Discordem:** (dostępne dla każdego gracza, patrz sekcja "Integracja z Discordem" poniżej)
  - `/discord` — generuje jednorazowy kod (ważny 10 minut) do połączenia postaci z kontem Discord.
  - `/discord unlink` — usuwa istniejące połączenie postaci z Discordem.

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

---

## Integracja z Discordem

Kanał globalny jest dwukierunkowo zsynchronizowany z kanałem `#in-game-chat` na Discordzie serwera gry.

### Kierunek: gra → Discord (relay przez Webhook)

Każda wiadomość rozgłoszona eventem `MessageSent` (o ile `fromDiscord === false`) jest asynchronicznie (przez kolejkę) wysyłana jako POST na Discord Incoming Webhook.

| Plik | Rola |
|------|------|
| `app/Listeners/ForwardGlobalChatMessageToDiscord.php` | Listener na `MessageSent` (auto-discovery), wysyła wiadomość na webhook Discorda |
| `config/services.php` → `discord.global_chat_webhook_url` | URL webhooka (`DISCORD_GLOBAL_CHAT_WEBHOOK_URL` w `.env`) |

Konfiguracja: Discord → Ustawienia kanału `#in-game-chat` → Integracje → Webhooks → Nowy webhook → skopiuj URL do `.env`.

### Kierunek: Discord → gra (bot bridge)

Osobny, długo działający proces bota **odpytuje REST API Discorda co ~2 sekundy** (`GET /channels/{id}/messages`, przez zwykły `Illuminate\Support\Facades\Http` — bez żadnej dodatkowej paczki Composera) i rozgłasza nowe wiadomości do gry jako `MessageSent` z flagą `fromDiscord: true` (dzięki czemu `ForwardGlobalChatMessageToDiscord` ich nie odbija z powrotem na Discorda).

Świadomie **nie** użyto biblioteki gateway/WebSocket (`team-reflex/discord-php`) — jej drzewo zależności (react/promise, guzzle, carbon, discord-php/http) koliduje z wersjami już zablokowanymi przez ten projekt (m.in. przez Reverb) i nie da się tego rozwiązać bez ryzykownego wymuszania downgrade'ów w całej aplikacji. Odpytywanie REST co kilka sekund kosztuje tyle, że wiadomość z Discorda pojawia się w grze z opóźnieniem rzędu ~2s zamiast natychmiast — akceptowalny kompromis jak na most czatu.

| Plik | Rola |
|------|------|
| `app/Console/Commands/DiscordChatBridgeCommand.php` | Komenda `php artisan discord:bridge` — długo działający proces bota (uruchamiany pod Supervisorem, tak jak `reverb:start`/`queue:work`) |

**Singleton lock:** Każda wiadomość ma swój lokalny w pamięci kursor pollingu (`$lastMessageId`), więc jeśli dwa procesy `discord:bridge` działają jednocześnie (np. ktoś ręcznie odpalił drugi obok tego z Supervisora), oba wykryją tę samą nową wiadomość jako nową i oba spróbują ją rozgłosić — stąd duplikaty w czacie w grze (czasem pod inną postacią, jeśli w międzyczasie zmieniło się powiązanie kont). Żeby to wykluczyć u źródła, `handle()` przy starcie zdobywa blokadę cache'a (`discord_bridge_singleton_owner`, TTL 45s, odświeżana co iterację pętli); drugi proces odmawia startu z czytelnym błędem, a proces, który zawiśnie dłużej niż TTL, sam się zatrzymuje zamiast dalej odpytywać bez blokady. To dodatkowa warstwa obok istniejącego per-wiadomościowego `Cache::add('discord_bridge_msg:'.$id, ...)`.
| `app/Infrastructure/Persistence/DiscordLinkCode.php` | Model jednorazowych kodów łączenia postaci z kontem Discord (tabela `discord_link_codes`) |
| `characters.discord_user_id` | Kolumna z ID (snowflake) połączonego konta Discord — jedna postać na jedno konto Discord |
| `characters.discord_link_reward_claimed_at` | Znacznik czasu przyznania jednorazowej nagrody za połączenie (patrz niżej) — chroni przed farmieniem przez unlink/relink |
| `app/Livewire/Global/DiscordLinkModal.php` | Stały modal na środku ekranu z kodem (zamiast znikającej notyfikacji), otwierany eventem `open-discord-link-modal` |

**Łączenie konta (linking):**
1. Gracz wpisuje `/discord` na czacie w grze → dostaje jednorazowy kod (np. `ABC123`, ważny 10 min) w modalu na środku ekranu, z przyciskiem do skopiowania.
2. Na kanale `#in-game-chat` na Discordzie wpisuje `!link ABC123`.
3. Bot zapisuje `discord_user_id` gracza na jego postaci i potwierdza połączenie na Discordzie.
4. Od tej pory wiadomości tego gracza na Discordzie pojawiają się na czacie globalnym w grze pod nazwą jego postaci (poziom, tytuł, odznaki premium/moda/admina — jak przy normalnej wiadomości).

Wiadomości od niepołączonych kont Discorda są ignorowane, a bot odpowiada z instrukcją jak wykonać `/link`.

**Nagroda za połączenie:** Przy pierwszym udanym `!link` dla danej postaci bot wysyła jej pocztą w grze (`SendMailAction`, załącznik `{type: 'gems', qty: 200}`) jednorazową nagrodę **200 diamentów** — gracz odbiera ją normalnie ze skrzynki pocztowej. Znacznik `discord_link_reward_claimed_at` gwarantuje, że nagroda jest przyznawana tylko raz na postać, nawet po `/discord unlink` i ponownym połączeniu (ewentualnie z innym kontem Discord).

**Wymagana konfiguracja (`.env`):**
```
DISCORD_BOT_TOKEN=              # token bota z Discord Developer Portal
DISCORD_CHAT_CHANNEL_ID=        # ID kanału #in-game-chat (Developer Mode -> Copy Channel ID)
DISCORD_UPDATE_LOG_CHANNEL_ID=  # ID kanału #update-log (domyślnie 899078131728650272)
```

Bot wymaga uprawnienia **Message Content Intent** (Privileged Gateway Intents w Discord Developer Portal) oraz zaproszenia na serwer z uprawnieniami "View Channel" i "Send Messages" na kanale `#in-game-chat` oraz `#update-log`.

Lokalnie proces bota jest częścią `composer dev` (obok `server`/`queue`/`vite`/`reverb`). Na produkcji patrz `docs/deployment_guide.md` — dodatkowy proces Supervisora `berserk-discord-bridge`.

---

### Integracja Update-log (Discord ↔ Panel Admina / Aktualności)

Proces bota (`php artisan discord:bridge`) automatycznie nasłuchuje i odpytuje również kanał **Update-log** (`DISCORD_UPDATE_LOG_CHANNEL_ID`, np. `899078131728650272`).

#### Format ogłoszeń na Discordzie:
```text
@Update-log notification
AKTUALIZACJA [wersja: beta 0.2.4]!
Wprowadzone zmiany:

• Kompletny rework systemu Petów...
```

#### Automatyczne parsujące zasady:
1. Usunięcie wzmianek ról (np. `@Update-log notification`, `<@&...>`).
2. Pierwsza linia tekstu staje się **Tytułem** (np. `AKTUALIZACJA [wersja: beta 0.2.4]!`).
3. Reszta treści tworzy **Opis / Treść zmiań**.
4. Wpis zapisywany jest w tabeli `news` z unikalnym `discord_message_id` i trafia bezpośrednio do Panelu Admina (`/admin/news`) oraz na Stronę Główną gry (`/`). Edycja wiadomości na Discordzie automatycznie aktualizuje treść w grze.
5. W Panelu Admina istnieje również możliwość bezpośredniego wysłania wpisu z gry na kanał Discord Update-log za pomocą przycisku **"Wyślij na DS"**.

