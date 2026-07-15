# 🔐 Social Login & Authentication Module

Ten dokument opisuje architekturę i przepływ autoryzacji z wykorzystaniem zewnętrznych dostawców tożsamości (Social Login).

## 📌 Przegląd

Aplikacja **Berserk Rush** oferuje graczom możliwość założenia konta i logowania przy użyciu zewnętrznych dostawców OAuth2 (Social Login), takich jak Google i Facebook (opcjonalnie Apple). Moduł oparty jest na pakiecie [Laravel Socialite](https://laravel.com/docs/socialite).

Głównym wyzwaniem gry RPG z logowaniem społecznościowym jest konieczność wyboru **nazwy postaci**, która nie jest przekazywana przez Google/Facebooka (albo nie nadaje się na nick w grze, np. imię i nazwisko).

## 🛠 Flow Rejestracji i Logowania (Socialite)

1. **Przekierowanie do dostawcy**
   Użytkownik klika na przycisk "Zaloguj przez [Dostawca]". System generuje specjalny link i przekierowuje użytkownika na stronę autoryzacyjną (np. Google).

2. **Callback i odbiór danych**
   Po udanym logowaniu dostawca odsyła użytkownika do endpointu `/auth/{provider}/callback`. W tym miejscu kontroler `SocialLoginController` w bloku `try...catch` pobiera z tokena dane użytkownika (np. adres email, ID społecznościowe). W środowisku lokalnym (dla testów na systemach Windows/Laragon), kontroler używa specjalnego obejścia blokującego weryfikację certyfikatów SSL, omijając powszechny błąd cURL (cURL 77/60).

3. **Pobieranie rozszerzonych informacji z Facebooka (Opcjonalnie)**
   Dla dostawcy `facebook`, dodane są zaawansowane parametry zapytania (`scopes`). System zaciąga pola takie jak `gender`, `birthday`, `age_range`, `location`, `hometown` oraz link do profilu. Te zdenormalizowane dane lądują od razu w odpowiednich kolumnach na tabeli `users`.

4. **Znalezienie lub stworzenie gracza**
   System weryfikuje czy e-mail zwrócony przez dostawcę już istnieje w naszej bazie:
   - **Istnieje:** System aktualizuje jego `auth_provider` oraz `auth_provider_id`, jeśli te dane były puste, a następnie loguje gracza (łączy konto).
   - **Nie istnieje:** System tworzy nowego rekordu użytkownika w bazie. Podczas tworzenia konta:
     - Adres email pobierany jest od dostawcy.
     - Jako `name` ustawiane jest domyślne imię lub UUID (zostanie zmienione później).
     - Najważniejsze: **Flaga `is_social_setup_pending` zostaje ustawiona na `true`**.

## 🛑 Wymuszenie Dokończenia Konfiguracji

Gracz powraca z pomyślnego logowania Social Login bezpośrednio na stronę główną (homepage). Ponieważ jednak brakuje mu unikalnego w grze nicku (nazwy konta), jest on zatrzymany.

Działa tu specjalny komponent `Livewire\Auth\SocialSetupModal`:
- Załadowany jest on w globalnym kontekście widoku `homepage.blade.php`.
- Wyświetla się warunkowo, gdy `Auth::user()->is_social_setup_pending === true`.
- Dopóki użytkownik nie wpisze i nie zatwierdzi własnej nazwy postaci, warstwa graficzna (modal) skutecznie blokuje klikanie innych przycisków w UI, zatrzymując grę w miejscu. 

**Proces finalizacji formularza:**
- Pole **email** jest zablokowane i widnieje tylko informacyjnie.
- Gracz wpisuje swój `name`. Pole posiada na żywo obsługę weryfikacji unikalności nazwy i jej odpowiednich reguł (np. brak spacji i znaków specjalnych).
- Po walidacji i naciśnięciu Zapisz, nazwa (login) zostaje zapisana w bazie.
- Flaga `is_social_setup_pending` zmieniona jest na `false`.
- Modal zostaje ukryty, wpuszczając gracza do gry.

## 🗄️ Struktura Bazy Danych
Kolumny w tabeli `users` odpowiedzialne za obsługę logowania społecznościowego i danych demograficznych:

- `auth_provider` - varchar (np. "google", "facebook")
- `auth_provider_id` - varchar (unikalne ID używane do powiązania sesji)
- `is_social_setup_pending` - boolean, default false
- `gender` - varchar
- `birthday` - date
- `age_range` - json
- `location` - varchar
- `hometown` - varchar
- `profile_url` - text
