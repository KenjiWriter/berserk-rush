<div x-data="{ 
        teleporting: false, 
        startTeleport(url) {
            this.teleporting = true;
            setTimeout(() => {
                window.location.href = url;
            }, 1200);
        },
        trailerOpen: false,
        galleryGridOpen: false,
        gallerySliderOpen: false,
        currentGalleryIndex: 0,
        images: {{ isset($galleryImages) ? json_encode($galleryImages->map(fn($img) => ['path' => asset($img->image_path), 'title' => $img->title])->toArray()) : '[]' }},
        init() {
            if (!localStorage.getItem('trailer_seen')) {
                setTimeout(() => {
                    this.trailerOpen = true;
                    localStorage.setItem('trailer_seen', 'true');
                }, 1000);
            }
        },
        openGalleryGrid() {
            this.galleryGridOpen = true;
        },
        openGallerySlider(index) {
            this.currentGalleryIndex = index;
            this.gallerySliderOpen = true;
        },
        nextImage() {
            if (this.currentGalleryIndex < this.images.length - 1) {
                this.currentGalleryIndex++;
            } else {
                this.currentGalleryIndex = 0;
            }
        },
        prevImage() {
            if (this.currentGalleryIndex > 0) {
                this.currentGalleryIndex--;
            } else {
                this.currentGalleryIndex = this.images.length - 1;
            }
        }
    }"
    @keydown.right.window="if(gallerySliderOpen) nextImage()" 
    @keydown.left.window="if(gallerySliderOpen) prevImage()" 
    @keydown.escape.window="gallerySliderOpen = false; galleryGridOpen = false; trailerOpen = false"
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
        {{-- Game title (Moved to top for mobile) --}}
        <div class="text-center mb-6">
            <h1 class="text-6xl font-bold bg-gradient-to-r from-amber-300 via-yellow-400 to-amber-500 bg-clip-text text-transparent medieval-font drop-shadow-2xl animate-pulse">
                Berserk Rush
            </h1>
            <p class="text-xl text-amber-200 mt-2 font-semibold drop-shadow-lg">
                Średniowieczne RPG przeglądarowe
            </p>
        </div>

        <livewire:auth.login-modal :hide-button="true" />

        <div class="flex flex-col lg:grid lg:grid-cols-6 lg:gap-6">
            {{-- Left Sidebar --}}
            <div class="contents lg:block lg:col-span-1 lg:space-y-6">
                {{-- Gallery Section --}}
                @if(isset($galleryImages) && $galleryImages->isNotEmpty())
                <div class="order-0 lg:order-none mb-6 lg:mb-0 bg-gradient-to-br from-amber-50/95 to-amber-100/95 border-4 border-amber-700 rounded-lg p-4 shadow-2xl backdrop-blur-sm relative overflow-hidden">
                    {{-- Decorative corners --}}
                    <div class="absolute top-0 left-0 w-6 h-6 bg-amber-800 transform rotate-45 -translate-x-3 -translate-y-3"></div>
                    <div class="absolute top-0 right-0 w-6 h-6 bg-amber-800 transform rotate-45 translate-x-3 -translate-y-3"></div>
                    <div class="absolute bottom-0 left-0 w-6 h-6 bg-amber-800 transform rotate-45 -translate-x-3 translate-y-3"></div>
                    <div class="absolute bottom-0 right-0 w-6 h-6 bg-amber-800 transform rotate-45 translate-x-3 translate-y-3"></div>

                    <div class="relative">
                        <h3 class="text-lg font-bold text-amber-900 mb-3 text-center border-b-2 border-amber-700 pb-2 medieval-font">
                            🖼️ Galeria Gry
                        </h3>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($galleryImages->take(4) as $image)
                                <div class="relative group cursor-pointer border-2 border-amber-600 rounded overflow-hidden aspect-video" @click="openGallerySlider({{ $loop->index }})">
                                    <img src="{{ asset($image->image_path) }}" alt="{{ $image->title }}" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110">
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <span class="text-white text-xs font-bold drop-shadow-md">Powiększ</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="mt-4 text-center">
                            <button @click="openGalleryGrid()" class="bg-gradient-to-r from-amber-700 to-amber-600 hover:from-amber-600 hover:to-amber-500 text-white font-bold py-2 px-4 rounded w-full border border-amber-500 shadow-md transition-all duration-300 transform hover:scale-[1.02]">
                                @if($galleryImages->count() > 4)
                                    Pokaż całą galerię ({{ $galleryImages->count() }})
                                @else
                                    Otwórz galerię
                                @endif
                            </button>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Active players --}}
                <div
                    class="order-1 lg:order-none mb-6 lg:mb-0 bg-gradient-to-br from-amber-50/95 to-amber-100/95 border-4 border-amber-700 rounded-lg p-4 shadow-2xl backdrop-blur-sm relative overflow-hidden">
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
                    class="order-4 lg:order-none mb-6 lg:mb-0 bg-gradient-to-br from-amber-50/95 to-amber-100/95 border-4 border-amber-700 rounded-lg p-4 shadow-2xl backdrop-blur-sm relative overflow-hidden">
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
            </div>

            {{-- Main content area --}}
            <div class="contents lg:block lg:col-span-4 lg:order-2">
                @guest
                    {{-- Hidden SVG ClipPath Definitions for S-Shaped Teardrop Hover Zones --}}
                    <svg class="absolute w-0 h-0 pointer-events-none" aria-hidden="true">
                        <defs>
                            {{-- Top-Left Sun Teardrop S-Clip --}}
                            <clipPath id="clip-yin-left" clipPathUnits="objectBoundingBox">
                                <path d="M 0.5,0 A 0.25,0.25 0 0,1 0.5,0.5 A 0.25,0.25 0 0,0 0.5,1 A 0.5,0.5 0 0,1 0.5,0 Z" />
                            </clipPath>
                            {{-- Bottom-Right Shadow Teardrop S-Clip --}}
                            <clipPath id="clip-yin-right" clipPathUnits="objectBoundingBox">
                                <path d="M 0.5,0 A 0.25,0.25 0 0,1 0.5,0.5 A 0.25,0.25 0 0,0 0.5,1 A 0.5,0.5 0 0,0 0.5,0 Z" />
                            </clipPath>
                        </defs>
                    </svg>

                    {{-- Central Yin-Yang Auth Medallion (Mounted over Ogłoszenia Królewskie) --}}
                    <div class="flex flex-col items-center justify-center -mb-28 sm:-mb-36 relative z-30 pointer-events-none">
                        {{-- Ambient glowing background aura --}}
                        <div class="absolute w-[320px] h-[320px] sm:w-[400px] sm:h-[400px] rounded-full bg-gradient-to-r from-amber-500/30 via-yellow-400/25 to-slate-900/40 blur-3xl animate-pulse"></div>

                        <div class="relative w-72 h-72 sm:w-88 sm:h-88 sm:w-[350px] sm:h-[350px] rounded-full border-4 border-amber-500/90 shadow-[0_0_60px_rgba(245,158,11,0.7)] overflow-hidden bg-slate-950 transition-transform duration-500 hover:scale-[1.03] pointer-events-auto">
                            {{-- Background Yin-Yang AI Image --}}
                            <img src="{{ asset('img/yin_yang_auth.png') }}" alt="Berserk Rush Portal" class="absolute inset-0 w-full h-full object-cover select-none">

                            {{-- Golden S-curve dividing line SVG (Thinner, transparent & subtle) --}}
                            <svg class="absolute inset-0 w-full h-full pointer-events-none z-10 opacity-35" viewBox="0 0 100 100">
                                <path d="M 50,0 A 25,25 0 0,1 50,50 A 25,25 0 0,0 50,100" 
                                      fill="none" 
                                      stroke="#fef08a" 
                                      stroke-width="0.8" 
                                      class="drop-shadow-[0_0_6px_rgba(250,204,21,0.6)]" />
                            </svg>

                            {{-- Top-Left Half (Stwórz Konto) with S-Curve Teardrop Clip & Glow --}}
                            <a href="{{ route('register') }}" 
                               class="absolute inset-0 group/left transition-all duration-300 hover:bg-amber-500/25 z-20 flex flex-col items-start justify-start pt-9 sm:pt-12 pl-7 sm:pl-10 w-full h-full"
                               style="clip-path: url(#clip-yin-left); -webkit-clip-path: url(#clip-yin-left);">
                                <div class="transform transition-transform duration-300 group-hover/left:scale-110 flex flex-col items-center text-center max-w-[150px] sm:max-w-[180px]">
                                    <span class="text-3xl sm:text-4xl drop-shadow-[0_4px_8px_rgba(0,0,0,0.9)]">⚔️</span>
                                    <span class="text-sm sm:text-base font-extrabold text-amber-200 medieval-font drop-shadow-[0_2px_10px_rgba(0,0,0,1)] tracking-widest mt-1 border-b-2 border-amber-400 pb-0.5 whitespace-nowrap">
                                        STWÓRZ KONTO
                                    </span>
                                    <span class="text-[10px] sm:text-xs text-amber-300 font-sans font-bold tracking-wider uppercase mt-1 bg-black/75 px-2.5 py-0.5 rounded-full border border-amber-500/60 shadow-lg">
                                        Dołącz teraz
                                    </span>
                                </div>
                            </a>

                            {{-- Bottom-Right Half (Zaloguj się) with S-Curve Teardrop Clip & Glow --}}
                            <div @click="$dispatch('open-login-modal')" 
                                 class="absolute inset-0 group/right cursor-pointer transition-all duration-300 hover:bg-purple-900/35 z-20 flex flex-col items-end justify-end pb-9 sm:pb-12 pr-7 sm:pr-10 w-full h-full"
                                 style="clip-path: url(#clip-yin-right); -webkit-clip-path: url(#clip-yin-right);">
                                <div class="transform transition-transform duration-300 group-hover/right:scale-110 flex flex-col items-center text-center max-w-[150px] sm:max-w-[180px]">
                                    <span class="text-3xl sm:text-4xl drop-shadow-[0_4px_8px_rgba(0,0,0,0.9)]">🗝️</span>
                                    <span class="text-sm sm:text-base font-extrabold text-slate-100 medieval-font drop-shadow-[0_2px_10px_rgba(0,0,0,1)] tracking-widest mt-1 border-b-2 border-slate-300 pb-0.5 whitespace-nowrap">
                                        ZALOGUJ SIĘ
                                    </span>
                                    <span class="text-[10px] sm:text-xs text-slate-200 font-sans font-bold tracking-wider uppercase mt-1 bg-black/75 px-2.5 py-0.5 rounded-full border border-slate-400/60 shadow-lg">
                                        Wróć do gry
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endguest
                @auth
                    {{-- Horizontal Character Selection Bar (Above Ogłoszenia Królewskie) --}}
                    <div class="order-5 lg:order-none mb-6 relative bg-gradient-to-br from-amber-50/95 via-amber-100/95 to-amber-50/95 border-4 border-amber-700 rounded-lg p-4 sm:p-5 shadow-2xl backdrop-blur-sm overflow-hidden">
                        {{-- Decorative corners --}}
                        <div class="absolute top-0 left-0 w-6 h-6 bg-amber-800 transform rotate-45 -translate-x-3 -translate-y-3"></div>
                        <div class="absolute top-0 right-0 w-6 h-6 bg-amber-800 transform rotate-45 translate-x-3 -translate-y-3"></div>
                        <div class="absolute bottom-0 left-0 w-6 h-6 bg-amber-800 transform rotate-45 -translate-x-3 translate-y-3"></div>
                        <div class="absolute bottom-0 right-0 w-6 h-6 bg-amber-800 transform rotate-45 translate-x-3 translate-y-3"></div>

                        <div class="relative">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-4 border-b-2 border-amber-700/60 pb-2.5 gap-2">
                                <h3 class="text-xl font-bold text-amber-900 medieval-font flex items-center gap-2">
                                    <span>🗡️</span> Twój Oddział Bohaterów
                                </h3>
                                <span class="text-xs text-amber-900 font-semibold bg-amber-200/90 px-3 py-1 rounded-full border border-amber-600/40 shadow-sm">
                                    Wybierz postać, aby ruszyć na szlak
                                </span>
                            </div>

                            @php
                                $shouldHighlightNewCharacter = Auth::check() && Auth::user()->game_stage == 1 && collect($myCharacters)->filter()->count() == 0;
                                $shouldHighlightPlayButton = Auth::check() && Auth::user()->game_stage == 3 && collect($myCharacters)->filter()->count() > 0;
                            @endphp

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                                @foreach ($myCharacters as $index => $character)
                                    <div class="bg-gradient-to-b from-amber-100/90 to-amber-200/90 border-2 border-amber-600/80 rounded-xl p-3.5 hover:bg-amber-200/95 transition-all duration-300 shadow-md hover:shadow-xl relative group {{ ($shouldHighlightNewCharacter && !$character) || ($shouldHighlightPlayButton && $character) ? 'animate-[pulse_1.5s_ease-in-out_infinite] ring-4 ring-amber-500 scale-105 shadow-[0_0_20px_rgba(245,158,11,0.6)] z-10' : '' }}">
                                        @if ($character)
                                            <a href="{{ route('characters.play', $character) }}" @click.prevent="startTeleport('{{ route('characters.play', $character) }}')" class="block h-full">
                                                <div class="flex items-center space-x-3 h-full">
                                                    {{-- Avatar --}}
                                                    <div class="relative flex-shrink-0">
                                                        <div class="w-12 h-12 border-2 border-amber-700 rounded-full overflow-hidden bg-gradient-to-b from-amber-200 to-amber-400 shadow-md group-hover:scale-105 transition-transform">
                                                            @if ($character->avatar && file_exists(public_path('img/avatars/' . $character->avatar . '.png')))
                                                                <img src="{{ asset('img/avatars/' . $character->avatar . '.png') }}" alt="Avatar {{ $character->avatar }}" class="w-full h-full object-cover">
                                                            @else
                                                                <div class="w-full h-full flex items-center justify-center text-xl text-amber-800">
                                                                    ⚔️
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <span class="absolute -bottom-1 -right-1 bg-amber-800 text-amber-100 text-[10px] font-bold px-1.5 py-0.2 rounded-full border border-amber-400">
                                                            {{ $character->level }} lvl
                                                        </span>
                                                    </div>

                                                    {{-- Info --}}
                                                    <div class="flex-1 min-w-0">
                                                        <div class="font-bold text-amber-950 text-sm truncate medieval-font group-hover:text-amber-700 transition-colors">
                                                            {{ $character->name }}
                                                        </div>
                                                        <div class="text-[11px] text-amber-800/90 font-medium">
                                                            Poziom {{ $character->level }}
                                                            @if ($character->attributes)
                                                                • {{ $character->getTotalAttributePoints() }} pkt
                                                            @endif
                                                        </div>
                                                        <div class="mt-1 flex items-center gap-1 text-[11px] text-green-700 font-bold">
                                                            <span>▶️ GRAJ teraz</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                        @else
                                            @if ($canCreateCharacter ?? true)
                                                <a href="{{ route('characters.create') }}" class="block h-full min-h-[56px]">
                                                    <div class="text-center text-amber-800 hover:text-amber-950 transition-colors flex flex-col items-center justify-center h-full border-2 border-dashed border-amber-600/60 rounded-lg p-2 hover:bg-amber-300/30">
                                                        <div class="text-xl mb-0.5">➕</div>
                                                        <div class="text-xs font-bold medieval-font">STWÓRZ POSTAĆ</div>
                                                    </div>
                                                </a>
                                            @else
                                                <div class="text-center text-amber-700/50 flex flex-col items-center justify-center h-full min-h-[56px] border-2 border-dashed border-amber-400/40 rounded-lg p-2">
                                                    <div class="text-xl mb-0.5">🔒</div>
                                                    <div class="text-xs font-bold medieval-font">SLOT ZABLOKOWANY</div>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endauth

                {{-- Admin messages parchment --}}
                <div class="order-6 lg:order-none mb-6 lg:mb-0 relative">
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

                        <div class="relative px-6 sm:px-8 pb-8 @guest pt-32 sm:pt-40 @else pt-8 @endguest">
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
            <div class="contents lg:block lg:col-span-1 lg:space-y-6 lg:order-3">
                @auth
                    {{-- User Info Panel --}}
                    <div
                        class="order-2 lg:order-none mb-6 lg:mb-0 bg-gradient-to-br from-amber-50/95 to-amber-100/95 border-4 border-amber-700 rounded-lg p-4 shadow-2xl backdrop-blur-sm relative overflow-hidden">
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

                    {{-- Premium Shop Panel --}}
                    <div
                        class="order-3 lg:order-none mb-6 lg:mb-0 bg-gradient-to-br from-yellow-100/90 to-amber-200/90 border-4 border-yellow-500 rounded-lg p-4 shadow-[0_0_20px_rgba(250,204,21,0.4)] backdrop-blur-sm relative overflow-hidden transition-all duration-300 transform hover:scale-105 hover:shadow-[0_0_30px_rgba(250,204,21,0.6)]">
                        <div class="absolute top-0 left-0 w-6 h-6 bg-yellow-600 transform rotate-45 -translate-x-3 -translate-y-3"></div>
                        <div class="absolute top-0 right-0 w-6 h-6 bg-yellow-600 transform rotate-45 translate-x-3 -translate-y-3"></div>
                        <div class="absolute bottom-0 left-0 w-6 h-6 bg-yellow-600 transform rotate-45 -translate-x-3 translate-y-3"></div>
                        <div class="absolute bottom-0 right-0 w-6 h-6 bg-yellow-600 transform rotate-45 translate-x-3 translate-y-3"></div>

                        <div class="relative text-center">
                            <h3 class="text-lg font-bold text-amber-900 mb-3 text-center border-b-2 border-yellow-600 pb-2 medieval-font flex items-center justify-center gap-2">
                                <span class="relative">💎<span class="absolute -top-2 -right-2 text-xs animate-pulse">✨</span></span> Sklep Premium
                            </h3>
                            
                            <a href="{{ route('itemshop') }}" wire:navigate
                                class="w-full bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-white font-bold py-3 px-4 rounded-lg transition-all duration-200 shadow-lg text-center block medieval-font tracking-widest border border-yellow-400">
                                👑 PRZEJDŹ DO SKLEPU
                            </a>
                            
                            @if(Auth::user()->hasPremium())
                                <div class="mt-3 text-xs font-bold text-yellow-700 bg-yellow-500/20 rounded py-1 px-2">
                                    Premium aktywne ({{ Auth::user()->premium_until->diffForHumans() }})
                                </div>
                            @endif
                        </div>
                    </div>
                @endauth

                {{-- Quick game info (O Grze) --}}
                <div
                    class="order-3 lg:order-none mb-6 lg:mb-0 bg-gradient-to-br from-amber-50/95 to-amber-100/95 border-4 border-amber-700 rounded-lg p-4 shadow-2xl backdrop-blur-sm relative overflow-hidden @guest lg:mt-[165px] @endguest">
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

                {{-- Top 10 Gildii (Moved under O Grze) --}}
                <div
                    class="order-5 lg:order-none mb-6 lg:mb-0 bg-gradient-to-br from-amber-50/95 to-amber-100/95 border-4 border-amber-700 rounded-lg p-4 shadow-2xl backdrop-blur-sm relative overflow-hidden">
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

    @auth
        @if(Auth::user()->characters()->count() == 0 && Auth::user()->game_stage == 0)
            <livewire:global.tutorial-overlay step="1" />
        @elseif(Auth::user()->characters()->count() > 0 && Auth::user()->game_stage == 2)
            <livewire:global.tutorial-overlay step="3" />
        @endif
    @endauth

    {{-- Teleport Animation Overlay --}}
    <div x-show="teleporting" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-[100] bg-slate-950 flex flex-col items-center justify-center"
         style="display: none;">
         
        <div class="relative flex flex-col items-center justify-center"
             x-show="teleporting"
             x-transition:enter="transition ease-in duration-700 delay-100"
             x-transition:enter-start="scale-50 opacity-0"
             x-transition:enter-end="scale-100 opacity-100">
             
            {{-- Magical Portal effect --}}
            <div class="absolute w-64 h-64 border-t-4 border-l-4 border-amber-500 rounded-full animate-[spin_1.5s_linear_infinite] shadow-[0_0_50px_rgba(245,158,11,0.5)]"></div>
            <div class="absolute w-48 h-48 border-b-4 border-r-4 border-yellow-400 rounded-full animate-[spin_1s_linear_infinite_reverse] shadow-[0_0_30px_rgba(250,204,21,0.6)]"></div>
            <div class="absolute w-24 h-24 bg-gradient-to-tr from-amber-400 to-yellow-200 rounded-full animate-pulse shadow-[0_0_60px_rgba(251,191,36,1)]"></div>
            
            <div class="mt-80 text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-amber-300 to-yellow-500 medieval-font drop-shadow-[0_0_10px_rgba(251,191,36,0.8)] animate-pulse text-center">
                Wkraczanie do świata gry...<br>
                <span class="text-sm text-amber-600/80 font-sans tracking-widest uppercase mt-3 block">Przygotuj się do walki</span>
            </div>
        </div>
    </div>

    {{-- Trailer Modal --}}
    <div x-show="trailerOpen" 
         style="display: none;"
         class="fixed inset-0 z-[100] flex items-center justify-center p-4">
         
         <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="trailerOpen = false"></div>
         
         <div class="relative bg-gradient-to-b from-slate-900 to-black border-4 border-amber-600 rounded-xl shadow-[0_0_50px_rgba(217,119,6,0.5)] p-2 w-full max-w-4xl"
              x-transition:enter="transition ease-out duration-500"
              x-transition:enter-start="opacity-0 scale-90 translate-y-8"
              x-transition:enter-end="opacity-100 scale-100 translate-y-0"
              x-transition:leave="transition ease-in duration-300"
              x-transition:leave-start="opacity-100 scale-100"
              x-transition:leave-end="opacity-0 scale-95">
              
             <button @click="trailerOpen = false" class="absolute -top-4 -right-4 w-10 h-10 bg-amber-600 hover:bg-amber-500 text-white rounded-full border-2 border-amber-200 shadow-lg flex items-center justify-center font-bold text-xl z-10 transition-colors">✕</button>
             
             <h2 class="text-2xl font-bold text-amber-400 mb-4 text-center medieval-font mt-4">Zobacz zwiastun</h2>
             
             <div class="relative pt-[56.25%] w-full bg-black rounded border border-amber-800 overflow-hidden">
                 <template x-if="trailerOpen">
                     <iframe class="absolute inset-0 w-full h-full" 
                             src="https://www.youtube.com/embed/GuD7lisUF3E?autoplay=1" 
                             title="YouTube video player" 
                             frameborder="0" 
                             allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                             allowfullscreen>
                     </iframe>
                 </template>
             </div>
         </div>
    </div>

    {{-- Gallery Grid Modal --}}
    <div x-show="galleryGridOpen" 
         style="display: none;"
         class="fixed inset-0 z-[105] flex items-center justify-center p-4">
         
         <div class="absolute inset-0 bg-black/90 backdrop-blur-md" @click="galleryGridOpen = false"></div>
         
         <div class="relative w-full max-w-6xl flex flex-col items-center bg-slate-900 border-4 border-amber-700 rounded-xl shadow-[0_0_50px_rgba(217,119,6,0.3)] p-6 max-h-[90vh] overflow-hidden"
              x-transition:enter="transition ease-out duration-300"
              x-transition:enter-start="opacity-0 scale-95"
              x-transition:enter-end="opacity-100 scale-100"
              x-transition:leave="transition ease-in duration-200"
              x-transition:leave-start="opacity-100 scale-100"
              x-transition:leave-end="opacity-0 scale-95">
              
             <button @click="galleryGridOpen = false" class="absolute top-4 right-4 text-amber-500 hover:text-white font-bold text-xl flex items-center gap-2 transition-colors z-10">
                 <span class="text-3xl drop-shadow-md">✕</span>
             </button>
             
             <h2 class="text-3xl font-bold text-amber-400 mb-6 text-center medieval-font w-full border-b border-amber-800 pb-4">Wszystkie Zdjęcia</h2>
             
             <div class="w-full overflow-y-auto pr-2" style="scrollbar-width: thin; scrollbar-color: #b45309 #1e293b;">
                 <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                     <template x-for="(image, index) in images" :key="index">
                         <div class="relative group cursor-pointer border-2 border-amber-700 rounded-lg overflow-hidden aspect-video shadow-lg hover:border-amber-400 transition-colors" 
                              @click="openGallerySlider(index)">
                             <img :src="image.path" :alt="image.title" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                             <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-end p-3">
                                 <span class="text-amber-300 text-sm font-bold truncate drop-shadow-md" x-text="image.title"></span>
                             </div>
                         </div>
                     </template>
                 </div>
             </div>
         </div>
    </div>

    {{-- Gallery Slider Modal --}}
    <div x-show="gallerySliderOpen" 
         style="display: none;"
         class="fixed inset-0 z-[110] flex items-center justify-center p-4 group/slider">
         
         <div class="absolute inset-0 bg-black/95 backdrop-blur-md" @click="gallerySliderOpen = false"></div>
         
         <div class="relative w-full max-w-6xl flex flex-col items-center"
              x-transition:enter="transition ease-out duration-300"
              x-transition:enter-start="opacity-0 scale-95"
              x-transition:enter-end="opacity-100 scale-100"
              x-transition:leave="transition ease-in duration-200"
              x-transition:leave-start="opacity-100 scale-100"
              x-transition:leave-end="opacity-0 scale-95">
              
             <button @click="gallerySliderOpen = false" class="absolute -top-12 right-0 text-amber-500 hover:text-white font-bold text-xl flex items-center gap-2 transition-colors">
                 <span>ZAMKNIJ</span> <span class="text-3xl">✕</span>
             </button>
             
             {{-- Prev Arrow --}}
             <button @click.stop="prevImage()" class="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-black/50 hover:bg-amber-600 border border-amber-600/50 hover:border-amber-400 rounded-full text-white flex items-center justify-center text-2xl transition-all opacity-50 group-hover/slider:opacity-100 z-20">
                 &#10094;
             </button>
             
             {{-- Next Arrow --}}
             <button @click.stop="nextImage()" class="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-black/50 hover:bg-amber-600 border border-amber-600/50 hover:border-amber-400 rounded-full text-white flex items-center justify-center text-2xl transition-all opacity-50 group-hover/slider:opacity-100 z-20">
                 &#10095;
             </button>

             <div class="bg-slate-900 border-4 border-amber-700 rounded-lg shadow-[0_0_40px_rgba(0,0,0,0.8)] overflow-hidden w-full relative">
                 <img :src="images[currentGalleryIndex]?.path" :alt="images[currentGalleryIndex]?.title" class="w-full h-auto max-h-[85vh] object-contain bg-black">
                 <div class="p-4 bg-gradient-to-t from-slate-900 via-slate-900/80 to-transparent absolute bottom-0 left-0 right-0">
                     <h3 class="text-2xl font-bold text-amber-400 medieval-font text-center drop-shadow-md" x-text="images[currentGalleryIndex]?.title"></h3>
                     <p class="text-center text-amber-600 text-sm mt-1">
                         <span x-text="currentGalleryIndex + 1"></span> / <span x-text="images.length"></span>
                     </p>
                 </div>
             </div>
         </div>
    </div>

    @auth
        @if(Auth::user()->is_social_setup_pending)
            <livewire:auth.social-setup-modal />
        @endif
    @endauth
</div>
