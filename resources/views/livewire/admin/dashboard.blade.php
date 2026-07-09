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

            <a href="{{ route('admin.loot-tables') }}" class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 hover:border-amber-500 transition">
                <h2 class="text-xl font-bold mb-2">🎁 Tabele Łupów</h2>
                <p class="text-gray-400">Szablony dropów z potworów, wagi i prawdopodobieństwa.</p>
            </a>
        </div>

        <div class="mt-8">
            <a href="{{ route('homepage') }}" class="text-gray-400 hover:text-white underline">
                &larr; Wróć do gry
            </a>
        </div>
    </div>
</div>
