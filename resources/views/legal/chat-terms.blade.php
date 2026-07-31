<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regulamin Czatu i Wyłączenie Odpowiedzialności - Berserk Rush</title>
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
                    <i class="fa-solid fa-comments"></i>
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-amber-400 medieval-font mb-2 tracking-wide">
                    Regulamin Czatu i Wyłączenie Odpowiedzialności
                </h1>
                <p class="text-slate-400 text-sm sm:text-base">
                    Zasady korzystania z czatu w grze Berserk Rush oraz klauzule wyłączenia odpowiedzialności właściciela serwisu
                </p>
            </div>

            <div class="space-y-8 text-slate-300 leading-relaxed text-sm sm:text-base">
                <section class="bg-slate-950/50 p-4 sm:p-5 rounded-lg border border-amber-900/40">
                    <h2 class="text-xl font-bold text-amber-500 medieval-font mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-user-pen text-amber-600"></i> § 1. Odpowiedzialność za Treść (User Generated Content)
                    </h2>
                    <p class="mb-2">
                        1. Wszelkie wiadomości, komentarze i materiały publikowane w czacie w czasie rzeczywistym stanowią wyłącznie **treści generowane przez użytkowników (UGC)**.
                    </p>
                    <p class="mb-2">
                        2. Autor wiadomości ponosi pełną i wyłączną odpowiedzialność cywilną, karną i administracyjną za treść oraz konsekwencje prawne swoich wypowiedzi.
                    </p>
                    <p>
                        3. Właściciel oraz Administracja gry **Berserk Rush** dostarczają jedynie usługę techniczną umożliwiającą przesyłanie wiadomości w czasie rzeczywistym i nie są autorami ani współautorami treści tworzonych przez graczy.
                    </p>
                </section>

                <section class="bg-slate-950/50 p-4 sm:p-5 rounded-lg border border-amber-900/40">
                    <h2 class="text-xl font-bold text-amber-500 medieval-font mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-shield-cat text-amber-600"></i> § 2. Pełne Wyłączenie Odpowiedzialności Właściciela
                    </h2>
                    <p class="mb-2">
                        1. Właściciel i Administracja serwisu w najszerszym zakresie dopuszczalnym przez obowiązujące prawo **nie ponoszą jakiejkolwiek odpowiedzialności** za:
                    </p>
                    <ul class="list-disc list-inside space-y-1 ml-2 mb-3 text-slate-400 text-sm">
                        <li>Treści, poglądy, opinie, znieważenia, pomówienia lub groźby kierowane przez użytkowników na czacie.</li>
                        <li>Transakcje, umowy, ustalenia finansowe lub wymiany wirtualnych/realnych dóbr dokonane pomiędzy graczami.</li>
                        <li>Oszustwa (scam), wyłudzenia oraz szkody majątkowe i niemajątkowe wynikające z kontaktów z innymi graczymi.</li>
                        <li>Linki zewnętrzne oraz materiały udostępniane przez użytkowników na czacie.</li>
                        <li>Ewentualne przerwy w działaniu czatu, opóźnienia w transmisji danych oraz usterki techniczne.</li>
                    </ul>
                    <p class="font-semibold text-amber-300/90">
                        2. Klauzula Zwolnienia z Odpowiedzialności (Hold Harmless): Użytkownik zgadza się zabezpieczyć i zwolnić Właściciela serwisu, jego przedstawicieli oraz moderatorów z wszelkiej odpowiedzialności, roszczeń, strat, odszkodowań oraz kosztów (w tym kosztów pomocy prawnej) wynikających z naruszenia przez Użytkownika praw osób trzecich lub postanowień niniejszego Regulaminu.
                    </p>
                </section>

                <section class="bg-slate-950/50 p-4 sm:p-5 rounded-lg border border-amber-900/40">
                    <h2 class="text-xl font-bold text-amber-500 medieval-font mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-ban text-amber-600"></i> § 3. Zachowania Zakazane na Czacie
                    </h2>
                    <p class="mb-2">Kategorycznie zabrania się publikowania na czacie treści o charakterze:</p>
                    <ul class="list-disc list-inside space-y-1.5 ml-2 text-slate-300">
                        <li>Mowy nienawiści, rasistowskich, ksenofobicznych, homofobicznych oraz nawołujących do przemocy.</li>
                        <li>Wulgaryzmów, zniesławień, nękania (stalkingu/harassmentu) oraz groźb w stosunku do innych graczy.</li>
                        <li>Handlu za realne pieniądze (RMT - Real Money Trading), w tym sprzedaży kont, waluty lub przedmiotów poza grą.</li>
                        <li>Ujawniania danych osobowych osób trzecich (doxxing, naruszenie RODO / GDPR).</li>
                        <li>Phishingu, rozpowszechniania złośliwego oprogramowania oraz linków do witryn niebezpiecznych.</li>
                        <li>Spamu, reklamy zewnętrznej oraz ciągłego powtarzania tych samych wiadomości (flooding).</li>
                        <li>Podszywania się pod Administrację, Moderatorów lub Mistrzów Gry (GM).</li>
                    </ul>
                </section>

                <section class="bg-slate-950/50 p-4 sm:p-5 rounded-lg border border-amber-900/40">
                    <h2 class="text-xl font-bold text-amber-500 medieval-font mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-gavel text-amber-600"></i> § 4. Moderacja, Sankcje i Przekazywanie Logów
                    </h2>
                    <p class="mb-2">
                        1. Administracja oraz Moderatorzy posiadają prawo do bieżącego monitorowania czatu oraz stosowania środków dyscyplinarnych.
                    </p>
                    <p class="mb-2">
                        2. Za naruszenie regulaminu czatu Administracja może nałożyć sankcje w postaci:
                    </p>
                    <ul class="list-disc list-inside space-y-1 ml-4 mb-3 text-slate-400 text-sm">
                        <li>Ostrzeżenia słownego lub usunięcia wiadomości.</li>
                        <li>Tymczasowego lub trwałego wyciszenia konta (Chat Mute).</li>
                        <li>Tymczasowej lub trwałej blokady Konta Użytkownika (Ban).</li>
                    </ul>
                    <p>
                        3. **Współpraca z Organami Ścigania:** W przypadku stwierdzenia możliwości popełnienia przestępstwa (w szczególności gróźb karalnych, pedofilii, oszustw), Administracja zastrzega sobie prawo do zabezpieczenia i przekazania odpowiednim organom ścigania (Policja, Prokuratura) logów czatu wraz z danymi identyfikacyjnymi (np. adres IP, e-mail).
                    </p>
                </section>

                <section class="bg-slate-950/50 p-4 sm:p-5 rounded-lg border border-amber-900/40">
                    <h2 class="text-xl font-bold text-amber-500 medieval-font mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-check-double text-amber-600"></i> § 5. Akceptacja i Postanowienia Końcowe
                    </h2>
                    <p class="mb-2">
                        1. Akceptacja Regulaminu Czatu jest dobrowolna, jednak stanowi niezbędny warunek do korzystania z funkcji wysyłania wiadomości.
                    </p>
                    <p class="mb-2">
                        2. Data i czas akceptacji Regulaminu przez Użytkownika są utwalane w systemie gry.
                    </p>
                    <p>
                        3. Administracja zastrzega sobie prawo do wprowadzania zmian w Regulaminie Czatu. Kontynuowanie korzystania z czatu po wprowadzeniu zmian oznacza ich akceptację.
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
