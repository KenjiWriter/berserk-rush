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
    class="rpg-home min-h-screen relative overflow-hidden">
    {{-- Background image --}}
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat"
        style="background-image: url('{{ asset('img/homepage-background.png') }}');">
    </div>

    {{-- Readability wash --}}
    <div class="absolute inset-0" style="background: linear-gradient(180deg, rgba(13,10,7,.80) 0%, rgba(13,10,7,.62) 22%, rgba(13,10,7,.72) 100%);"></div>
    <div class="absolute inset-0 pointer-events-none" style="box-shadow: inset 0 0 220px rgba(0,0,0,.65);"></div>

    {{-- Ambient embers --}}
    <div class="rpg-embers absolute inset-0 pointer-events-none overflow-hidden">
        <span class="rpg-ember rpg-ember-1"></span>
        <span class="rpg-ember rpg-ember-2"></span>
        <span class="rpg-ember rpg-ember-3"></span>
        <span class="rpg-ember rpg-ember-4"></span>
        <span class="rpg-ember rpg-ember-5"></span>
        <span class="rpg-ember rpg-ember-6"></span>
    </div>

    <div class="relative container mx-auto px-4 py-10">
        {{-- Title --}}
        <div class="text-center mb-6 relative">
            <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[420px] h-[220px] pointer-events-none" style="background: radial-gradient(ellipse, rgba(220,177,91,.28) 0%, transparent 70%); filter: blur(10px);"></div>

            <div class="relative inline-flex items-center justify-center w-14 h-14 rounded-full rpg-crest mb-2">
                <i class="fa-solid fa-fire-flame-curved"></i>
            </div>

            <div class="relative inline-block px-2">
                <div class="rpg-rule"></div>
                <h1 class="rpg-title">Berserk Rush</h1>
                <p class="rpg-subtitle">Średniowieczne RPG przeglądarowe</p>
                <div class="rpg-rule"></div>
            </div>

            <div class="relative mt-5 flex flex-wrap items-center justify-center gap-3">
                <div wire:poll.30s class="inline-flex items-center gap-2.5 px-4 py-1.5 rpg-badge text-xs sm:text-sm">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-60"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                    </span>
                    <span>{{ number_format($activePlayers) }} graczy online</span>
                </div>

                <button @click="trailerOpen = true" class="rpg-badge rpg-badge-link inline-flex items-center gap-2 px-4 py-1.5 text-xs sm:text-sm">
                    <i class="fa-solid fa-clapperboard"></i> Zwiastun
                </button>

                <a href="https://discord.gg/YJa68KK9hC" target="_blank" rel="noopener noreferrer" class="rpg-badge rpg-badge-link inline-flex items-center gap-2 px-4 py-1.5 text-xs sm:text-sm">
                    <i class="fa-brands fa-discord"></i> Discord
                </a>
            </div>
        </div>

        <livewire:auth.login-modal :hide-button="true" />

        @guest
            <svg class="absolute w-0 h-0 pointer-events-none" aria-hidden="true">
                <defs>
                    <clipPath id="clip-yin-left" clipPathUnits="objectBoundingBox">
                        <path d="M 0.5,0 A 0.25,0.25 0 0,1 0.5,0.5 A 0.25,0.25 0 0,0 0.5,1 A 0.5,0.5 0 0,1 0.5,0 Z" />
                    </clipPath>
                    <clipPath id="clip-yin-right" clipPathUnits="objectBoundingBox">
                        <path d="M 0.5,0 A 0.25,0.25 0 0,1 0.5,0.5 A 0.25,0.25 0 0,0 0.5,1 A 0.5,0.5 0 0,0 0.5,0 Z" />
                    </clipPath>
                </defs>
            </svg>

            {{-- Hero row: promo tile / medallion / promo tile --}}
            <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_1fr] gap-5 md:gap-6 items-center mb-10 relative z-30">

                {{-- Left promo tile: Trailer --}}
                <button @click="trailerOpen = true" class="rpg-promo-tile group text-left order-2 md:order-1">
                    <div class="rpg-promo-tile-bg" style="background-image: url('{{ asset('img/homepage-background.png') }}');"></div>
                    <div class="rpg-promo-tile-overlay"></div>
                    <div class="relative flex flex-col items-center justify-center h-full gap-2 p-5 text-center">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center rpg-crest text-lg group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-play"></i>
                        </div>
                        <span class="rpg-medallion-title text-sm">Zobacz Zwiastun</span>
                        <span class="rpg-medallion-sub">Poznaj świat gry</span>
                    </div>
                </button>

                {{-- Central Yin-Yang Auth Medallion --}}
                <div class="flex flex-col items-center justify-center order-1 md:order-2">
                    <div class="absolute w-[260px] h-[260px] sm:w-[320px] sm:h-[320px] rounded-full" style="background: radial-gradient(circle, rgba(220,177,91,.3) 0%, transparent 70%); filter: blur(18px);"></div>

                    <div class="relative w-64 h-64 sm:w-[300px] sm:h-[300px] rounded-full rpg-medallion-frame overflow-hidden bg-stone-950 pointer-events-auto">
                        <img src="{{ asset('img/yin_yang_auth.png') }}" alt="Berserk Rush Portal" class="absolute inset-0 w-full h-full object-cover select-none opacity-90">

                        <svg class="absolute inset-0 w-full h-full pointer-events-none z-10 opacity-30" viewBox="0 0 100 100">
                            <path d="M 50,0 A 25,25 0 0,1 50,50 A 25,25 0 0,0 50,100"
                                  fill="none"
                                  stroke="#e8cd8a"
                                  stroke-width="0.6" />
                        </svg>

                        {{-- Top-Left Half (Stwórz Konto) --}}
                        <a href="{{ route('register') }}"
                           class="absolute inset-0 group/left transition-colors duration-300 hover:bg-amber-100/10 z-20 flex flex-col items-start justify-start pt-12 sm:pt-16 pl-6 sm:pl-10 w-full h-full"
                           style="clip-path: url(#clip-yin-left); -webkit-clip-path: url(#clip-yin-left);">
                            <div class="transform transition-transform duration-300 group-hover/left:scale-105 rpg-medallion-label flex flex-col items-center text-center max-w-[120px] sm:max-w-[150px]">
                                <span class="rpg-medallion-title">Stwórz Konto</span>
                                <span class="rpg-medallion-sub">Dołącz teraz</span>
                            </div>
                        </a>

                        {{-- Bottom-Right Half (Zaloguj się) --}}
                        <div @click="$dispatch('open-login-modal')"
                             class="absolute inset-0 group/right cursor-pointer transition-colors duration-300 hover:bg-slate-100/10 z-20 flex flex-col items-end justify-end pb-12 sm:pb-16 pr-6 sm:pr-10 w-full h-full"
                             style="clip-path: url(#clip-yin-right); -webkit-clip-path: url(#clip-yin-right);">
                            <div class="transform transition-transform duration-300 group-hover/right:scale-105 rpg-medallion-label flex flex-col items-center text-center max-w-[120px] sm:max-w-[150px]">
                                <span class="rpg-medallion-title">Zaloguj się</span>
                                <span class="rpg-medallion-sub">Wróć do gry</span>
                            </div>
                        </div>
                    </div>

                    {{-- Direct Auth Buttons (mobile-first clarity, hidden once the medallion is comfortably tappable) --}}
                    <div class="mt-5 flex sm:hidden items-center justify-center gap-3 w-full max-w-xs px-2">
                        <a href="{{ route('register') }}" class="rpg-btn rpg-btn-gold flex-1">Rejestracja</a>
                        <button @click="$dispatch('open-login-modal')" class="rpg-btn rpg-btn-dark flex-1">Logowanie</button>
                    </div>
                </div>

                {{-- Right promo tile: Gallery or Discord --}}
                @if(isset($galleryImages) && $galleryImages->isNotEmpty())
                    <button @click="openGalleryGrid()" class="rpg-promo-tile group text-left order-3">
                        <div class="rpg-promo-tile-bg" style="background-image: url('{{ asset($galleryImages->first()->image_path) }}');"></div>
                        <div class="rpg-promo-tile-overlay"></div>
                        <div class="relative flex flex-col items-center justify-center h-full gap-2 p-5 text-center">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center rpg-crest text-lg group-hover:scale-110 transition-transform">
                                <i class="fa-solid fa-images"></i>
                            </div>
                            <span class="rpg-medallion-title text-sm">Galeria Gry</span>
                            <span class="rpg-medallion-sub">{{ $galleryImages->count() }} zrzutów ekranu</span>
                        </div>
                    </button>
                @else
                    <a href="https://discord.gg/YJa68KK9hC" target="_blank" rel="noopener noreferrer" class="rpg-promo-tile group text-left order-3">
                        <div class="rpg-promo-tile-bg" style="background-image: url('{{ asset('img/homepage-background.png') }}');"></div>
                        <div class="rpg-promo-tile-overlay"></div>
                        <div class="relative flex flex-col items-center justify-center h-full gap-2 p-5 text-center">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center rpg-crest text-lg group-hover:scale-110 transition-transform">
                                <i class="fa-brands fa-discord"></i>
                            </div>
                            <span class="rpg-medallion-title text-sm">Dołącz na Discord</span>
                            <span class="rpg-medallion-sub">Społeczność graczy</span>
                        </div>
                    </a>
                @endif
            </div>
        @endguest

        @auth
            @if (session()->has('message'))
                <div class="mb-6 p-4 bg-amber-900/40 border border-amber-600/60 rounded-lg text-amber-200 text-sm font-semibold flex items-center justify-between shadow-lg">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-circle-check text-amber-400 text-base"></i>
                        <span>{{ session('message') }}</span>
                    </div>
                </div>
            @endif

            {{-- Account bar --}}
            <div class="mb-6 rpg-panel">
                <div class="flex flex-col lg:flex-row items-center justify-between gap-4 px-5 py-4">
                    <div class="flex items-center gap-3 text-center sm:text-left">
                        <div class="w-11 h-11 rounded-full flex items-center justify-center text-lg rpg-avatar-badge">
                            <i class="fa-solid fa-user-shield"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold rpg-ink medieval-font">
                                {{ Auth::user()->name }}
                            </h3>
                            <p class="text-xs sm:text-sm rpg-ink-soft font-semibold">
                                Witaj z powrotem, wojowniku!
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button @click="trailerOpen = true" class="rpg-btn rpg-btn-dark">
                            <i class="fa-solid fa-clapperboard"></i> Zwiastun
                        </button>
                        <a href="https://discord.gg/YJa68KK9hC" target="_blank" rel="noopener noreferrer" class="rpg-btn rpg-btn-dark">
                            <i class="fa-brands fa-discord"></i> Discord
                        </a>
                    </div>

                    <div class="w-full sm:w-auto flex flex-col sm:flex-row items-center gap-2">
                        <livewire:auth.profile-management-modal />
                        <livewire:auth.logout-modal />
                    </div>
                </div>
            </div>

            {{-- Character roster --}}
            <div class="mb-8 rpg-panel">
                <div class="rpg-panel-header">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Twój Oddział Bohaterów</span>
                    <span class="ml-auto normal-case tracking-normal text-[11px] font-sans font-semibold opacity-80">Wybierz postać, aby ruszyć na szlak</span>
                </div>

                <div class="p-4 sm:p-5">
                    @php
                        $shouldHighlightNewCharacter = Auth::check() && Auth::user()->game_stage == 1 && collect($myCharacters)->filter()->count() == 0;
                        $shouldHighlightPlayButton = Auth::check() && Auth::user()->game_stage == 3 && collect($myCharacters)->filter()->count() > 0;
                    @endphp

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                        @foreach ($myCharacters as $index => $character)
                            <div class="rpg-slot relative {{ ($shouldHighlightNewCharacter && !$character) || ($shouldHighlightPlayButton && $character) ? 'rpg-slot-highlight' : '' }}">
                                @if ($character)
                                    <button type="button" 
                                            wire:click.prevent.stop="openDeleteModal('{{ $character->id }}')" 
                                            @click.stop
                                            class="absolute top-1.5 right-1.5 text-stone-600 hover:text-red-600 transition-colors p-1.5 z-30 rounded hover:bg-red-950/30"
                                            title="Usuń postać {{ $character->name }}">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                    <a href="{{ route('characters.play', $character) }}" @click.prevent="startTeleport('{{ route('characters.play', $character) }}')" class="block h-full p-3">
                                        <div class="flex items-center space-x-2.5 h-full">
                                            <div class="relative flex-shrink-0">
                                                <div class="w-10 h-10 rounded-full overflow-hidden flex items-center justify-center rpg-avatar-badge">
                                                    @if ($character->avatar && file_exists(public_path('img/avatars/' . $character->avatar . '.png')))
                                                        <img src="{{ asset('img/avatars/' . $character->avatar . '.png') }}" alt="Avatar {{ $character->avatar }}" class="w-full h-full object-cover">
                                                    @else
                                                        <i class="fa-solid fa-user-ninja text-lg"></i>
                                                    @endif
                                                </div>
                                                <span class="absolute -bottom-1 -right-1 rpg-level-pin">
                                                    {{ $character->level }}
                                                </span>
                                            </div>

                                            <div class="flex-1 min-w-0">
                                                <div class="font-bold rpg-ink text-xs sm:text-sm truncate medieval-font">
                                                    {{ $character->name }}
                                                </div>
                                                <div class="text-[10px] sm:text-[11px] rpg-ink-soft font-medium truncate">
                                                    Poziom {{ $character->level }}
                                                    @if ($character->attributes)
                                                        • {{ $character->getTotalAttributePoints() }} pkt
                                                    @endif
                                                </div>
                                                @php
                                                    $activeCharId = session('active_character');
                                                @endphp
                                                @if ($activeCharId && $activeCharId === $character->id)
                                                    <div class="mt-0.5 flex items-center gap-1 text-[10px] sm:text-[11px] rpg-ink font-bold">
                                                        <i class="fa-solid fa-circle-play text-[9px]"></i> <span>AKTYWNA (Wróć)</span>
                                                    </div>
                                                @elseif ($activeCharId)
                                                    <div class="mt-0.5 flex items-center gap-1 text-[10px] sm:text-[11px] rpg-ink-soft font-bold">
                                                        <i class="fa-solid fa-lock text-[8px]"></i> <span>ZABLOKOWANA</span>
                                                    </div>
                                                @else
                                                    <div class="mt-0.5 flex items-center gap-1 text-[10px] sm:text-[11px] text-emerald-800 font-bold">
                                                        <i class="fa-solid fa-play text-[8px]"></i> <span>GRAJ teraz</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </a>
                                @else
                                    @if ($canCreateCharacter ?? true)
                                        <a href="{{ route('characters.create') }}" class="block h-full min-h-[50px] p-2.5">
                                            <div class="text-center rpg-ink-soft transition-colors flex flex-col items-center justify-center h-full rpg-slot-empty rounded p-1.5">
                                                <i class="fa-solid fa-plus text-base mb-0.5"></i>
                                                <div class="text-[11px] font-bold medieval-font">Stwórz Postać</div>
                                            </div>
                                        </a>
                                    @else
                                        <div class="text-center rpg-ink-soft opacity-50 flex flex-col items-center justify-center h-full min-h-[50px] rpg-slot-empty rounded p-2.5">
                                            <i class="fa-solid fa-lock text-base mb-0.5"></i>
                                            <div class="text-[11px] font-bold medieval-font">Slot Zablokowany</div>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endauth

        {{-- Three-column grid: each column is an independent stack so a tall
             news feed in the middle column never pushes the side columns down --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

            {{-- Column 1 --}}
            <div class="contents lg:flex lg:flex-col lg:gap-6">
                {{-- Top 10 Bohaterów --}}
                <div class="order-2 lg:order-none mb-6 lg:mb-0 rpg-panel">
                    <div class="rpg-panel-header">
                        <i class="fa-solid fa-trophy"></i>
                        <span>Top 10 Bohaterów</span>
                    </div>
                    <div class="p-4">
                        @forelse ($topCharacters as $index => $character)
                            <div class="rpg-row">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="rpg-rank {{ $index < 3 ? 'rpg-rank-top' : '' }}">{{ $index + 1 }}</span>
                                    <span class="truncate {{ $index < 3 ? 'font-bold rpg-ink' : 'rpg-ink-soft' }}">{{ $character['name'] }}</span>
                                </div>
                                <span class="rpg-level-tag">{{ $character['level'] }}</span>
                            </div>
                        @empty
                            <div class="rpg-empty-state">
                                <i class="fa-solid fa-crown"></i>
                                <span>Bądź pierwszym bohaterem w rankingu!</span>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- O Grze --}}
                <div class="order-5 lg:order-none mb-6 lg:mb-0 rpg-panel">
                    <div class="rpg-panel-header">
                        <i class="fa-solid fa-gamepad"></i>
                        <span>O Grze</span>
                    </div>
                    <div class="p-4 space-y-2.5 text-sm">
                        <div class="flex items-center gap-2.5 rpg-ink-soft font-semibold">
                            <i class="fa-solid fa-dice-d20 w-4 text-center opacity-70"></i>
                            <span>Walki turowe</span>
                        </div>
                        <div class="flex items-center gap-2.5 rpg-ink-soft font-semibold">
                            <i class="fa-solid fa-wand-magic-sparkles w-4 text-center opacity-70"></i>
                            <span>Ulepszanie przedmiotów</span>
                        </div>
                        <div class="flex items-center gap-2.5 rpg-ink-soft font-semibold">
                            <i class="fa-solid fa-users-rays w-4 text-center opacity-70"></i>
                            <span>System gildii</span>
                        </div>
                        <div class="flex items-center gap-2.5 rpg-ink-soft font-semibold">
                            <i class="fa-solid fa-coins w-4 text-center opacity-70"></i>
                            <span>Ekonomia graczy</span>
                        </div>
                        <div class="flex items-center gap-2.5 rpg-ink-soft font-semibold">
                            <i class="fa-solid fa-hammer w-4 text-center opacity-70"></i>
                            <span>Crafting</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Column 2 --}}
            <div class="contents lg:flex lg:flex-col lg:gap-6">
                {{-- Ogłoszenia Królewskie --}}
                <div class="order-1 lg:order-none mb-6 lg:mb-0 rpg-panel rpg-panel-feature">
                    <div class="rpg-panel-header">
                        <i class="fa-solid fa-scroll"></i>
                        <span>Ogłoszenia Królewskie</span>
                    </div>
                    <div class="p-5 sm:p-6 space-y-5">
                        @foreach ($adminMessages as $message)
                            <article class="rpg-news-item">
                                <h3 class="text-lg font-bold rpg-ink mb-2 medieval-font">{{ $message['title'] }}</h3>
                                <div class="rpg-news-content mb-2">
                                    {!! $message['content'] !!}
                                </div>
                                <p class="text-xs rpg-ink-faint italic mt-2 border-t border-amber-900/20 pt-1.5">
                                    {{ date('j F Y', strtotime($message['date'])) }}
                                </p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Column 3 --}}
            <div class="contents lg:flex lg:flex-col lg:gap-6">
                {{-- Top 10 Gildii --}}
                <div class="order-3 lg:order-none mb-6 lg:mb-0 rpg-panel">
                    <div class="rpg-panel-header">
                        <i class="fa-solid fa-shield-cat"></i>
                        <span>Top 10 Gildii</span>
                    </div>
                    <div class="p-4">
                        @forelse ($topGuilds as $index => $guild)
                            <div class="rpg-row rpg-row-stacked">
                                <div class="flex items-center justify-between w-full">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="rpg-rank {{ $index < 3 ? 'rpg-rank-top' : '' }}">{{ $index + 1 }}</span>
                                        <span class="truncate {{ $index < 3 ? 'font-bold rpg-ink' : 'rpg-ink-soft' }}">{{ $guild['name'] }}</span>
                                    </div>
                                    <span class="rpg-level-tag">{{ $guild['avgLevel'] }}</span>
                                </div>
                                <div class="text-xs rpg-ink-faint ml-7 font-medium">{{ $guild['members'] }} członków</div>
                            </div>
                        @empty
                            <div class="rpg-empty-state">
                                <i class="fa-solid fa-flag"></i>
                                <span>Załóż pierwszą gildię i zapisz się w historii!</span>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Sklep Premium (auth only) --}}
                @auth
                    <div class="order-4 lg:order-none mb-6 lg:mb-0 rpg-panel rpg-panel-gold">
                        <div class="flex flex-col items-center text-center px-4 py-5">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-lg mb-2 rpg-avatar-badge">
                                <i class="fa-solid fa-gem"></i>
                            </div>
                            <h3 class="text-lg font-bold rpg-ink medieval-font">Sklep Premium</h3>
                            @if(Auth::user()->hasPremium())
                                <p class="text-[11px] font-bold rpg-ink rpg-badge-inline mt-1.5">
                                    <i class="fa-solid fa-crown mr-1"></i> Premium ({{ Auth::user()->premium_until->diffForHumans() }})
                                </p>
                            @else
                                <p class="text-xs rpg-ink-soft font-semibold mt-1">
                                    Zdobądź unikalne ulepszenia oraz Gemy!
                                </p>
                            @endif

                            <a href="{{ route('itemshop') }}" wire:navigate class="rpg-btn rpg-btn-gold w-full mt-3">
                                <i class="fa-solid fa-crown"></i> Przejdź do Sklepu
                            </a>
                        </div>
                    </div>
                @endauth

                {{-- Galeria Gry --}}
                @if(isset($galleryImages) && $galleryImages->isNotEmpty())
                    <div class="order-6 lg:order-none mb-6 lg:mb-0 rpg-panel">
                        <div class="rpg-panel-header">
                            <i class="fa-solid fa-images"></i>
                            <span>Galeria Gry</span>
                        </div>
                        <div class="p-4">
                            <div class="grid grid-cols-2 gap-2">
                                @foreach($galleryImages->take(4) as $image)
                                    <div class="relative group cursor-pointer rounded overflow-hidden aspect-video rpg-thumb" @click="openGallerySlider({{ $loop->index }})">
                                        <img src="{{ asset($image->image_path) }}" alt="{{ $image->title }}" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110">
                                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                            <span class="text-white text-xs font-bold">Powiększ</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-3 text-center">
                                <button @click="openGalleryGrid()" class="rpg-btn rpg-btn-dark w-full">
                                    <i class="fa-solid fa-images"></i>
                                    @if($galleryImages->count() > 4)
                                        Cała galeria ({{ $galleryImages->count() }})
                                    @else
                                        Galeria
                                    @endif
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <footer class="relative mt-12 py-8 border-t border-amber-900/40 text-center z-20 bg-stone-950/40 backdrop-blur-xs">
        <div class="container mx-auto px-4">
            <div class="flex flex-wrap items-center justify-center gap-4 sm:gap-8 text-xs sm:text-sm font-semibold medieval-font mb-3">
                <a href="{{ route('terms') }}" class="text-amber-500/80 hover:text-amber-400 transition-colors flex items-center gap-1.5">
                    <i class="fa-solid fa-scroll text-amber-600"></i> Regulamin Gry
                </a>
                <span class="text-amber-900/60 font-normal select-none">•</span>
                <a href="{{ route('privacy') }}" class="text-amber-500/80 hover:text-amber-400 transition-colors flex items-center gap-1.5">
                    <i class="fa-solid fa-user-shield text-amber-600"></i> Polityka Prywatności
                </a>
                <span class="text-amber-900/60 font-normal select-none">•</span>
                <a href="{{ route('chat-terms') }}" class="text-amber-500/80 hover:text-amber-400 transition-colors flex items-center gap-1.5">
                    <i class="fa-solid fa-comments text-amber-600"></i> Regulamin Czatu
                </a>
            </div>
            <p class="text-xs text-amber-900/80 font-medium">
                &copy; {{ date('Y') }} Berserk Rush. Wszelkie prawa zastrzeżone.
            </p>
        </div>
    </footer>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&display=swap');

        .medieval-font { font-family: 'Cinzel', serif; }

        .rpg-home {
            --ink: #2c2013;
            --ink-soft: #5a4630;
            --ink-faint: #7a6748;
            --parchment: #f1e4c4;
            --parchment-2: #e7d7ac;
            --wood: #201812;
            --wood-light: #3b2c1c;
            --gold: #b8862f;
            --gold-bright: #dcb15b;
            color: var(--parchment);
            background-color: #0d0a07;
        }

        .rpg-ink { color: var(--ink); }
        .rpg-ink-soft { color: var(--ink-soft); }
        .rpg-ink-faint { color: var(--ink-faint); }

        .rpg-rule {
            width: 120px;
            height: 2px;
            margin: 0 auto;
            background: linear-gradient(90deg, transparent, var(--gold) 50%, transparent);
        }

        .rpg-title {
            font-family: 'Cinzel', serif;
            font-weight: 700;
            font-size: clamp(2.25rem, 5vw, 3.25rem);
            color: var(--gold-bright);
            letter-spacing: .03em;
            text-shadow: 0 2px 10px rgba(0,0,0,.6);
            margin: .35rem 0;
        }

        .rpg-subtitle {
            color: #d8c9a8;
            font-size: .95rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            margin-bottom: .35rem;
        }

        .rpg-badge {
            background: rgba(20, 15, 10, .78);
            border: 1px solid rgba(184,134,47,.4);
            border-radius: 999px;
            color: #e8d5a8;
            font-weight: 600;
        }
        .rpg-badge-link { cursor: pointer; transition: background-color .2s ease, border-color .2s ease; }
        .rpg-badge-link:hover { background: rgba(184,134,47,.2); border-color: rgba(184,134,47,.7); }

        .rpg-crest {
            background: radial-gradient(circle, var(--wood-light), var(--wood));
            color: var(--gold-bright);
            border: 2px solid rgba(184,134,47,.6);
            box-shadow: 0 0 24px rgba(220,177,91,.35), inset 0 0 10px rgba(0,0,0,.5);
        }

        /* Ambient embers */
        .rpg-ember {
            position: absolute;
            bottom: -10px;
            width: 3px;
            height: 3px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(220,177,91,.95) 0%, rgba(184,134,47,.5) 60%, transparent 100%);
            animation: rpg-ember-rise linear infinite;
            opacity: 0;
        }
        .rpg-ember-1 { left: 8%;  width: 4px; height: 4px; animation-duration: 16s; animation-delay: 0s; }
        .rpg-ember-2 { left: 22%; width: 2px; height: 2px; animation-duration: 13s; animation-delay: 2s; }
        .rpg-ember-3 { left: 45%; width: 3px; height: 3px; animation-duration: 19s; animation-delay: 4s; }
        .rpg-ember-4 { left: 63%; width: 2px; height: 2px; animation-duration: 15s; animation-delay: 1s; }
        .rpg-ember-5 { left: 80%; width: 4px; height: 4px; animation-duration: 21s; animation-delay: 6s; }
        .rpg-ember-6 { left: 92%; width: 2px; height: 2px; animation-duration: 17s; animation-delay: 3s; }
        @keyframes rpg-ember-rise {
            0% { transform: translateY(0) translateX(0); opacity: 0; }
            8% { opacity: .8; }
            85% { opacity: .5; }
            100% { transform: translateY(-92vh) translateX(24px); opacity: 0; }
        }

        /* Panels */
        .rpg-panel {
            position: relative;
            background-color: var(--parchment);
            background-image:
                radial-gradient(circle, rgba(184,134,47,.9) 0%, transparent 70%),
                radial-gradient(circle, rgba(184,134,47,.9) 0%, transparent 70%),
                radial-gradient(circle, rgba(184,134,47,.9) 0%, transparent 70%),
                radial-gradient(circle, rgba(184,134,47,.9) 0%, transparent 70%),
                linear-gradient(180deg, rgba(255,255,255,.15), rgba(0,0,0,.02) 40%);
            background-size: 7px 7px, 7px 7px, 7px 7px, 7px 7px, auto;
            background-position: 6px 6px, calc(100% - 6px) 6px, 6px calc(100% - 6px), calc(100% - 6px) calc(100% - 6px), 0 0;
            background-repeat: no-repeat;
            border: 1px solid rgba(32,24,18,.4);
            border-radius: 4px;
            box-shadow: 0 8px 20px -10px rgba(0,0,0,.6), inset 0 0 0 1px rgba(220,177,91,.25);
            overflow: hidden;
        }

        .rpg-panel-header {
            display: flex;
            align-items: center;
            gap: .55rem;
            background: linear-gradient(180deg, var(--wood-light), var(--wood));
            color: var(--gold-bright);
            padding: .6rem 1rem;
            font-family: 'Cinzel', serif;
            font-size: .74rem;
            letter-spacing: .1em;
            text-transform: uppercase;
            border-bottom: 2px solid rgba(184,134,47,.45);
        }

        .rpg-panel-gold {
            border-color: rgba(184,134,47,.65);
            box-shadow: 0 8px 24px -10px rgba(184,134,47,.35), 0 8px 20px -10px rgba(0,0,0,.6);
        }

        .rpg-panel-feature .rpg-panel-header { font-size: .85rem; padding: .8rem 1.1rem; }

        /* Rows / rankings */
        .rpg-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .4rem 0;
            border-bottom: 1px solid rgba(32,24,18,.1);
            font-size: .85rem;
        }
        .rpg-row:last-child { border-bottom: none; }
        .rpg-row-stacked { flex-direction: column; align-items: stretch; gap: .15rem; }

        .rpg-rank {
            width: 1.4rem;
            font-variant-numeric: tabular-nums;
            font-weight: 600;
            color: var(--ink-faint);
        }
        .rpg-rank-top { color: var(--gold); font-weight: 800; }

        .rpg-level-tag {
            font-size: .72rem;
            font-weight: 700;
            color: var(--wood);
            background: rgba(184,134,47,.22);
            border: 1px solid rgba(184,134,47,.4);
            border-radius: 3px;
            padding: .1rem .4rem;
        }

        .rpg-badge-inline {
            background: rgba(184,134,47,.18);
            border: 1px solid rgba(184,134,47,.4);
            border-radius: 3px;
            padding: .15rem .5rem;
            display: inline-block;
        }

        /* News */
        .rpg-news-item {
            border-left: 3px solid var(--gold);
            padding-left: 1rem;
            padding-bottom: 0.75rem;
            margin-bottom: 1.5rem;
        }
        .rpg-news-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .rpg-news-content {
            color: var(--ink-soft);
            font-size: 0.875rem;
            line-height: 1.6;
        }

        .rpg-news-content p {
            margin-bottom: 0.6rem;
        }
        .rpg-news-content p:last-child {
            margin-bottom: 0;
        }

        .rpg-news-content strong,
        .rpg-news-content b {
            color: var(--ink);
            font-weight: 700;
        }

        .rpg-news-content ul {
            list-style-type: disc;
            padding-left: 1.25rem;
            margin-top: 0.35rem;
            margin-bottom: 0.65rem;
        }

        .rpg-news-content ol {
            list-style-type: decimal;
            padding-left: 1.25rem;
            margin-top: 0.35rem;
            margin-bottom: 0.65rem;
        }

        .rpg-news-content li {
            margin-bottom: 0.3rem;
            line-height: 1.5;
        }

        .rpg-news-content h1,
        .rpg-news-content h2,
        .rpg-news-content h3,
        .rpg-news-content h4 {
            font-family: 'Cinzel', serif;
            font-weight: 700;
            color: var(--ink);
            margin-top: 0.85rem;
            margin-bottom: 0.35rem;
        }
        .rpg-news-content h1 { font-size: 1.15rem; }
        .rpg-news-content h2 { font-size: 1.05rem; }
        .rpg-news-content h3 { font-size: 0.95rem; }

        .rpg-news-content hr {
            border: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(184,134,47,0.4) 50%, transparent);
            margin: 0.75rem 0;
        }

        .rpg-news-content code {
            background: rgba(32,24,18,0.08);
            border: 1px solid rgba(184,134,47,0.3);
            border-radius: 3px;
            padding: 0.1rem 0.3rem;
            font-size: 0.825rem;
            color: var(--ink);
        }

        /* Buttons */
        .rpg-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            padding: .6rem .9rem;
            font-family: 'Cinzel', serif;
            font-size: .72rem;
            letter-spacing: .07em;
            text-transform: uppercase;
            border-radius: 3px;
            border: 1px solid rgba(0,0,0,.3);
            transition: filter .15s ease, transform .15s ease;
        }
        .rpg-btn:active { transform: scale(.97); }
        .rpg-btn-gold { background: linear-gradient(180deg, var(--gold-bright), var(--gold)); color: var(--wood); }
        .rpg-btn-gold:hover { filter: brightness(1.08); }
        .rpg-btn-dark { background: var(--wood); color: var(--gold-bright); border-color: rgba(184,134,47,.4); }
        .rpg-btn-dark:hover { filter: brightness(1.3); }

        /* Character slots */
        .rpg-slot {
            position: relative;
            background: rgba(255,255,255,.35);
            border: 1px solid rgba(32,24,18,.25);
            border-radius: 4px;
            transition: background-color .2s ease, border-color .2s ease;
        }
        .rpg-slot:hover { background: rgba(255,255,255,.55); }
        .rpg-slot-empty { border: 1px dashed rgba(32,24,18,.35); }
        .rpg-slot-highlight {
            border-color: var(--gold);
            box-shadow: 0 0 0 2px rgba(184,134,47,.35);
            animation: rpg-slot-pulse 1.8s ease-in-out infinite;
        }
        @keyframes rpg-slot-pulse {
            0%, 100% { box-shadow: 0 0 0 2px rgba(184,134,47,.25); }
            50% { box-shadow: 0 0 0 4px rgba(184,134,47,.5); }
        }

        .rpg-avatar-badge {
            background: linear-gradient(180deg, var(--wood-light), var(--wood));
            color: var(--gold-bright);
            border: 1px solid rgba(184,134,47,.4);
        }

        .rpg-level-pin {
            background: var(--wood);
            color: var(--gold-bright);
            font-size: 9px;
            font-weight: 700;
            padding: 1px 4px;
            border-radius: 999px;
            border: 1px solid rgba(184,134,47,.5);
        }

        .rpg-thumb { border: 1px solid rgba(32,24,18,.3); }

        /* Yin-Yang medallion */
        .rpg-medallion-frame {
            border: 3px solid rgba(184,134,47,.7);
            box-shadow: 0 10px 40px -12px rgba(0,0,0,.7);
        }
        .rpg-medallion-label {
            background: rgba(13,10,7,.82);
            border: 1px solid rgba(184,134,47,.5);
            border-radius: 6px;
            padding: .5rem .65rem;
        }
        .rpg-medallion-title {
            font-family: 'Cinzel', serif;
            font-weight: 700;
            font-size: .78rem;
            color: #f0e0b8;
            letter-spacing: .04em;
            white-space: nowrap;
        }
        .rpg-medallion-sub {
            font-size: .62rem;
            color: #c7b285;
            text-transform: uppercase;
            letter-spacing: .1em;
            margin-top: .1rem;
        }

        /* Promo tiles flanking the auth medallion */
        .rpg-promo-tile {
            position: relative;
            display: block;
            width: 100%;
            max-width: 260px;
            height: 160px;
            margin: 0 auto;
            border-radius: 6px;
            border: 2px solid rgba(184,134,47,.55);
            overflow: hidden;
            box-shadow: 0 10px 30px -14px rgba(0,0,0,.75);
            transition: border-color .2s ease, transform .2s ease;
        }
        .rpg-promo-tile:hover { border-color: rgba(220,177,91,.9); transform: translateY(-2px); }
        .rpg-promo-tile-bg {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            filter: saturate(1.05);
            transition: transform .4s ease;
        }
        .rpg-promo-tile:hover .rpg-promo-tile-bg { transform: scale(1.06); }
        .rpg-promo-tile-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(13,10,7,.35), rgba(13,10,7,.85));
        }

        /* Empty-state filler for rankings */
        .rpg-empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .5rem;
            padding: 1.5rem .5rem;
            text-align: center;
            color: var(--ink-faint);
            font-size: .8rem;
            font-style: italic;
        }
        .rpg-empty-state i { font-size: 1.4rem; color: var(--gold); opacity: .7; }
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

        {{-- Character Deletion Modal --}}
        @if ($showDeleteModal)
            <div class="fixed inset-0 z-[120] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" wire:click="closeDeleteModal"></div>

                <div class="relative w-full max-w-md bg-stone-900 border-2 border-red-800 rounded-lg shadow-[0_0_40px_rgba(220,38,38,0.3)] p-6 text-stone-100 z-10"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100">
                    <div class="flex items-center justify-between border-b border-red-900/60 pb-3 mb-4">
                        <div class="flex items-center gap-2.5 text-red-500 font-bold text-lg medieval-font">
                            <i class="fa-solid fa-skull-crossbones text-xl"></i>
                            <span>Usuwanie Postaci</span>
                        </div>
                        <button wire:click="closeDeleteModal" class="text-stone-400 hover:text-white transition-colors">
                            <i class="fa-solid fa-xmark text-xl"></i>
                        </button>
                    </div>

                    <div class="mb-4 bg-red-950/50 border border-red-900/60 p-3 rounded text-xs text-red-200 leading-relaxed">
                        ⚠️ <strong class="text-red-400 font-semibold">UWAGA!</strong> Zamierzasz bezpowrotnie usunąć postać <strong class="text-white select-all">{{ $characterToDeleteName }}</strong>. Wszystkie jej statystyki, ekwipunek i chowańce zostaną utracone.
                    </div>

                    @if ($deleteErrorMessage)
                        <div class="mb-4 bg-red-900/70 text-red-100 p-2.5 rounded text-xs border border-red-600 font-semibold flex items-center gap-2">
                            <i class="fa-solid fa-triangle-exclamation text-sm text-red-300 flex-shrink-0"></i>
                            <span>{{ $deleteErrorMessage }}</span>
                        </div>
                    @endif

                    <form wire:submit.prevent="confirmDeleteCharacter" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-stone-300 mb-1">
                                Wpisz dokładnie nazwę postaci (<span class="text-amber-400 font-mono select-all">{{ $characterToDeleteName }}</span>):
                            </label>
                            <input type="text" 
                                   wire:model.defer="deleteCharacterNameInput" 
                                   placeholder="{{ $characterToDeleteName }}" 
                                   class="w-full bg-stone-950 border border-stone-700 rounded px-3 py-2 text-sm text-stone-100 focus:outline-none focus:border-red-500 shadow-inner"
                                   required>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-stone-300 mb-1">
                                Wpisz kod usunięcia postaci (min. 7 znaków):
                            </label>
                            <input type="text" 
                                   wire:model.defer="deleteCodeInput" 
                                   placeholder="np. TajneKod123" 
                                   minlength="7"
                                   class="w-full bg-stone-950 border border-stone-700 rounded px-3 py-2 text-sm text-stone-100 focus:outline-none focus:border-red-500 shadow-inner font-mono"
                                   required>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-stone-800">
                            <button type="button" wire:click="closeDeleteModal" class="px-4 py-2 bg-stone-800 hover:bg-stone-700 text-stone-300 text-xs font-bold rounded transition-colors">
                                Anuluj
                            </button>
                            <button type="submit" class="px-4 py-2 bg-red-700 hover:bg-red-600 text-white text-xs font-bold rounded transition-colors flex items-center gap-1.5 shadow-lg">
                                <i class="fa-solid fa-trash-can"></i>
                                <span>Usuń Postać</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endauth
</div>
