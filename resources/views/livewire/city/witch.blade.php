<div
    class="min-h-screen bg-gradient-to-b from-purple-900/90 via-indigo-800/90 to-purple-900/90 text-amber-100 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-b from-slate-900/40 via-slate-800/50 to-slate-900/60"></div>

    <div class="relative container mx-auto px-4 py-8 min-h-screen">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div
                class="bg-gradient-to-r from-purple-50/95 to-purple-100/95 border-4 border-purple-700 rounded-lg p-4 shadow-2xl backdrop-blur-sm">
                <h2 class="text-xl font-bold text-purple-900 medieval-font">{{ $character->name }} u Wiedźmy</h2>
            </div>

            <button wire:click="backToHub"
                class="bg-gradient-to-r from-slate-600 to-slate-700 hover:from-slate-700 hover:to-slate-800 text-amber-200 font-bold py-2 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg medieval-font">
                🏰 Powrót do miasta
            </button>
        </div>

        {{-- Witch content --}}
        <div class="text-center">
            <div
                class="bg-gradient-to-br from-purple-50/95 to-purple-100/95 border-4 border-purple-700 rounded-lg p-12 shadow-2xl backdrop-blur-sm max-w-2xl mx-auto">
                <div class="text-8xl mb-6">🧙‍♀️</div>
                <h1 class="text-4xl font-bold text-purple-900 medieval-font mb-4">Witaj u Wiedźmy!</h1>
                <p class="text-xl text-purple-800 font-semibold mb-6">
                    "Hehe... {{ $character->name }}, czy szukasz magicznych mikstur? Moje eliksiry mogą cię znacznie
                    wzmocnić..."
                </p>
                <div class="text-purple-700">
                    <p class="font-semibold">🧪 Funkcje w przygotowaniu:</p>
                    <ul class="mt-4 space-y-2">
                        <li>• Warenie mikstur</li>
                        <li>• Sklep alchemiczny</li>
                        <li>• Warzenie eliksirów</li>
                        <li>• Crafting zaklęć</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
