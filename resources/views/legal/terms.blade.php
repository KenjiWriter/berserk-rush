<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regulamin - Berserk Rush</title>
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
                    <i class="fa-solid fa-scroll"></i>
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-amber-400 medieval-font mb-2 tracking-wide">
                    Regulamin Gry Berserk Rush
                </h1>
                <p class="text-slate-400 text-sm sm:text-base">
                    Zasady korzystania z serwisu, gry oraz prawa i obowiązki gracza
                </p>
            </div>

            <div class="space-y-8 text-slate-300 leading-relaxed text-sm sm:text-base">
                <section>
                    <h2 class="text-xl font-bold text-amber-500 medieval-font mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-amber-600"></i> § 1. Postanowienia Ogólne
                    </h2>
                    <p class="mb-2">
                        1. Niniejszy Regulamin określa zasady korzystania z gry internetowej **Berserk Rush** dostępnej w serwisie internetowym.
                    </p>
                    <p class="mb-2">
                        2. Właścicielem i administratorem gry jest zespół Berserk Rush.
                    </p>
                    <p>
                        3. Każdy użytkownik przed rozpoczęciem korzystania z Gry zobowiązany jest do zapoznania się z Regulaminem oraz jego akceptacji.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-amber-500 medieval-font mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-user-check text-amber-600"></i> § 2. Rejestracja i Konto Użytkownika
                    </h2>
                    <p class="mb-2">
                        1. Warunkiem rozpoczęcia gry jest utworzenie Konta Użytkownika poprzez podanie unikalnego miana, adresu e-mail oraz bezpiecznego hasła.
                    </p>
                    <p class="mb-2">
                        2. Użytkownik jest zobowiązany do zachowania w tajemnicy danych dostępowych do swojego Konta.
                    </p>
                    <p>
                        3. Zabrania się przekazywania, sprzedaży oraz odstępowania Konta osobom trzecim.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-amber-500 medieval-font mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-gavel text-amber-600"></i> § 3. Zasady Fair Play i Zakazy
                    </h2>
                    <p class="mb-2">
                        1. Wszyscy gracze zobowiązani są do przestrzegania zasad uczciwej rywalizacji (Fair Play).
                    </p>
                    <p class="mb-2">
                        2. Kategorycznie zabrania się używania zewnętrznego oprogramowania wspomagającego (boty, skrypty auto-clicker, exploity).
                    </p>
                    <p class="mb-2">
                        3. Wykorzystywanie barier lub błędów technicznych w grze w celu uzyskania nienależnej przewagi jest zabronione i winno być niezwłocznie zgłaszane administracji.
                    </p>
                    <p>
                        4. Używanie mowy nienawiści, wulgaryzmów oraz obrażanie innych graczy na czacie, wiadomościach prywatnych lub nazwach postaci skutkuje blokadą lub usunięciem konta.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-amber-500 medieval-font mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-gem text-amber-600"></i> § 4. Płatności i Wirtualne Przedmioty
                    </h2>
                    <p class="mb-2">
                        1. Gra oferuje możliwość zakupu opcjonalnej waluty wirtualnej (Diamenty) oraz konta Premium za pomocą dostępnych operatorów płatności.
                    </p>
                    <p class="mb-2">
                        2. Waluty wirtualne oraz przedmioty cyfrowe nie posiadają wartości w świecie rzeczywistym i nie podlegają wymianie na pieniądze tradycyjne.
                    </p>
                    <p>
                        3. Usunięcie konta przez Użytkownika powoduje wygaśnięcie wszystkich wirtualnych zasobów bez prawa do zwrotu poniesionych kosztów.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-amber-500 medieval-font mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-user-minus text-amber-600"></i> § 5. Usunięcie Konta i Sancje
                    </h2>
                    <p class="mb-2">
                        1. Użytkownik ma prawo w dowolnym momencie usunąć swoje Konto korzystając z panelu zarządzania profilem.
                    </p>
                    <p>
                        2. Administracja zastrzega sobie prawo do zablokowania lub usunięcia Konta Użytkownika naruszającego postanowienia niniejszego Regulaminu.
                    </p>
                </section>
            </div>

            <div class="mt-10 pt-6 border-t border-amber-700/40 text-center">
                <a href="{{ route('homepage') }}" class="inline-block bg-gradient-to-r from-amber-700 to-amber-800 hover:from-amber-600 hover:to-amber-700 text-amber-100 font-bold py-3 px-8 rounded-lg shadow-lg medieval-font border border-amber-500 transition-transform active:scale-95">
                    Rozumiem i Akceptuję
                </a>
            </div>
        </div>
    </div>
</body>
</html>
