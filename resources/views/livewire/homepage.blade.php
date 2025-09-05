<div
    class="min-h-screen bg-gradient-to-b from-slate-800/90 via-slate-700/90 to-slate-800/90 text-amber-100 relative overflow-hidden">
    {{-- Background image --}}
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat"
        style="background-image: url('{{ asset('img/homepage-background.png') }}');">
    </div>

    {{-- Dark overlay for readability --}}
    <div class="absolute inset-0 bg-gradient-to-b from-slate-900/60 via-slate-800/70 to-slate-900/60"></div>

    {{-- Floating particles --}}
    <div class="particles-container absolute inset-0 pointer-events-none">
        <div class="particle particle-1"></div>
        <div class="particle particle-2"></div>
        <div class="particle particle-3"></div>
        <div class="particle particle-4"></div>
        <div class="particle particle-5"></div>
        <div class="particle particle-6"></div>
        <div class="particle particle-7"></div>
        <div class="particle particle-8"></div>
        <div class="particle particle-9"></div>
        <div class="particle particle-10"></div>
    </div>

    {{-- Glowing orbs --}}
    <div class="absolute inset-0 pointer-events-none">
        <div class="glow-orb glow-orb-1"></div>
        <div class="glow-orb glow-orb-2"></div>
        <div class="glow-orb glow-orb-3"></div>
    </div>

    {{-- Background pattern overlay --}}
    <div class="absolute inset-0 opacity-10 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60"
        viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg
        fill="%23d97706" fill-opacity="0.3"%3E%3Cpath d="M30 30c0-16.569 13.431-30 30-30v60c-16.569 0-30-13.431-30-30z"
        /%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"></div>

    <div class="relative container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-6 gap-6">
            {{-- Left Sidebar --}}
            <div class="lg:col-span-1 space-y-6 order-2 lg:order-1">
                {{-- Active players --}}
                <div
                    class="bg-gradient-to-br from-amber-50/95 to-amber-100/95 border-4 border-amber-700 rounded-lg p-4 shadow-2xl backdrop-blur-sm relative overflow-hidden">
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

                    <div class="relative">
                        <h3
                            class="text-lg font-bold text-amber-900 mb-3 text-center border-b-2 border-amber-700 pb-2 medieval-font">
                            ⚔️ Aktywni Gracze
                        </h3>
                        <div class="text-center">
                            <span class="text-3xl font-bold text-green-700">{{ number_format($activePlayers) }}</span>
                            <p class="text-sm text-amber-800 font-semibold">online teraz</p>
                        </div>
                    </div>
                </div>

                {{-- Top Characters --}}
                <div
                    class="bg-gradient-to-br from-amber-50/95 to-amber-100/95 border-4 border-amber-700 rounded-lg p-4 shadow-2xl backdrop-blur-sm relative overflow-hidden">
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

                    <div class="relative">
                        <h3
                            class="text-lg font-bold text-amber-900 mb-3 text-center border-b-2 border-amber-700 pb-2 medieval-font">
                            👑 Top 10 Bohaterów
                        </h3>
                        <div class="space-y-2">
                            @foreach ($topCharacters as $index => $character)
                                <div
                                    class="flex items-center justify-between text-sm {{ $index < 3 ? 'text-yellow-700 font-bold' : 'text-amber-800' }}">
                                    <div class="flex items-center">
                                        <span class="w-6">{{ $index + 1 }}.</span>
                                        <span class="truncate">{{ $character['name'] }}</span>
                                    </div>
                                    <span class="font-bold">{{ $character['level'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Top Guilds --}}
                <div
                    class="bg-gradient-to-br from-amber-50/95 to-amber-100/95 border-4 border-amber-700 rounded-lg p-4 shadow-2xl backdrop-blur-sm relative overflow-hidden">
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

                    <div class="relative">
                        <h3
                            class="text-lg font-bold text-amber-900 mb-3 text-center border-b-2 border-amber-700 pb-2 medieval-font">
                            🏰 Top 10 Gildii
                        </h3>
                        <div class="space-y-2">
                            @foreach ($topGuilds as $index => $guild)
                                <div class="text-sm {{ $index < 3 ? 'text-yellow-700 font-bold' : 'text-amber-800' }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span class="w-6">{{ $index + 1 }}.</span>
                                            <span class="truncate">{{ $guild['name'] }}</span>
                                        </div>
                                        <span class="font-bold">{{ $guild['avgLevel'] }}</span>
                                    </div>
                                    <div class="text-xs text-amber-700 ml-6">{{ $guild['members'] }} członków</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Main content area --}}
            <div class="lg:col-span-4 order-1 lg:order-2">
                {{-- Game title --}}
                <div class="text-center mb-8">
                    <h1
                        class="text-6xl font-bold bg-gradient-to-r from-amber-300 via-yellow-400 to-amber-500 bg-clip-text text-transparent medieval-font drop-shadow-2xl animate-pulse">
                        Berserk Rush
                    </h1>
                    <p class="text-xl text-amber-200 mt-2 font-semibold drop-shadow-lg">
                        Średniowieczne RPG przeglądarowe
                    </p>
                </div>

                {{-- Admin messages parchment --}}
                <div class="relative">
                    {{-- Parchment background --}}
                    <div
                        class="bg-gradient-to-br from-amber-50/95 to-amber-100/95 rounded-lg border-4 border-amber-700 shadow-2xl relative overflow-hidden backdrop-blur-sm">
                        {{-- Decorative corners --}}
                        <div
                            class="absolute top-0 left-0 w-8 h-8 bg-amber-800 transform rotate-45 -translate-x-4 -translate-y-4">
                        </div>
                        <div
                            class="absolute top-0 right-0 w-8 h-8 bg-amber-800 transform rotate-45 translate-x-4 -translate-y-4">
                        </div>
                        <div
                            class="absolute bottom-0 left-0 w-8 h-8 bg-amber-800 transform rotate-45 -translate-x-4 translate-y-4">
                        </div>
                        <div
                            class="absolute bottom-0 right-0 w-8 h-8 bg-amber-800 transform rotate-45 translate-x-4 translate-y-4">
                        </div>

                        {{-- Parchment texture overlay --}}
                        <div class="absolute inset-0 opacity-20 bg-[url('data:image/svg+xml,%3Csvg width="100"
                            height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"%3E%3Cfilter
                            id="noiseFilter"%3E%3CfeTurbulence type="fractalNoise" baseFrequency="0.9" numOctaves="4"
                            stitchTiles="stitch"/%3E%3C/filter%3E%3Crect width="100" height="100"
                            filter="url(%23noiseFilter)" opacity="0.3"/%3E%3C/svg%3E')]"></div>

                        <div class="relative p-8">
                            <h2
                                class="text-3xl font-bold text-amber-900 mb-6 text-center medieval-font border-b-2 border-amber-700 pb-3">
                                📜 Ogłoszenia Królewskie 📜
                            </h2>

                            <div class="space-y-6">
                                @foreach ($adminMessages as $message)
                                    <div class="border-l-4 border-amber-700 pl-4">
                                        <h3 class="text-xl font-bold text-amber-900 mb-2">{{ $message['title'] }}</h3>
                                        <p class="text-amber-800 leading-relaxed mb-2">{{ $message['content'] }}</p>
                                        <p class="text-sm text-amber-600 italic">
                                            {{ date('j F Y', strtotime($message['date'])) }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Sidebar --}}
            <div class="lg:col-span-1 space-y-6 order-3">
                @auth
                    {{-- User Info Panel --}}
                    <div
                        class="bg-gradient-to-br from-amber-50/95 to-amber-100/95 border-4 border-amber-700 rounded-lg p-4 shadow-2xl backdrop-blur-sm relative overflow-hidden">
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

                        <div class="relative">
                            <h3
                                class="text-lg font-bold text-amber-900 mb-3 text-center border-b-2 border-amber-700 pb-2 medieval-font">
                                👤 {{ Auth::user()->name }}
                            </h3>
                            <div class="space-y-2">
                                <div class="text-center text-amber-800 text-sm">
                                    Witaj z powrotem, wojowniku!
                                </div>
                                <livewire:auth.logout-modal />
                            </div>
                        </div>
                    </div>

                    {{-- My Characters (when logged in) --}}
                    <div
                        class="bg-gradient-to-br from-amber-50/95 to-amber-100/95 border-4 border-amber-700 rounded-lg p-4 shadow-2xl backdrop-blur-sm relative overflow-hidden">
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

                        <div class="relative">
                            <h3
                                class="text-lg font-bold text-amber-900 mb-3 text-center border-b-2 border-amber-700 pb-2 medieval-font">
                                🗡️ Moje Postacie
                            </h3>
                            <div class="space-y-3">
                                @foreach ($myCharacters as $index => $character)
                                    <div
                                        class="bg-amber-100/80 border-2 border-amber-600 rounded p-3 hover:bg-amber-200/80 transition-colors cursor-pointer shadow-lg">
                                        @if ($character)
                                            <div class="text-center">
                                                <div class="font-bold text-amber-900">{{ $character['name'] }}</div>
                                                <div class="text-sm text-amber-700">Poziom {{ $character['level'] }}</div>
                                            </div>
                                        @else
                                            <div class="text-center text-amber-700 opacity-75">
                                                <div class="text-2xl mb-1">➕</div>
                                                <div class="text-sm font-semibold">Utwórz postać</div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Login/Register section (when not logged in) --}}
                    <div
                        class="bg-gradient-to-br from-amber-50/95 to-amber-100/95 border-4 border-amber-700 rounded-lg p-6 shadow-2xl backdrop-blur-sm relative overflow-hidden">
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

                        <div class="relative">
                            <h3
                                class="text-lg font-bold text-amber-900 mb-4 text-center border-b-2 border-amber-700 pb-2 medieval-font">
                                🎯 Rozpocznij Przygodę
                            </h3>

                            <div class="space-y-4">
                                <div class="text-center text-amber-800 text-sm mb-4 font-semibold">
                                    Dołącz do tysięcy wojowników w epickiej przygodzie!
                                </div>

                                <a href="{{ route('register') }}"
                                    class="w-full bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-700 hover:to-amber-800 text-white font-bold py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg text-center block medieval-font">
                                    ⚔️ Załóż Konto
                                </a>

                                <livewire:auth.login-modal />
                            </div>
                        </div>
                    </div>

                    {{-- Quick game info --}}
                    <div
                        class="bg-gradient-to-br from-amber-50/95 to-amber-100/95 border-4 border-amber-700 rounded-lg p-4 shadow-2xl backdrop-blur-sm relative overflow-hidden">
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

                        <div class="relative">
                            <h3
                                class="text-lg font-bold text-amber-900 mb-3 text-center border-b-2 border-amber-700 pb-2 medieval-font">
                                🎮 O Grze
                            </h3>
                            <div class="space-y-2 text-sm text-amber-800">
                                <div class="flex items-center">
                                    <span class="text-amber-700 font-bold">•</span>
                                    <span class="ml-2 font-semibold">Walki turowe</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-amber-700 font-bold">•</span>
                                    <span class="ml-2 font-semibold">Ulepszanie przedmiotów</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-amber-700 font-bold">•</span>
                                    <span class="ml-2 font-semibold">System gildii</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-amber-700 font-bold">•</span>
                                    <span class="ml-2 font-semibold">Ekonomia graczy</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-amber-700 font-bold">•</span>
                                    <span class="ml-2 font-semibold">Crafting</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </div>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&display=swap');

        .medieval-font {
            font-family: 'Cinzel', serif;
        }

        /* Floating particles */
        .particle {
            position: absolute;
            background: radial-gradient(circle, rgba(251, 191, 36, 0.8) 0%, rgba(251, 191, 36, 0.4) 50%, transparent 100%);
            border-radius: 50%;
            pointer-events: none;
            animation: float 15s infinite linear;
        }

        .particle-1 {
            width: 4px;
            height: 4px;
            left: 10%;
            animation-delay: 0s;
            animation-duration: 20s;
        }

        .particle-2 {
            width: 6px;
            height: 6px;
            left: 20%;
            animation-delay: 2s;
            animation-duration: 18s;
        }

        .particle-3 {
            width: 3px;
            height: 3px;
            left: 30%;
            animation-delay: 4s;
            animation-duration: 22s;
        }

        .particle-4 {
            width: 5px;
            height: 5px;
            left: 40%;
            animation-delay: 6s;
            animation-duration: 16s;
        }

        .particle-5 {
            width: 4px;
            height: 4px;
            left: 50%;
            animation-delay: 8s;
            animation-duration: 19s;
        }

        .particle-6 {
            width: 7px;
            height: 7px;
            left: 60%;
            animation-delay: 10s;
            animation-duration: 21s;
        }

        .particle-7 {
            width: 3px;
            height: 3px;
            left: 70%;
            animation-delay: 12s;
            animation-duration: 17s;
        }

        .particle-8 {
            width: 5px;
            height: 5px;
            left: 80%;
            animation-delay: 14s;
            animation-duration: 23s;
        }

        .particle-9 {
            width: 4px;
            height: 4px;
            left: 90%;
            animation-delay: 16s;
            animation-duration: 15s;
        }

        .particle-10 {
            width: 6px;
            height: 6px;
            left: 25%;
            animation-delay: 18s;
            animation-duration: 20s;
        }

        @keyframes float {
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
                transform: translateY(-100px) translateX(100px) rotate(360deg);
                opacity: 0;
            }
        }

        /* Glowing orbs */
        .glow-orb {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            filter: blur(1px);
        }

        .glow-orb-1 {
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(251, 191, 36, 0.15) 0%, transparent 70%);
            top: 20%;
            left: 80%;
            animation: glow-pulse 8s infinite ease-in-out;
        }

        .glow-orb-2 {
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.1) 0%, transparent 70%);
            top: 60%;
            left: 15%;
            animation: glow-pulse 6s infinite ease-in-out reverse;
        }

        .glow-orb-3 {
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(251, 191, 36, 0.2) 0%, transparent 70%);
            top: 10%;
            left: 30%;
            animation: glow-pulse 10s infinite ease-in-out;
        }

        @keyframes glow-pulse {

            0%,
            100% {
                opacity: 0.3;
                transform: scale(1);
            }

            50% {
                opacity: 0.6;
                transform: scale(1.1);
            }
        }

        /* Additional sparkle effect */
        .particles-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image:
                radial-gradient(2px 2px at 20px 30px, rgba(251, 191, 36, 0.4), transparent),
                radial-gradient(2px 2px at 40px 70px, rgba(245, 158, 11, 0.3), transparent),
                radial-gradient(1px 1px at 90px 40px, rgba(251, 191, 36, 0.5), transparent),
                radial-gradient(1px 1px at 130px 80px, rgba(245, 158, 11, 0.4), transparent),
                radial-gradient(2px 2px at 160px 30px, rgba(251, 191, 36, 0.3), transparent);
            background-repeat: repeat;
            background-size: 200px 100px;
            animation: sparkle 20s linear infinite;
        }

        @keyframes sparkle {
            0% {
                transform: translateY(0px);
            }

            100% {
                transform: translateY(-200px);
            }
        }
    </style>
</div>
