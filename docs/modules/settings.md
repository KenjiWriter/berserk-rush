# Ustawienia Gry

Zakładka "Ustawienia Gry" (`App\Livewire\City\GameSettings`, trasa `city.settings`) pozwala graczowi dostosować dźwięk i powiadomienia. Ustawienia są **czysto klienckie** — brak modelu/tabeli/migracji, wszystko trzymane w `localStorage` przeglądarki, zgodnie z istniejącym wzorcem (np. zwinięcie sidebar w `desktop-nav.blade.php`). Wybór ten jest świadomy: głośność i toasty są renderowane w 100% po stronie klienta (Alpine.js w `components/layouts/app.blade.php`), więc trzymanie ich w bazie wymagałoby zbędnego round-tripu do serwera bez żadnej korzyści poza synchronizacją między urządzeniami.

## Głośność dźwięku
Globalny odtwarzacz audio (`resources/views/components/layouts/app.blade.php`, blok "Global Audio Player") dzieli dźwięki na 3 kategorie i osobno reguluje ich głośność:
- **sfx** — pozostałe efekty (ekwipunek, sklep, zakładki, hover, itd.),
- **levelup** — dźwięk awansu poziomu,
- **combat** — `combat, victory, defeat, hit, crit, dodge`.

Wartości (0-100) są trzymane pod kluczami `berserk_sfx_volume`, `berserk_levelup_volume`, `berserk_combat_volume`. Zmiana suwaka na stronie Ustawień emituje event `settings-volume-changed` (żywa aktualizacja bez przeładowania), a przy każdym pełnym przeładowaniu layoutu wartości i tak są ponownie odczytywane z `localStorage`.

## Powiadomienia o osiągnięciach/questach
`App\Application\Shared\NotificationTracker` zbiera automatyczne komunikaty o postępie questów (`QuestService::progressQuest`) i osiągnięć (`AchievementService::progress`) generowane podczas walki. Jedynym miejscem, które je odczytuje i pokazuje jako toast, jest `App\Livewire\Adventure\MapStub` (po zakończeniu starcia) — dispatchuje event `notify` z `category: 'quest_achievement'`.

Toast-system w layoucie sprawdza klucz `berserk_notify_quest_achievement` w `localStorage` (domyślnie włączone, brak klucza = włączone) i pomija wyświetlenie toastu jeśli wyłączone. Sam progres questa/osiągnięcia jest zawsze zapisywany w bazie niezależnie od tego ustawienia — toggle wpływa wyłącznie na widoczność powiadomienia.

Nie obejmuje to bezpośrednich komunikatów zwrotnych po akcji gracza (np. "Odebrano nagrodę" po kliknięciu w zakładce Questów) — te nie są traktowane jako "powiadomienia", tylko odpowiedź na kliknięcie.
