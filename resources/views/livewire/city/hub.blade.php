<div
    class="min-h-screen bg-gradient-to-b from-blue-900/90 via-indigo-800/90 to-purple-900/90 text-amber-100 relative overflow-hidden">
    {{-- Background city image --}}
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-60"
        style="background-image: url('{{ asset('img/city-background.png') }}');">
    </div>

    {{-- Dark overlay for readability --}}
    <div class="absolute inset-0 bg-gradient-to-b from-slate-900/40 via-slate-800/50 to-slate-900/60"></div>

    {{-- Floating motes of light --}}
    <div class="absolute inset-0 pointer-events-none">
        <div class="magic-mote magic-mote-1"></div>
        <div class="magic-mote magic-mote-2"></div>
        <div class="magic-mote magic-mote-3"></div>
        <div class="magic-mote magic-mote-4"></div>
        <div class="magic-mote magic-mote-5"></div>
    </div>

    <div class="relative container mx-auto px-4 py-8 min-h-screen">
        {{-- Header with character info --}}
        <div class="flex items-center justify-between mb-8">
            <div
                class="bg-gradient-to-r from-amber-50/95 to-amber-100/95 border-4 border-amber-700 rounded-lg p-4 shadow-2xl backdrop-blur-sm">
                <div class="flex items-center space-x-3">
                    {{-- Character avatar --}}
                    <div
                        class="w-12 h-12 border-2 border-amber-700 rounded-full overflow-hidden bg-gradient-to-b from-amber-200 to-amber-300">
                        @if ($character->avatar && file_exists(public_path('img/avatars/' . $character->avatar . '.png')))
                            <img src="{{ asset('img/avatars/' . $character->avatar . '.png') }}"
                                alt="Avatar {{ $character->avatar }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-lg text-amber-700">
                                ⚔️
                            </div>
                        @endif
                    </div>

                    {{-- Character info --}}
                    <div>
                        <h2 class="text-xl font-bold text-amber-900 medieval-font">{{ $character->name }}</h2>
                        <div class="text-sm text-amber-700">
                            Poziom {{ $character->level }} • {{ $character->xp }} XP •
                            {{ number_format($character->gold) }} złota
                        </div>
                    </div>
                </div>
            </div>

            {{-- Back button --}}
            <button wire:click="backToHomepage"
                class="bg-gradient-to-r from-slate-600 to-slate-700 hover:from-slate-700 hover:to-slate-800 text-amber-200 font-bold py-2 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg medieval-font">
                🏠 Powrót do gry
            </button>
        </div>

        {{-- City title --}}
        <div class="text-center mb-12">
            <h1
                class="text-5xl font-bold bg-gradient-to-r from-amber-300 via-yellow-400 to-amber-500 bg-clip-text text-transparent medieval-font drop-shadow-2xl mb-2">
                🏰 Miasto Berserków
            </h1>
            <p class="text-xl text-amber-200 font-semibold drop-shadow-lg">
                Wybierz gdzie chcesz się udać
            </p>
        </div>

        {{-- City layout --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
            {{-- Left side buildings --}}
            <div class="space-y-6">
                {{-- Armorsmith --}}
                <div class="relative group">
                    <button wire:click="goTo('armorsmith')"
                        class="w-full bg-gradient-to-br from-amber-50/90 to-amber-100/90 border-4 border-amber-700 rounded-lg p-6 shadow-2xl backdrop-blur-sm hover:from-amber-100/95 hover:to-amber-200/95 transition-all duration-300 transform hover:scale-105 hover:shadow-3xl">
                        {{-- Decorative corners --}}
                        <div
                            class="absolute top-0 left-0 w-6 h-6 bg-amber-800 transform rotate-45 -translate-x-3 -translate-y-3">
                        </div>
                        <div
                            class="absolute top-0 right-0 w-6 h-6 bg-amber-800 transform rotate-45 translate-x-3 -translate-y-3">
                        </div>
                        <div
                            class="absolute bottom-0 left-0 w-6 h-6 bg-amber-800 transform rotate-45 -translate-x-3 translate-y-3">
                        </div>
                        <div
                            class="absolute bottom-0 right-0 w-6 h-6 bg-amber-800 transform rotate-45 translate-x-3 translate-y-3">
                        </div>

                        <div class="relative text-center">
                            <div class="text-6xl mb-4">🛡️</div>
                            <h3 class="text-2xl font-bold text-amber-900 medieval-font mb-2">Zbrojmistrz</h3>
                            <p class="text-amber-800 font-semibold">Zbroje i tarcze dla wojowników</p>
                        </div>
                    </button>
                </div>

                {{-- Weaponsmith --}}
                <div class="relative group">
                    <button wire:click="goTo('weaponsmith')"
                        class="w-full bg-gradient-to-br from-amber-50/90 to-amber-100/90 border-4 border-amber-700 rounded-lg p-6 shadow-2xl backdrop-blur-sm hover:from-amber-100/95 hover:to-amber-200/95 transition-all duration-300 transform hover:scale-105 hover:shadow-3xl">
                        {{-- Decorative corners --}}
                        <div
                            class="absolute top-0 left-0 w-6 h-6 bg-amber-800 transform rotate-45 -translate-x-3 -translate-y-3">
                        </div>
                        <div
                            class="absolute top-0 right-0 w-6 h-6 bg-amber-800 transform rotate-45 translate-x-3 -translate-y-3">
                        </div>
                        <div
                            class="absolute bottom-0 left-0 w-6 h-6 bg-amber-800 transform rotate-45 -translate-x-3 translate-y-3">
                        </div>
                        <div
                            class="absolute bottom-0 right-0 w-6 h-6 bg-amber-800 transform rotate-45 translate-x-3 translate-y-3">
                        </div>

                        <div class="relative text-center">
                            <div class="text-6xl mb-4">⚔️</div>
                            <h3 class="text-2xl font-bold text-amber-900 medieval-font mb-2">Brońmistrz</h3>
                            <p class="text-amber-800 font-semibold">Miecze, łuki i magiczne berła</p>
                        </div>
                    </button>
                </div>
            </div>

            {{-- Center - Main road --}}
            <div class="flex items-center justify-center">
                <div class="text-center">
                    <div
                        class="w-32 h-32 mx-auto bg-gradient-to-b from-stone-400 to-stone-600 rounded-full border-4 border-stone-700 shadow-2xl flex items-center justify-center mb-4">
                        <div class="text-4xl">🏰</div>
                    </div>
                    <h3 class="text-xl font-bold text-amber-300 medieval-font">Plac Centralny</h3>
                    <p class="text-amber-200 text-sm font-semibold">Serce miasta</p>
                </div>
            </div>

            {{-- Right side buildings --}}
            <div class="space-y-6">
                {{-- Witch --}}
                <div class="relative group">
                    <button wire:click="goTo('witch')"
                        class="w-full bg-gradient-to-br from-purple-50/90 to-purple-100/90 border-4 border-purple-700 rounded-lg p-6 shadow-2xl backdrop-blur-sm hover:from-purple-100/95 hover:to-purple-200/95 transition-all duration-300 transform hover:scale-105 hover:shadow-3xl">
                        {{-- Decorative corners --}}
                        <div
                            class="absolute top-0 left-0 w-6 h-6 bg-purple-800 transform rotate-45 -translate-x-3 -translate-y-3">
                        </div>
                        <div
                            class="absolute top-0 right-0 w-6 h-6 bg-purple-800 transform rotate-45 translate-x-3 -translate-y-3">
                        </div>
                        <div
                            class="absolute bottom-0 left-0 w-6 h-6 bg-purple-800 transform rotate-45 -translate-x-3 translate-y-3">
                        </div>
                        <div
                            class="absolute bottom-0 right-0 w-6 h-6 bg-purple-800 transform rotate-45 translate-x-3 translate-y-3">
                        </div>

                        <div class="relative text-center">
                            <div class="text-6xl mb-4">🧙‍♀️</div>
                            <h3 class="text-2xl font-bold text-purple-900 medieval-font mb-2">Wiedźma</h3>
                            <p class="text-purple-800 font-semibold">Alchemia i magiczne mikstury</p>
                        </div>
                    </button>
                </div>

                {{-- Adventure --}}
                <div class="relative group">
                    <button wire:click="goTo('adventure')"
                        class="w-full bg-gradient-to-br from-green-50/90 to-green-100/90 border-4 border-green-700 rounded-lg p-6 shadow-2xl backdrop-blur-sm hover:from-green-100/95 hover:to-green-200/95 transition-all duration-300 transform hover:scale-105 hover:shadow-3xl">
                        {{-- Decorative corners --}}
                        <div
                            class="absolute top-0 left-0 w-6 h-6 bg-green-800 transform rotate-45 -translate-x-3 -translate-y-3">
                        </div>
                        <div
                            class="absolute top-0 right-0 w-6 h-6 bg-green-800 transform rotate-45 translate-x-3 -translate-y-3">
                        </div>
                        <div
                            class="absolute bottom-0 left-0 w-6 h-6 bg-green-800 transform rotate-45 -translate-x-3 translate-y-3">
                        </div>
                        <div
                            class="absolute bottom-0 right-0 w-6 h-6 bg-green-800 transform rotate-45 translate-x-3 translate-y-3">
                        </div>

                        <div class="relative text-center">
                            <div class="text-6xl mb-4">🗺️</div>
                            <h3 class="text-2xl font-bold text-green-900 medieval-font mb-2">Przygoda</h3>
                            <p class="text-green-800 font-semibold">Wyrusz na niebezpieczne ekspedycje</p>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        {{-- Mobile layout --}}
        <div class="lg:hidden mt-8">
            <div class="grid grid-cols-2 gap-4">
                <button wire:click="goTo('armorsmith')"
                    class="bg-gradient-to-br from-amber-50/90 to-amber-100/90 border-4 border-amber-700 rounded-lg p-4 text-center shadow-xl">
                    <div class="text-4xl mb-2">🛡️</div>
                    <div class="font-bold text-amber-900 medieval-font">Zbrojmistrz</div>
                </button>

                <button wire:click="goTo('weaponsmith')"
                    class="bg-gradient-to-br from-amber-50/90 to-amber-100/90 border-4 border-amber-700 rounded-lg p-4 text-center shadow-xl">
                    <div class="text-4xl mb-2">⚔️</div>
                    <div class="font-bold text-amber-900 medieval-font">Brońmistrz</div>
                </button>

                <button wire:click="goTo('witch')"
                    class="bg-gradient-to-br from-purple-50/90 to-purple-100/90 border-4 border-purple-700 rounded-lg p-4 text-center shadow-xl">
                    <div class="text-4xl mb-2">🧙‍♀️</div>
                    <div class="font-bold text-purple-900 medieval-font">Wiedźma</div>
                </button>

                <button wire:click="goTo('adventure')"
                    class="bg-gradient-to-br from-green-50/90 to-green-100/90 border-4 border-green-700 rounded-lg p-4 text-center shadow-xl">
                    <div class="text-4xl mb-2">🗺️</div>
                    <div class="font-bold text-green-900 medieval-font">Przygoda</div>
                </button>
            </div>
        </div>
    </div>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&display=swap');

        .medieval-font {
            font-family: 'Cinzel', serif;
        }

        /* Magic motes */
        .magic-mote {
            position: absolute;
            background: radial-gradient(circle, rgba(147, 197, 253, 0.8) 0%, rgba(59, 130, 246, 0.4) 50%, transparent 100%);
            border-radius: 50%;
            pointer-events: none;
            animation: float-magic 20s infinite linear;
        }

        .magic-mote-1 {
            width: 6px;
            height: 6px;
            left: 15%;
            animation-delay: 0s;
            animation-duration: 25s;
        }

        .magic-mote-2 {
            width: 4px;
            height: 4px;
            left: 35%;
            animation-delay: 5s;
            animation-duration: 20s;
        }

        .magic-mote-3 {
            width: 8px;
            height: 8px;
            left: 55%;
            animation-delay: 10s;
            animation-duration: 30s;
        }

        .magic-mote-4 {
            width: 5px;
            height: 5px;
            left: 75%;
            animation-delay: 15s;
            animation-duration: 22s;
        }

        .magic-mote-5 {
            width: 7px;
            height: 7px;
            left: 85%;
            animation-delay: 20s;
            animation-duration: 28s;
        }

        @keyframes float-magic {
            0% {
                transform: translateY(100vh) translateX(0px) rotate(0deg);
                opacity: 0;
            }

            10% {
                opacity: 1;
            }

            90% {
                opacity: 1;
            }

            100% {
                transform: translateY(-100px) translateX(50px) rotate(360deg);
                opacity: 0;
            }
        }
    </style>
</div>
