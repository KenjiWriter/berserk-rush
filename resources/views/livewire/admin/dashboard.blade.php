<div class="min-h-screen bg-gray-900 text-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-bold mb-8 text-amber-500">Berserk Rush - Panel Administracyjny</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @if(auth()->user()->permission_level >= 9)
            <a href="{{ route('admin.news') }}" class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 hover:border-amber-500 transition">
                <h2 class="text-xl font-bold mb-2">📜 Aktualności</h2>
                <p class="text-gray-400">Zarządzanie ogłoszeniami wyświetlanymi na stronie głównej.</p>
            </a>
            @endif

            <a href="{{ route('admin.maps') }}" class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 hover:border-amber-500 transition">
                <h2 class="text-xl font-bold mb-2">🗺️ Mapy</h2>
                <p class="text-gray-400">Zarządzanie mapami, poziomami dostępu i grafikami.</p>
            </a>

            <a href="{{ route('admin.monsters') }}" class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 hover:border-amber-500 transition">
                <h2 class="text-xl font-bold mb-2">🧟 Potwory</h2>
                <p class="text-gray-400">Tworzenie przeciwników, ustalanie statystyk i przypisywanie do map.</p>
            </a>

            <a href="{{ route('admin.item-templates') }}" class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 hover:border-amber-500 transition">
                <h2 class="text-xl font-bold mb-2">⚔️ Przedmioty (Szablony)</h2>
                <p class="text-gray-400">Baza przedmiotów, sloty, statystyki bazowe i poziomy.</p>
            </a>

            <a href="{{ route('admin.merchant-items') }}" class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 hover:border-amber-500 transition">
                <h2 class="text-xl font-bold mb-2">🏬 Sklepy / Kupcy</h2>
                <p class="text-gray-400">Zarządzanie ofertą handlarzy, limitowane sztuki i levele.</p>
            </a>

            <a href="{{ route('admin.item-recipes') }}" class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 hover:border-amber-500 transition">
                <h2 class="text-xl font-bold mb-2">🛠️ Przepisy (Rzemiosło)</h2>
                <p class="text-gray-400">Kombinacje materiałów do tworzenia nowych przedmiotów.</p>
            </a>

            <a href="{{ route('admin.upgrade-rules') }}" class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 hover:border-amber-500 transition">
                <h2 class="text-xl font-bold mb-2">⚒️ Ulepszenia</h2>
                <p class="text-gray-400">Zasady ulepszeń w kuźni, koszty i szanse na sukces.</p>
            </a>

            <a href="{{ route('admin.loot-tables') }}" class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 hover:border-amber-500 transition">
                <h2 class="text-xl font-bold mb-2">🎁 Tabele Łupów</h2>
                <p class="text-gray-400">Szablony dropów z potworów, wagi i prawdopodobieństwa.</p>
            </a>

            <a href="{{ route('admin.pet-templates') }}" class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 hover:border-amber-500 transition">
                <h2 class="text-xl font-bold mb-2">🐉 Pety (Szablony)</h2>
                <p class="text-gray-400">Zarządzanie gatunkami chowańców, z których wykluwają się jaja.</p>
            </a>

            <a href="{{ route('admin.quests') }}" class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 hover:border-amber-500 transition">
                <h2 class="text-xl font-bold mb-2">📜 Questy</h2>
                <p class="text-gray-400">Zarządzanie zadaniami, celami i nagrodami.</p>
            </a>
            
            <a href="{{ route('admin.titles') }}" class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 hover:border-amber-500 transition">
                <h2 class="text-xl font-bold mb-2">👑 Tytuły</h2>
                <p class="text-gray-400">Zarządzanie tytułami postaci i ich bonusami.</p>
            </a>

            <a href="{{ route('admin.achievements') }}" class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 hover:border-amber-500 transition">
                <h2 class="text-xl font-bold mb-2">🏆 Osiągnięcia</h2>
                <p class="text-gray-400">Zarządzanie osiągnięciami, punktami i nagrodami.</p>
            </a>

            @if(auth()->user()->permission_level >= 9)
            <a href="{{ route('admin.gallery') }}" class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 hover:border-amber-500 transition">
                <h2 class="text-xl font-bold mb-2">🖼️ Galeria</h2>
                <p class="text-gray-400">Zarządzanie zdjęciami na stronie głównej gry.</p>
            </a>

            <a href="{{ route('admin.item-shop-packages') }}" class="bg-gradient-to-br from-yellow-900/40 to-amber-900/40 p-6 rounded-lg shadow-[0_0_15px_rgba(251,191,36,0.1)] border border-yellow-600/50 hover:border-yellow-400 hover:shadow-[0_0_20px_rgba(251,191,36,0.3)] transition-all">
                <h2 class="text-xl font-bold mb-2 text-yellow-500">💎 Pakiety Item Shop</h2>
                <p class="text-gray-400">Zarządzanie pakietami premium, cenami i ilością gemów w ofercie.</p>
            </a>
            @endif

            <a href="{{ route('admin.dungeons') }}" class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 hover:border-amber-500 transition">
                <h2 class="text-xl font-bold mb-2">🏰 Lochy</h2>
                <p class="text-gray-400">Zarządzanie instancjami, falami potworów i nagrodami za lochy.</p>
            </a>

            <a href="{{ route('admin.combat-skills') }}" class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 hover:border-amber-500 transition">
                <h2 class="text-xl font-bold mb-2">⚔️ Umiejętności Walki</h2>
                <p class="text-gray-400">Zarządzanie umiejętnościami, ich siłą i wymaganą bronią.</p>
            </a>

            @if(auth()->user()->permission_level >= 9)
            <a href="{{ route('admin.characters') }}" class="bg-gradient-to-br from-amber-900/50 to-red-900/50 p-6 rounded-lg shadow-[0_0_15px_rgba(245,158,11,0.15)] border border-amber-600/60 hover:border-amber-400 hover:shadow-[0_0_20px_rgba(245,158,11,0.35)] transition-all">
                <h2 class="text-xl font-bold mb-2 text-amber-400">🧙‍♂️ Postacie / Gracze</h2>
                <p class="text-gray-300">Podgląd lokalizacji, statusu online/offline, VIP, gemów i wyciszeń (mute).</p>
            </a>

            @php
                $newSuggestionsCount = \App\Infrastructure\Persistence\Suggestion::where('status', 'new')->count();
                $activeEvent = app(\App\Application\Events\WeekendEventService::class)->getActiveEvent();
            @endphp
            <a href="{{ route('admin.suggestions') }}" class="bg-gradient-to-br from-indigo-950/60 to-purple-950/60 p-6 rounded-lg shadow-[0_0_15px_rgba(99,102,241,0.15)] border border-indigo-600/60 hover:border-indigo-400 hover:shadow-[0_0_20px_rgba(99,102,241,0.35)] transition-all relative">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-xl font-bold text-indigo-300">💡 Sugestie i Uwagi</h2>
                    @if($newSuggestionsCount > 0)
                        <span class="px-2.5 py-0.5 bg-amber-500 text-stone-950 font-extrabold text-xs rounded-full animate-pulse shadow">
                            {{ $newSuggestionsCount }} nowych
                        </span>
                    @endif
                </div>
                <p class="text-gray-300">Lista uwag, błędów i propozycji zmian zgłoszonych przez graczy.</p>
            </a>

            <a href="{{ route('admin.events') }}" class="bg-gradient-to-br from-yellow-950/60 via-amber-950/50 to-stone-900/90 p-6 rounded-lg shadow-[0_0_15px_rgba(245,158,11,0.2)] border border-amber-600/60 hover:border-amber-400 hover:shadow-[0_0_25px_rgba(245,158,11,0.4)] transition-all relative group">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-xl font-bold text-amber-400 flex items-center gap-2">
                        <i class="fa-solid fa-calendar-star"></i> Eventy Weekendowe
                    </h2>
                    @if($activeEvent)
                        <span class="px-2.5 py-0.5 bg-emerald-500/20 text-emerald-300 font-extrabold text-xs rounded-full border border-emerald-500/40 animate-pulse">
                            AKTYWNY
                        </span>
                    @endif
                </div>
                <p class="text-gray-300">Zarządzanie eventami (2x Gold, 2x EXP, 2x Drop, 2x Gems, Ulepszenia). Auto-losowanie co weekend.</p>
            </a>
            @endif
        </div>

        <div class="mt-8">
            <a href="{{ route('homepage') }}" class="text-gray-400 hover:text-white underline">
                &larr; Wróć do gry
            </a>
        </div>
    </div>
</div>
