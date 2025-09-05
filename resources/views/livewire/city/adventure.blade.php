<div
    class="min-h-screen bg-gradient-to-b from-green-900/90 via-emerald-800/90 to-green-900/90 text-amber-100 relative overflow-hidden">
    {{-- Background adventure image --}}
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-60"
        style="background-image: url('{{ asset('img/adventure-background.jpg') }}');">
    </div>

    {{-- Dark overlay for readability --}}
    <div class="absolute inset-0 bg-gradient-to-b from-slate-900/40 via-slate-800/50 to-slate-900/60"></div>

    {{-- Floating adventure elements --}}
    <div class="absolute inset-0 pointer-events-none">
        <div class="adventure-element adventure-element-1">⚔️</div>
        <div class="adventure-element adventure-element-2">🗡️</div>
        <div class="adventure-element adventure-element-3">🛡️</div>
        <div class="adventure-element adventure-element-4">💎</div>
        <div class="adventure-element adventure-element-5">🏹</div>
    </div>

    <div class="relative container mx-auto px-4 py-8 min-h-screen">
        {{-- Header with character info --}}
        <div class="flex items-center justify-between mb-8">
            <div
                class="bg-gradient-to-r from-green-50/95 to-green-100/95 border-4 border-green-700 rounded-lg p-4 shadow-2xl backdrop-blur-sm">
                <div class="flex items-center space-x-3">
                    {{-- Character avatar --}}
                    <div
                        class="w-12 h-12 border-2 border-green-700 rounded-full overflow-hidden bg-gradient-to-b from-green-200 to-green-300">
                        @if ($character->avatar && file_exists(public_path('img/avatars/' . $character->avatar . '.png')))
                            <img src="{{ asset('img/avatars/' . $character->avatar . '.png') }}"
                                alt="Avatar {{ $character->avatar }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-lg text-green-700">
                                ⚔️
                            </div>
                        @endif
                    </div>

                    {{-- Character info --}}
                    <div>
                        <h2 class="text-xl font-bold text-green-900 medieval-font">{{ $character->name }}</h2>
                        <div class="text-sm text-green-700">
                            Poziom {{ $character->level }} • {{ $character->xp }} XP •
                            {{ number_format($character->gold) }} złota
                        </div>
                    </div>
                </div>
            </div>

            {{-- Back button --}}
            <button wire:click="backToHub"
                class="bg-gradient-to-r from-slate-600 to-slate-700 hover:from-slate-700 hover:to-slate-800 text-amber-200 font-bold py-2 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg medieval-font">
                🏰 Powrót do miasta
            </button>
        </div>

        {{-- Title --}}
        <div class="text-center mb-12">
            <h1
                class="text-5xl font-bold bg-gradient-to-r from-green-300 via-emerald-400 to-green-500 bg-clip-text text-transparent medieval-font drop-shadow-2xl mb-2">
                🗺️ Wybierz Przygodę
            </h1>
            <p class="text-xl text-green-200 font-semibold drop-shadow-lg">
                Twój poziom: {{ $character->level }} • Wybierz mapę odpowiednią dla Ciebie
            </p>
        </div>

        {{-- Map access error --}}
        @error('map_access')
            <div class="mb-6 p-4 bg-red-100/90 border-2 border-red-600 rounded-lg backdrop-blur-sm max-w-2xl mx-auto">
                <p class="text-red-800 font-semibold text-center">{{ $message }}</p>
            </div>
        @enderror

        {{-- Maps grid - 2 kolumny na desktop, 1 na mobile --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 max-w-6xl mx-auto">
            @foreach ($maps as $map)
                @php
                    $isAccessible = $map->isAccessibleBy($character);
                    $isCurrentLevel = $character->level >= $map->level_min && $character->level <= $map->level_max;

                    // Najpierw sprawdź czy obraz z bazy istnieje (dodaj img/ prefix jeśli potrzeba)
                    $imagePath = null;
                    if ($map->image_path) {
                        if (str_starts_with($map->image_path, 'img/')) {
                            $imagePath = $map->image_path;
                        } else {
                            $imagePath = 'img/' . $map->image_path;
                        }
                    }

                    $imageExists = $imagePath && file_exists(public_path($imagePath));

                    // Jeśli nie istnieje, użyj hardcoded mapping
                    if (!$imageExists) {
                        $hardcodedImages = [
                            'Mroczny Las' => 'img/maps/dark-forest.png',
                            'Stare Ruiny' => 'img/maps/old-ruins.png',
                            'Jaskinia Trolli' => 'img/maps/troll-cave.png',
                            'Pustkowia Orków' => 'img/maps/orc-wasteland.png',
                            'Bagna Grozy' => 'img/maps/horror-swamps.png',
                            'Góry Cienia' => 'img/maps/shadow-mountains.png',
                            'Wieża Magów' => 'img/maps/shadow-mountains.png',
                            'Skażone Miasto' => 'img/maps/corrupted-city.png',
                        ];

                        $fallbackPath = $hardcodedImages[$map->name] ?? null;
                        if ($fallbackPath && file_exists(public_path($fallbackPath))) {
                            $imagePath = $fallbackPath;
                            $imageExists = true;
                        }
                    }
                @endphp

                <div class="relative group">
                    <div
                        class="bg-gradient-to-br from-green-50/90 to-green-100/90 border-4 {{ $isAccessible ? 'border-green-700' : 'border-gray-500' }} rounded-lg shadow-2xl backdrop-blur-sm {{ $isAccessible ? 'hover:from-green-100/95 hover:to-green-200/95 hover:shadow-3xl' : 'opacity-50' }} transition-all duration-300 {{ $isAccessible ? 'transform hover:scale-105 cursor-pointer' : 'cursor-not-allowed' }}">

                        {{-- Decorative corners --}}
                        @if ($isAccessible)
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
                        @endif

                        {{-- Current level indicator --}}
                        @if ($isCurrentLevel)
                            <div
                                class="absolute -top-2 -right-2 bg-yellow-500 text-yellow-900 px-3 py-1 rounded-full text-sm font-bold shadow-lg border-2 border-yellow-600 z-10">
                                🌟 TWÓJ POZIOM
                            </div>
                        @endif

                        <div class="relative p-6">
                            {{-- Map image --}}
                            <div
                                class="w-full h-40 rounded-lg mb-4 overflow-hidden border-2 {{ $isAccessible ? 'border-green-600' : 'border-gray-600' }} relative">
                                @if ($imageExists)
                                    {{-- Rzeczywisty obraz mapy --}}
                                    <img src="{{ asset($imagePath) }}" alt="{{ $map->name }}"
                                        class="w-full h-full object-cover {{ $isAccessible ? '' : 'grayscale' }} transition-all duration-300"
                                        loading="lazy">

                                    {{-- Overlay z kłódką dla niedostępnych map --}}
                                    @if (!$isAccessible)
                                        <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                            <div class="text-4xl text-gray-300">🔒</div>
                                        </div>
                                    @endif
                                @else
                                    {{-- Emoji placeholder jako ostatnia opcja --}}
                                    <div
                                        class="w-full h-full {{ $isAccessible ? 'bg-gradient-to-b from-green-200 to-green-400' : 'bg-gradient-to-b from-gray-300 to-gray-500' }} flex items-center justify-center">
                                        <div class="text-4xl {{ $isAccessible ? 'text-green-800' : 'text-gray-700' }}">
                                            @switch($map->name)
                                                @case('Mroczny Las')
                                                    🌲
                                                @break

                                                @case('Stare Ruiny')
                                                    🏛️
                                                @break

                                                @case('Jaskinia Trolli')
                                                    🕳️
                                                @break

                                                @case('Pustkowia Orków')
                                                    🏜️
                                                @break

                                                @case('Bagna Grozy')
                                                    🌿
                                                @break

                                                @case('Góry Cienia')
                                                    ⛰️
                                                @break

                                                @case('Wieża Magów')
                                                    🗼
                                                @break

                                                @case('Skażone Miasto')
                                                    🏙️
                                                @break

                                                @default
                                                    🗺️
                                            @endswitch
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Map info --}}
                            <div class="text-center">
                                <h3
                                    class="text-2xl font-bold {{ $isAccessible ? 'text-green-900' : 'text-gray-700' }} medieval-font mb-2">
                                    {{ $map->name }}
                                </h3>

                                <div
                                    class="text-lg font-semibold {{ $isAccessible ? 'text-green-800' : 'text-gray-600' }} mb-3">
                                    Poziom {{ $map->level_range }}
                                    @if (isset($map->tier))
                                        • Tier {{ $map->tier }}
                                    @endif
                                </div>

                                {{-- Action button --}}
                                @if ($isAccessible)
                                    <button wire:click="enterMap('{{ $map->id }}')"
                                        class="w-full bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white font-bold py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg medieval-font">
                                        ⚔️ Wejdź na mapę
                                    </button>
                                @else
                                    <div
                                        class="w-full bg-gray-400 text-gray-700 font-bold py-3 px-4 rounded-lg cursor-not-allowed medieval-font">
                                        🔒 Niedostępne
                                    </div>
                                @endif

                                {{-- Level requirement info --}}
                                @if (!$isAccessible)
                                    <div class="mt-2 text-xs text-gray-600">
                                        @if ($character->level < $map->level_min)
                                            Wymagany poziom: {{ $map->level_min }}
                                        @else
                                            Za wysoki poziom (max: {{ $map->level_max }})
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- No maps available message --}}
        @if ($maps->isEmpty())
            <div class="text-center">
                <div
                    class="bg-gradient-to-br from-amber-50/95 to-amber-100/95 border-4 border-amber-700 rounded-lg p-12 shadow-2xl backdrop-blur-sm max-w-2xl mx-auto">
                    <div class="text-8xl mb-6">🗺️</div>
                    <h2 class="text-3xl font-bold text-amber-900 medieval-font mb-4">Brak Dostępnych Map</h2>
                    <p class="text-xl text-amber-800 font-semibold">
                        Aktualnie nie ma żadnych map do eksploracji. Wróć później!
                    </p>
                </div>
            </div>
        @endif
    </div>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&display=swap');

        .medieval-font {
            font-family: 'Cinzel', serif;
        }

        /* Adventure floating elements */
        .adventure-element {
            position: absolute;
            font-size: 1.5rem;
            opacity: 0.7;
            pointer-events: none;
            animation: float-adventure 15s infinite linear;
        }

        .adventure-element-1 {
            left: 10%;
            animation-delay: 0s;
            animation-duration: 20s;
        }

        .adventure-element-2 {
            left: 30%;
            animation-delay: 4s;
            animation-duration: 18s;
        }

        .adventure-element-3 {
            left: 50%;
            animation-delay: 8s;
            animation-duration: 22s;
        }

        .adventure-element-4 {
            left: 70%;
            animation-delay: 12s;
            animation-duration: 16s;
        }

        .adventure-element-5 {
            left: 85%;
            animation-delay: 16s;
            animation-duration: 19s;
        }

        @keyframes float-adventure {
            0% {
                transform: translateY(100vh) translateX(0px) rotate(0deg);
                opacity: 0;
            }

            10% {
                opacity: 0.7;
            }

            90% {
                opacity: 0.7;
            }

            100% {
                transform: translateY(-100px) translateX(30px) rotate(180deg);
                opacity: 0;
            }
        }

        /* Image hover effects */
        .group:hover img {
            transform: scale(1.05);
        }

        img {
            transition: transform 0.3s ease;
        }
    </style>
</div>
