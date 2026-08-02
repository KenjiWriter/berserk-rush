<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Polityka Prywatności - Berserk Rush</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        .medieval-font { font-family: 'Cinzel', serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-200 min-h-screen font-sans selection:bg-amber-500 selection:text-slate-950">
    {{-- Background pattern --}}
    <div class="fixed inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-amber-900/20 via-slate-950 to-slate-950 pointer-events-none"></div>

    <div class="relative max-w-4xl mx-auto px-4 py-12">
        {{-- Header Navigation --}}
        <div class="mb-8 flex justify-between items-center">
            <a href="{{ route('homepage') }}" class="inline-flex items-center gap-2 text-amber-500 hover:text-amber-400 font-bold transition-colors medieval-font text-lg">
                <i class="fa-solid fa-arrow-left"></i> Powrót do Gry
            </a>
            <span class="text-xs uppercase tracking-widest text-slate-500 font-semibold">Ostatnia aktualizacja: {{ date('Y-m-d') }}</span>
        </div>

        {{-- Document Card --}}
        <div class="bg-slate-900/90 border-2 border-amber-700/60 rounded-xl p-6 sm:p-10 shadow-2xl backdrop-blur-md relative overflow-hidden">
            <div class="absolute top-0 left-0 w-8 h-8 border-t-2 border-l-2 border-amber-500"></div>
            <div class="absolute top-0 right-0 w-8 h-8 border-t-2 border-r-2 border-amber-500"></div>
            <div class="absolute bottom-0 left-0 w-8 h-8 border-b-2 border-l-2 border-amber-500"></div>
            <div class="absolute bottom-0 right-0 w-8 h-8 border-b-2 border-r-2 border-amber-500"></div>

            <div class="text-center mb-10 border-b border-amber-700/40 pb-6">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-tr from-amber-700 to-amber-500 border-2 border-amber-400 flex items-center justify-center text-slate-950 text-2xl shadow-lg">
                    <i class="fa-solid fa-user-lock"></i>
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-amber-400 medieval-font mb-2 tracking-wide">
                    Polityka Prywatności Berserk Rush
                </h1>
                <p class="text-slate-400 text-sm sm:text-base">
                    Informacje o przetwarzaniu i ochronie danych osobowych
                </p>
            </div>

            <div class="space-y-8 text-slate-300 leading-relaxed text-sm sm:text-base">
                <section>
                    <h2 class="text-xl font-bold text-amber-500 medieval-font mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-user-shield text-amber-600"></i> § 1. Administrator Danych
                    </h2>
                    <p class="mb-2">
                        1. Administratorem Twoich danych osobowych przetwarzanych w ramach serwisu i gry **Berserk Rush** jest zespół Berserk Rush.
                    </p>
                    <p>
                        2. W sprawach związanych z ochroną danych osobowych możesz skontaktować się z administratorem poprzez formularz w grze lub adres e-mail wsparcia.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-amber-500 medieval-font mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-database text-amber-600"></i> § 2. Zakres Gromadzonych Danych
                    </h2>
                    <p class="mb-2">
                        1. W celu świadczenia usług gry zbieramy następujące dane:
                    </p>
                    <ul class="list-disc list-inside space-y-1 ml-4 mb-2">
                        <li>Nazwa użytkownika (miano wojownika)</li>
                        <li>Adres e-mail</li>
                        <li>Zaszyfrowane hasło dostępowe</li>
                        <li>Postęp w grze, historia rozgrywek i postacie</li>
                        <li>Adres IP oraz podstawowe logi systemowe (w celach bezpieczeństwa)</li>
                        <li>Historia transakcji mikropłatności (identyfikatory transakcji Stripe, zakupione pakiety Gemów, kwoty i statusy płatności)</li>
                    </ul>
                    <p>
                        2. W przypadku logowania przez dostawców zewnętrznych (Google, Facebook) zbieramy wyłącznie podstawowy identyfikator profilu i publiczny adres e-mail.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-amber-500 medieval-font mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-bullseye text-amber-600"></i> § 3. Cel i Podstawa Przetwarzania
                    </h2>
                    <p class="mb-2">
                        1. Dane przetwarzane są w celu realizowania usługi świadczonej drogą elektroniczną (umożliwienie rozgrywki, zapisu stanu gry, logowania, realizacja zakupów waluty wirtualnej).
                    </p>
                    <p class="mb-2">
                        2. Podstawą prawną przetwarzania jest niezbędność wykonania umowy (akceptacja Regulaminu), wypełnienie obowiązków prawnych ciążących na Administratorze (przepisy podatkowo-księgowe) oraz uzasadniony interes Administratora (ochrona przed oszustwami, obsługa reklamacji i dochodzenie roszczeń).
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-amber-500 medieval-font mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-[#000] fa-cookie-bite text-amber-600"></i> § 4. Pliki Cookie i Logi Systemowe
                    </h2>
                    <p class="mb-2">
                        1. Serwis wykorzystuje niezbędne pliki sesyjne (Cookies) do utrzymania sesji zalogowanego użytkownika oraz zapewnienia ochrony CSRF.
                    </p>
                    <p>
                        2. Pliki te są kluczowe do prawidłowego funkcjonowania Gry.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-amber-500 medieval-font mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-scale-balanced text-amber-600"></i> § 5. Prawa Użytkownika (RODO)
                    </h2>
                    <p class="mb-2">
                        1. Posiadasz prawo dostępu do swoich danych, ich sprostowania, ograniczenia przetwarzania oraz wniesienia sprzeciwu.
                    </p>
                    <p class="mb-2">
                        2. Posiadasz prawo do **całkowitego usunięcia swoich danych** ("prawo do bycia zapomnianym"). Usunięcie konta w grze automatycznie usuwa wszystkie powiązane z nim dane osobowe oraz postacie.
                    </p>
                    <p>
                        3. Usunięcie konta możesz zrealizować samodzielnie w zakładce "Zarządzaj profilem" na stronie głównej.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-amber-500 medieval-font mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-credit-card text-amber-600"></i> § 6. Przetwarzanie Danych przy Płatnościach (Stripe)
                    </h2>
                    <p class="mb-2">
                        1. Płatności w Sklepie Premium realizowane są za pośrednictwem licencjonowanego operatora płatności **Stripe Payments Europe, Ltd.** (lub Stripe Inc.).
                    </p>
                    <p class="mb-2">
                        2. Wszelkie wrażliwe dane płatnicze (numery kart kredytowych/debetowych, kody CVC/CVV, dane autoryzacyjne) są wprowadzane bezpośrednio w zaszyfrowanych formularzach operatora Stripe. Serwis **Berserk Rush nie przetwarza ani nie gromadzi pełnych danych kart płatniczych**.
                    </p>
                    <p>
                        3. Dane transakcyjne (identyfikatory płatności, kwoty, waluta) przechowywane są przez okres wymagany przepisami prawa podatkowego i rachunkowego oraz przez okres przedawnienia ewentualnych roszczeń reklamacyjnych.
                    </p>
                </section>
            </div>

            <div class="mt-10 pt-6 border-t border-amber-700/40 text-center">
                <a href="{{ route('homepage') }}" class="inline-block bg-gradient-to-r from-amber-700 to-amber-800 hover:from-amber-600 hover:to-amber-700 text-amber-100 font-bold py-3 px-8 rounded-lg shadow-lg medieval-font border border-amber-500 transition-transform active:scale-95">
                    Zapoznałem się z Polityką Prywatności
                </a>
            </div>
        </div>
    </div>
</body>
</html>
