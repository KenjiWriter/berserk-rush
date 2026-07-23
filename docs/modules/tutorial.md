# Moduł Samouczka (Tutorial)

System samouczka wprowadza nowych graczy do mechanik gry "Berserk Rush". Opiera się na sekwencji interaktywnych okienek dialogowych, w których "Kapitan" obozu tłumaczy zasady gry oraz nagradza gracza za postępy. 

## Architektura i Koncepcja

Postęp samouczka jest śledzony poprzez pole `game_stage` w modelu `User` (a nie `Character`). Oznacza to, że samouczek jest przypisany do konta gracza (jeśli gracz stworzy kolejną postać, `game_stage` nie resetuje się samoistnie dla samego użytkownika, choć docelowo powiązanie postępu z użytkownikiem zapobiega powielaniu bonusów przy zakładaniu nowych postaci). 

### Główne Komponenty

1. **`TutorialOverlay` (Livewire Component)**:
   - Plik: `app/Livewire/Global/TutorialOverlay.php`
   - Widok: `resources/views/livewire/global/tutorial-overlay.blade.php`
   - Odpowiada za wyświetlanie dymków dialogowych Kapitana.
   - Może przyjmować parametry z nagrodami: `$rewardItemTemplateId`, `$rewardXp`, `$rewardGold`.
   - Funkcja `nextStep()` inkrementuje `game_stage` o 1, przydziela nagrody postaci (jeśli istnieją) i emituje zdarzenie `tutorial-completed`.

2. **Warunkowe renderowanie**:
   - Komponent samouczka jest dodawany w różnych widokach gry (Hub, Profil, Brońmistrz, Wyprawy), owinięty w instrukcje warunkowe `@if(auth()->user()->game_stage == X)`.
   - Elementy interfejsu kluczowe dla danego kroku samouczka otrzymują klasy animacji (np. `animate-pulse`, `ring-4 ring-amber-500`), aby zwrócić uwagę gracza.

3. **Nasłuchiwanie Zdarzeń**:
   - Większość widoków korzystających z samouczka nasłuchuje na zdarzenie `tutorial-completed` (np. przez atrybut `#[On('tutorial-completed')]`), aby odświeżyć stan interfejsu (np. by po zamknięciu okienka zaczął pulsować wybrany przycisk).

## Przebieg Wstępnego Treningu

Podstawowy ciąg nauki gry przez nowicjusza (game_stage od 0 do 21):

- **Tworzenie i Profil (game_stage 0-3)**: Gracz poznaje swojego przewodnika, zapoznaje się z panelem postaci, dostaje pierwsze punkty statystyk i uczy się ich rozdawania.
- **Ekwipunek (game_stage 4-7)**: Otrzymanie zardzewiałego miecza, instrukcja przejścia do plecaka i zakładania przedmiotu. Za poprawne wyposażenie miecza przyznawane jest nagrodowe doświadczenie i gracz awansuje na wyższy poziom.
- **Walka (game_stage 8-13)**: Przejście do mapy wypraw (Przygoda). Opis World Bossa, list przeciwników. Kapitan każe wyruszyć w pierwszy bój (poziomy 0-15). Podczas pierwszej walki wyłączony jest system `auto-battle`, aby gracz mógł śledzić mechanikę. Po wygranej dostaje łup z potwora oraz bonusowy Hełm od Kapitana.
- **Rozwój Po Walce (game_stage 13-16)**: Powrót do miasta i rozdanie punktów statystyk z awansu poziomu po walce. Za wykonanie tego zadania gracz dostaje 150 złota.
- **Brońmistrz i Ulepszenia (game_stage 17-20)**: Kapitan zabiera gracza do Brońmistrza, objaśnia mechaniki kupna, sprzedaży oraz Kuźni Ulepszeń (+0 do +9). Gracz dostaje zadanie kupienia "Miecza Nowicjusza". 
- **Zakończenie (game_stage 20 -> 21)**: Powrót do miasta i otrzymanie nagrody finałowej – Skórzanej Zbroi. Po tym etapie gracz jest wolny od samouczka pierwszego etapu.
- **Tablica Wyzwań i Osiągnięcia (game_stage 22-30)**: Aktywowane po wbiciu 5 poziomu postaci. Kapitan ponownie wita gracza, zachęca do odwiedzenia Tablicy Wyzwań, instruuje jak odbierać misje, wykonuje się przykładową misję, odbiera nagrodę, po czym Kapitan przedstawia system Osiągnięć.
- **Czarodziej i Zaklinanie Przedmiotów (game_stage 30-34)**: Po powrocie do Głównego Obozu Kapitan informuje gracza, że kolejnym mieszkańcem jest Czarodziej. Kafel Czarodzieja w Hubie zostaje podświetlony. Po przejściu do Czarodzieja Kapitan oprowadza gracza po mechanice zaklinania (dodawanie bonusów) i zleca pomyślne zaklecie dowolnego przedmiotu. Po wykonaniu zadania z sukcesem gracz otrzymuje nagrodę 200 EXP oraz 250 Golda.

## Implementacja na przyszłość

Aby w przyszłości rozszerzyć samouczek (np. o system Chowańców/Petów), wystarczy:
1. Rozszerzyć logikę wyświetlania tekstu w `tutorial-overlay.blade.php` pod nowymi indeksami `$step`.
2. Dodać instrukcje warunkowe `@elseif` do widoków odpowiedzialnych za nowe mechaniki.
3. Ewentualnie podpiąć event listener `tutorial-completed` w nowych kontrolerach Livewire.
