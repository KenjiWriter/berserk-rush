<div class="min-h-screen bg-gray-900 text-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-bold mb-8 text-amber-500">Berserk Rush - Panel Administracyjny</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
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

            <a href="{{ route('admin.gallery') }}" class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 hover:border-amber-500 transition">
                <h2 class="text-xl font-bold mb-2">🖼️ Galeria</h2>
                <p class="text-gray-400">Zarządzanie zdjęciami na stronie głównej gry.</p>
            </a>

            <a href="{{ route('admin.item-shop-packages') }}" class="bg-gradient-to-br from-yellow-900/40 to-amber-900/40 p-6 rounded-lg shadow-[0_0_15px_rgba(251,191,36,0.1)] border border-yellow-600/50 hover:border-yellow-400 hover:shadow-[0_0_20px_rgba(251,191,36,0.3)] transition-all">
                <h2 class="text-xl font-bold mb-2 text-yellow-500">💎 Pakiety Item Shop</h2>
                <p class="text-gray-400">Zarządzanie pakietami premium, cenami i ilością gemów w ofercie.</p>
            </a>

            <a href="{{ route('admin.dungeons') }}" class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 hover:border-amber-500 transition">
                <h2 class="text-xl font-bold mb-2">🏰 Lochy</h2>
                <p class="text-gray-400">Zarządzanie instancjami, falami potworów i nagrodami za lochy.</p>
            </a>
        </div>

        <div class="mt-8">
            <a href="{{ route('homepage') }}" class="text-gray-400 hover:text-white underline">
                &larr; Wróć do gry
            </a>
        </div>
    </div>
</div>
