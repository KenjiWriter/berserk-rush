<div class="min-h-screen bg-stone-950 text-amber-100 relative overflow-hidden selection:bg-amber-500 selection:text-stone-950" style="font-family: 'Cinzel', serif;">
    {{-- Dynamic Background with Overlay --}}
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-45 pointer-events-none filter brightness-90 contrast-105"
        style="background-image: url('{{ asset('img/itemshop-bg.png') }}');">
    </div>

    {{-- Dark Vignette & Atmospheric Gradients --}}
    <div class="absolute inset-0 bg-gradient-to-b from-stone-950/85 via-stone-950/70 to-stone-950/95 pointer-events-none"></div>
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-amber-500/10 via-transparent to-stone-950/80 pointer-events-none"></div>

    {{-- Glowing Orbs Animations --}}
    <div class="absolute inset-0 pointer-events-none overflow-hidden">
        <div class="glow-orb glow-orb-1"></div>
        <div class="glow-orb glow-orb-2"></div>
        <div class="glow-orb glow-orb-3"></div>
    </div>

    {{-- Subtle SVG Pattern --}}
    <div class="absolute inset-0 opacity-5 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23d97706" fill-opacity="0.4"%3E%3Cpath d="M30 30c0-16.569 13.431-30 30-30v60c-16.569 0-30-13.431-30-30z" /%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] pointer-events-none"></div>

    <div class="relative max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 z-10">

        {{-- Navigation & Top Bar --}}
        <div class="flex items-center justify-between mb-8">
            <a href="{{ route('homepage') }}" wire:navigate class="group inline-flex items-center gap-2.5 px-4 py-2.5 rounded-xl bg-stone-900/90 border border-amber-700/50 text-amber-300 font-bold hover:bg-amber-950 hover:border-amber-400 hover:text-amber-100 transition-all duration-200 shadow-lg shadow-black/40">
                <i class="fa-solid fa-arrow-left text-amber-400 group-hover:-translate-x-1 transition-transform"></i>
                <span class="text-sm uppercase tracking-wider">Ekran Główny</span>
            </a>

            <div class="hidden sm:flex items-center gap-2 text-xs text-amber-400/80 font-mono bg-stone-900/60 border border-stone-800 px-3 py-1.5 rounded-lg">
                <i class="fa-solid fa-shield-halved text-amber-500"></i>
                <span>Bezpieczne Płatności SSL • BLIK & Karta</span>
            </div>
        </div>

        {{-- Hero Header --}}
        <div class="text-center mb-10 relative">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs font-semibold tracking-widest uppercase mb-4 shadow-[0_0_15px_rgba(245,158,11,0.2)]">
                <i class="fa-solid fa-gem text-amber-400 animate-pulse"></i>
                <span>Oficjalny Sklep Berserk Rush</span>
            </div>

            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-transparent bg-clip-text bg-gradient-to-r from-amber-100 via-amber-300 to-yellow-500 drop-shadow-[0_4px_12px_rgba(0,0,0,0.8)] tracking-tight mb-3">
                Sklep Premium
            </h1>
            <p class="text-stone-400 text-sm sm:text-base max-w-2xl mx-auto font-sans">
                Zdobądź Gemy, Aktywuj Status VIP oraz Wyposaż Swoje Postacie w Magiczne Zwoje i Awatary.
            </p>
        </div>

        {{-- User Dashboard Bar --}}
        <div class="bg-gradient-to-r from-stone-900/90 via-amber-950/40 to-stone-900/90 border border-amber-600/40 rounded-2xl p-5 mb-8 shadow-2xl backdrop-blur-md grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 items-center">

            {{-- Gems Balance --}}
            <div class="flex items-center gap-4 border-b sm:border-b-0 sm:border-r border-amber-800/30 pb-4 sm:pb-0 pr-0 sm:pr-4">
                <div class="w-13 h-13 rounded-2xl bg-gradient-to-br from-amber-500 to-yellow-600 p-0.5 shadow-[0_0_20px_rgba(245,158,11,0.4)] shrink-0">
                    <div class="w-full h-full bg-stone-950 rounded-[14px] flex items-center justify-center text-2xl">
                        💎
                    </div>
                </div>
                <div>
                    <p class="text-amber-500/80 text-[11px] font-bold uppercase tracking-widest">Twoje Gemy</p>
                    <p class="text-2xl font-black text-amber-200 tracking-wide">{{ \App\Support\NumberHelper::formatShort($user->gems) }} <span class="text-xs text-amber-500 font-medium">💎</span></p>
                </div>
            </div>

            {{-- VIP Status --}}
            <div class="flex items-center gap-4 border-b lg:border-b-0 lg:border-r border-amber-800/30 pb-4 lg:pb-0 pr-0 lg:pr-4">
                <div class="w-13 h-13 rounded-2xl bg-gradient-to-br {{ $user->hasPremium() ? 'from-yellow-400 to-amber-600 shadow-[0_0_20px_rgba(250,204,21,0.5)]' : 'from-stone-700 to-stone-900' }} p-0.5 shrink-0">
                    <div class="w-full h-full bg-stone-950 rounded-[14px] flex items-center justify-center text-2xl">
                        👑
                    </div>
                </div>
                <div>
                    <p class="text-amber-500/80 text-[11px] font-bold uppercase tracking-widest">Status Konto VIP</p>
                    @if($user->hasPremium())
                        <p class="text-sm font-bold text-yellow-400 flex items-center gap-1.5">
                            <span class="animate-pulse">✨</span>
                            Do {{ $user->premium_until->format('d.m.Y H:i') }}
                        </p>
                    @else
                        <p class="text-sm font-bold text-stone-400">Konto Standardowe</p>
                    @endif
                </div>
            </div>

            {{-- Active Character & Quick Action --}}
            <div class="flex items-center justify-between gap-4">
                @php
                    $activeCharId = session('active_character');
                    $activeChar = $activeCharId ? $user->characters()->where('id', $activeCharId)->first() : $user->characters()->first();
                @endphp
                <div class="truncate">
                    <p class="text-amber-500/80 text-[11px] font-bold uppercase tracking-widest">Odbiorca Zwojów</p>
                    <p class="text-sm font-bold text-amber-200 truncate flex items-center gap-1.5">
                        <i class="fa-solid fa-user-shield text-amber-400"></i>
                        {{ $activeChar ? $activeChar->name : 'Brak postaci' }}
                    </p>
                </div>

                <button type="button" data-shop-nav="sekcja-gemy" class="shop-nav-jump px-3.5 py-2 rounded-xl bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-stone-950 text-xs font-black uppercase tracking-wider shadow-lg hover:shadow-amber-500/30 transition-all shrink-0">
                    + Doładuj
                </button>
            </div>
        </div>

        {{-- Sticky Section Navigation --}}
        <div class="sticky top-0 z-20 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-3 bg-stone-950/90 backdrop-blur-md border-b border-amber-900/40">
            <div class="flex justify-start sm:justify-center overflow-x-auto gap-2 sm:gap-4 no-scrollbar">

                <button type="button" data-shop-nav="sekcja-reflinki" class="shop-nav-jump shop-nav-btn px-5 py-3 rounded-xl font-bold text-sm sm:text-base uppercase tracking-wider transition-all duration-200 flex items-center gap-2.5 shrink-0 whitespace-nowrap text-stone-400 hover:text-amber-300 hover:bg-stone-900/60">
                    <span class="text-lg">🔗</span>
                    <span>Reflinki</span>
                </button>

                <button type="button" data-shop-nav="sekcja-gemy" class="shop-nav-jump shop-nav-btn px-5 py-3 rounded-xl font-bold text-sm sm:text-base uppercase tracking-wider transition-all duration-200 flex items-center gap-2.5 shrink-0 whitespace-nowrap text-stone-400 hover:text-amber-300 hover:bg-stone-900/60">
                    <span class="text-lg">💎</span>
                    <span>Pakiety Gemów</span>
                </button>

                <button type="button" data-shop-nav="sekcja-vip" class="shop-nav-jump shop-nav-btn px-5 py-3 rounded-xl font-bold text-sm sm:text-base uppercase tracking-wider transition-all duration-200 flex items-center gap-2.5 shrink-0 whitespace-nowrap text-stone-400 hover:text-amber-300 hover:bg-stone-900/60">
                    <span class="text-lg">👑</span>
                    <span>Konto VIP</span>
                </button>

                <button type="button" data-shop-nav="sekcja-zwoje" class="shop-nav-jump shop-nav-btn px-5 py-3 rounded-xl font-bold text-sm sm:text-base uppercase tracking-wider transition-all duration-200 flex items-center gap-2.5 shrink-0 whitespace-nowrap text-stone-400 hover:text-amber-300 hover:bg-stone-900/60">
                    <span class="text-lg">📜</span>
                    <span>Zwoje & Resety</span>
                </button>

                <button type="button" data-shop-nav="sekcja-awatary" class="shop-nav-jump shop-nav-btn px-5 py-3 rounded-xl font-bold text-sm sm:text-base uppercase tracking-wider transition-all duration-200 flex items-center gap-2.5 shrink-0 whitespace-nowrap text-stone-400 hover:text-amber-300 hover:bg-stone-900/60">
                    <span class="text-lg">🎭</span>
                    <span>Awatary</span>
                </button>

            </div>
        </div>

        {{-- Horizontal Scrollbox (custom, draggable) - sits directly under the nav --}}
        <div class="px-1 pt-4 pb-6">
            <div id="shop-carousel-track" class="relative h-3 rounded-full bg-stone-900/80 border border-amber-900/50 cursor-pointer">
                <div id="shop-carousel-thumb" class="absolute top-0 left-0 h-full rounded-full bg-gradient-to-r from-amber-600 to-yellow-500 shadow-[0_0_8px_rgba(245,158,11,0.4)] cursor-grab active:cursor-grabbing" style="width: 20%;"></div>
            </div>
        </div>

        {{-- Horizontal Section Carousel --}}
        <div id="shop-carousel" class="no-scrollbar flex items-stretch overflow-x-auto snap-x snap-mandatory -mx-4 sm:-mx-6 lg:-mx-8">

        {{-- SECTION: REFLINKI --}}
        <section id="sekcja-reflinki" data-shop-section class="shrink-0 w-full snap-start px-4 sm:px-6 lg:px-8 pb-16">
            <div class="text-center mb-8">
                <h2 class="text-2xl sm:text-3xl font-black text-amber-200 mb-2">Poleć Znajomych</h2>
                <p class="text-stone-400 text-sm max-w-xl mx-auto">Wyślij swój link znajomemu. Gdy założy konto (również przez Google/Facebook), obaj zyskujecie!</p>
            </div>

            <div class="max-w-4xl mx-auto space-y-6" x-data="{ copiedLink: false, copiedCode: false }">

                {{-- Link + Code card --}}
                <div class="bg-stone-900/90 border border-amber-800/40 rounded-2xl p-6 shadow-xl">
                    <label class="block text-xs font-bold text-amber-500/80 uppercase tracking-widest mb-2">Twój unikalny link polecający</label>
                    <div class="flex flex-col sm:flex-row gap-3 mb-5">
                        <input
                            type="text"
                            readonly
                            value="{{ $referralLink }}"
                            onclick="this.select()"
                            class="flex-1 px-4 py-3 bg-stone-950/70 border border-amber-800/40 rounded-xl text-amber-200 text-sm font-mono truncate focus:outline-none focus:ring-1 focus:ring-amber-500"
                        >
                        <button
                            type="button"
                            @click="navigator.clipboard.writeText('{{ $referralLink }}'); copiedLink = true; setTimeout(() => copiedLink = false, 2000)"
                            class="px-5 py-3 rounded-xl bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-stone-950 font-extrabold text-xs uppercase tracking-wider shadow-md hover:shadow-amber-500/30 transition-all flex items-center justify-center gap-2 shrink-0"
                        >
                            <i class="fa-solid" :class="copiedLink ? 'fa-check' : 'fa-copy'"></i>
                            <span x-text="copiedLink ? 'Skopiowano!' : 'Kopiuj Link'"></span>
                        </button>
                    </div>

                    <label class="block text-xs font-bold text-amber-500/80 uppercase tracking-widest mb-2">Lub Twój kod</label>
                    <div class="flex items-center gap-3">
                        <div class="px-5 py-2.5 bg-stone-950/70 border border-amber-800/40 rounded-xl text-amber-300 text-lg font-black tracking-[0.2em]">
                            {{ $referralCode }}
                        </div>
                        <button
                            type="button"
                            @click="navigator.clipboard.writeText('{{ $referralCode }}'); copiedCode = true; setTimeout(() => copiedCode = false, 2000)"
                            class="px-4 py-2.5 rounded-xl border-2 border-amber-600/80 hover:border-amber-400 text-amber-300 font-bold text-xs uppercase tracking-wider hover:bg-amber-600/20 transition-all flex items-center gap-2"
                        >
                            <i class="fa-solid" :class="copiedCode ? 'fa-check' : 'fa-copy'"></i>
                            <span x-text="copiedCode ? 'Skopiowano!' : 'Kopiuj Kod'"></span>
                        </button>
                    </div>
                </div>

                {{-- Rewards explanation --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="bg-stone-900/80 border border-amber-800/40 rounded-2xl p-5 shadow-lg">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-11 h-11 rounded-xl bg-amber-950 flex items-center justify-center text-xl border border-amber-500/40 shrink-0">🎁</div>
                            <h3 class="font-bold text-amber-200 text-sm">Twój znajomy dostaje</h3>
                        </div>
                        <p class="text-xs text-stone-300 leading-relaxed">Po założeniu konta z Twojego linku (email lub Google/Facebook) natychmiast otrzymuje <strong class="text-amber-300">3 dni Konta VIP</strong> oraz <strong class="text-amber-300">3 dni darmowego dostępu do Lustra</strong> na pierwszej utworzonej postaci.</p>
                    </div>

                    <div class="bg-stone-900/80 border border-amber-800/40 rounded-2xl p-5 shadow-lg">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-11 h-11 rounded-xl bg-amber-950 flex items-center justify-center text-xl border border-amber-500/40 shrink-0">💎</div>
                            <h3 class="font-bold text-amber-200 text-sm">Ty dostajesz</h3>
                        </div>
                        <p class="text-xs text-stone-300 leading-relaxed"><strong class="text-amber-300">200 Gemów na pocztę</strong> za każdego poleconego znajomego, którego konto osiągnie 30 poziom na dowolnej postaci. Nagroda jest jednorazowa na konto znajomego.</p>
                    </div>
                </div>

                {{-- Stats --}}
                <div class="grid grid-cols-2 gap-5">
                    <div class="bg-stone-900/60 border border-stone-800 rounded-2xl p-5 text-center">
                        <p class="text-3xl font-black text-amber-300">{{ $referredCount }}</p>
                        <p class="text-[11px] font-bold text-stone-400 uppercase tracking-widest mt-1">Poleconych Znajomych</p>
                    </div>
                    <div class="bg-stone-900/60 border border-stone-800 rounded-2xl p-5 text-center">
                        <p class="text-3xl font-black text-emerald-400">{{ $rewardedCount }}</p>
                        <p class="text-[11px] font-bold text-stone-400 uppercase tracking-widest mt-1">Odebranych Nagród (30 lvl)</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- SECTION: GEMS PACKAGES --}}
        <section id="sekcja-gemy" data-shop-section class="shrink-0 w-full snap-start px-4 sm:px-6 lg:px-8 pb-16">
            @if(request()->has('success'))
                <div class="mb-8 p-4 bg-emerald-950/80 border border-emerald-500/60 rounded-xl text-emerald-200 text-center backdrop-blur-md shadow-lg flex items-center justify-center gap-3">
                    <i class="fa-solid fa-circle-check text-emerald-400 text-xl"></i>
                    <span>Płatność zakończona sukcesem! Gemy zostały pomyślnie dopisane do Twojego konta.</span>
                </div>
            @endif
            @if(request()->has('cancel'))
                <div class="mb-8 p-4 bg-rose-950/80 border border-rose-500/60 rounded-xl text-rose-200 text-center backdrop-blur-md shadow-lg flex items-center justify-center gap-3">
                    <i class="fa-solid fa-circle-xmark text-rose-400 text-xl"></i>
                    <span>Transakcja została anulowana. Żadne środki nie zostały pobrane.</span>
                </div>
            @endif

            <div class="text-center mb-8">
                <h2 class="text-2xl sm:text-3xl font-black text-amber-200 mb-2">Pakiety Gemów</h2>
                <p class="text-stone-400 text-sm">Doładuj konto premium walutą i odblokuj pełnię możliwości sklepu</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @forelse($packages as $index => $pkg)
                    @php
                        $isFeatured = $index === 1 || $index === count($packages) - 1;
                    @endphp
                    <div class="group relative bg-gradient-to-b from-stone-900/90 via-stone-900/70 to-stone-950 border {{ $isFeatured ? 'border-amber-500 shadow-[0_0_25px_rgba(245,158,11,0.25)]' : 'border-amber-800/40 hover:border-amber-500/60' }} rounded-2xl p-6 text-center shadow-xl hover:shadow-[0_0_30px_rgba(245,158,11,0.35)] transition-all duration-300 transform hover:-translate-y-1.5 flex flex-col justify-between overflow-hidden">

                        @if($isFeatured)
                            <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-gradient-to-r from-yellow-500 to-amber-600 text-stone-950 font-black text-[10px] uppercase tracking-widest px-3.5 py-1 rounded-full shadow-md z-10">
                                NAJPOPULARNIEJSZY
                            </div>
                        @endif

                        {{-- Ambient Hover Glow --}}
                        <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-40 h-40 bg-amber-500/10 rounded-full blur-3xl group-hover:bg-amber-500/25 transition-all pointer-events-none"></div>

                        <div class="pt-2">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-amber-950/80 border border-amber-500/40 flex items-center justify-center text-3xl shadow-inner group-hover:scale-110 transition-transform duration-300">
                                💎
                            </div>

                            <h3 class="text-xl font-bold text-amber-200 mb-1 group-hover:text-yellow-300 transition-colors">{{ $pkg->name }}</h3>
                            <div class="text-2xl font-black text-amber-400 tracking-wide mb-6">
                                +{{ number_format($pkg->gem_amount) }} <span class="text-sm font-semibold">Gemów</span>
                            </div>
                        </div>

                        <div>
                            <div class="text-3xl font-black text-white mb-5 tracking-tight">
                                {{ number_format($pkg->price_in_cents / 100, 2) }} <span class="text-sm text-amber-500 font-bold uppercase">{{ $pkg->currency }}</span>
                            </div>

                            <button
                                wire:click="buyGems('{{ $pkg->id }}')"
                                class="w-full py-3 rounded-xl bg-gradient-to-r from-amber-600 via-yellow-500 to-amber-600 hover:from-amber-500 hover:via-yellow-400 hover:to-amber-500 text-stone-950 font-black uppercase tracking-wider shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 transition-all duration-200 active:scale-95 flex items-center justify-center gap-2"
                            >
                                <i class="fa-solid fa-cart-shopping text-sm"></i>
                                <span>Kup Teraz</span>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center text-stone-500 italic py-12 bg-stone-900/40 rounded-2xl border border-stone-800">
                        Obecnie brak dostępnych pakietów w sklepie. Zapraszamy wkrótce!
                    </div>
                @endforelse
            </div>
        </section>

        {{-- SECTION: KONTO VIP --}}
        <section id="sekcja-vip" data-shop-section class="shrink-0 w-full snap-start px-4 sm:px-6 lg:px-8 pb-16">
            <div class="mb-12 max-w-5xl mx-auto">
                <div class="text-center mb-8">
                    <h2 class="text-2xl sm:text-3xl font-black text-amber-200 mb-2">Przywileje Konta VIP</h2>
                    <p class="text-stone-400 text-sm">Aktywuj konto VIP za pomocą Gemów i przyspiesz swój rozwój w królestwie</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="bg-stone-900/80 border border-amber-800/40 p-4 rounded-xl flex items-center gap-4 shadow-lg hover:border-amber-500/60 transition-all">
                        <div class="w-12 h-12 rounded-xl bg-amber-950 flex items-center justify-center text-2xl border border-amber-500/40 shrink-0">
                            ⚔️
                        </div>
                        <div>
                            <h4 class="font-bold text-amber-200 text-sm">+20% Więcej Złota</h4>
                            <p class="text-xs text-stone-400">Zwiększa nagrody ze wszystkich potworów</p>
                        </div>
                    </div>

                    <div class="bg-stone-900/80 border border-amber-800/40 p-4 rounded-xl flex items-center gap-4 shadow-lg hover:border-amber-500/60 transition-all">
                        <div class="w-12 h-12 rounded-xl bg-amber-950 flex items-center justify-center text-2xl border border-amber-500/40 shrink-0">
                            🌟
                        </div>
                        <div>
                            <h4 class="font-bold text-amber-200 text-sm">+20% Szybszy Exp</h4>
                            <p class="text-xs text-stone-400">Przyspiesza awansowanie na wyższe poziomy</p>
                        </div>
                    </div>

                    <div class="bg-stone-900/80 border border-amber-800/40 p-4 rounded-xl flex items-center gap-4 shadow-lg hover:border-amber-500/60 transition-all">
                        <div class="w-12 h-12 rounded-xl bg-amber-950 flex items-center justify-center text-2xl border border-amber-500/40 shrink-0">
                            🎒
                        </div>
                        <div>
                            <h4 class="font-bold text-amber-200 text-sm">Większy Plecak (64 Sloty)</h4>
                            <p class="text-xs text-stone-400">Podwojona pojemność ekwipunku (+32 miejsca)</p>
                        </div>
                    </div>

                    <div class="bg-stone-900/80 border border-amber-800/40 p-4 rounded-xl flex items-center gap-4 shadow-lg hover:border-amber-500/60 transition-all">
                        <div class="w-12 h-12 rounded-xl bg-amber-950 flex items-center justify-center text-2xl border border-amber-500/40 shrink-0">
                            🪞
                        </div>
                        <div>
                            <h4 class="font-bold text-amber-200 text-sm">Dłuższy Czas w Lustrze</h4>
                            <p class="text-xs text-stone-400">Możliwość sesji polowania w Lustrze do 10 godzin</p>
                        </div>
                    </div>

                    <div class="bg-stone-900/80 border border-amber-800/40 p-4 rounded-xl flex items-center gap-4 shadow-lg hover:border-amber-500/60 transition-all">
                        <div class="w-12 h-12 rounded-xl bg-amber-950 flex items-center justify-center text-2xl border border-amber-500/40 shrink-0">
                            🔮
                        </div>
                        <div>
                            <h4 class="font-bold text-amber-200 text-sm">Szybsze Losowanie u Wiedźmy</h4>
                            <p class="text-xs text-stone-400">Błyskawiczne przelosowywanie bonusów w Switchbocie</p>
                        </div>
                    </div>

                    <div class="bg-stone-900/80 border border-amber-800/40 p-4 rounded-xl flex items-center justify-center gap-4 shadow-lg hover:border-amber-500/60 transition-all sm:col-span-2 lg:col-span-3">
                        <div class="w-12 h-12 rounded-xl bg-amber-950 flex items-center justify-center text-2xl border border-amber-500/40 shrink-0">
                            👑
                        </div>
                        <div>
                            <h4 class="font-bold text-yellow-300 text-sm">Złoty Nick na Czacie</h4>
                            <p class="text-xs text-stone-400">Wyróżniająca animacja i ranga na czacie</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                {{-- 3 Days VIP --}}
                <div class="bg-gradient-to-b from-stone-900/90 to-stone-950 border border-amber-800/40 rounded-2xl p-7 text-center shadow-xl hover:border-amber-500/60 transition-all flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-bold text-amber-500/70 uppercase tracking-widest">Pakiet Próbny</span>
                        <h3 class="text-2xl font-bold text-amber-200 mb-1 mt-1">3 Dni VIP</h3>
                        <p class="text-stone-400 text-xs mb-6">Idealne przetestowanie przewagi w walce</p>
                        <div class="text-4xl mb-6">🥉</div>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-amber-300 flex items-center justify-center gap-1.5 mb-6">
                            100 <span class="text-xl">💎</span>
                        </div>
                        <button
                            wire:click="buyPremium(3, 100)"
                            class="w-full py-3 rounded-xl border-2 border-amber-600/80 hover:border-amber-400 text-amber-300 font-bold tracking-wider hover:bg-amber-600/20 transition-all uppercase text-xs"
                        >
                            {{ auth()->user()->hasPremium() ? 'Przedłuż Premium' : 'Aktywuj Premium' }}
                        </button>
                    </div>
                </div>

                {{-- 14 Days VIP --}}
                <div class="relative bg-gradient-to-b from-amber-950/70 via-stone-900 to-stone-950 border-2 border-yellow-500 rounded-2xl p-7 text-center shadow-[0_0_30px_rgba(250,204,21,0.25)] flex flex-col justify-between transform md:-translate-y-3 z-10">
                    <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-gradient-to-r from-yellow-500 to-amber-500 text-stone-950 font-black text-[10px] uppercase tracking-widest px-4 py-1 rounded-full shadow-lg">
                        NAJCHĘTNIEJ WYBIERANY
                    </div>
                    <div>
                        <span class="text-xs font-bold text-yellow-400/90 uppercase tracking-widest">Optymalny Wybór</span>
                        <h3 class="text-3xl font-black text-yellow-300 mb-1 mt-1">14 Dni VIP</h3>
                        <p class="text-amber-200/70 text-xs mb-6">Pełna dominacja przez dwa tygodnie</p>
                        <div class="text-5xl mb-6 drop-shadow-[0_0_15px_rgba(250,204,21,0.6)] animate-pulse">👑</div>
                    </div>
                    <div>
                        <div class="text-4xl font-black text-yellow-400 flex items-center justify-center gap-1.5 mb-6">
                            400 <span class="text-2xl">💎</span>
                        </div>
                        <button
                            wire:click="buyPremium(14, 400)"
                            class="w-full py-3.5 rounded-xl bg-gradient-to-r from-yellow-500 via-amber-500 to-yellow-500 hover:from-yellow-400 hover:to-amber-400 text-stone-950 font-black tracking-wider shadow-lg shadow-yellow-500/30 transition-all uppercase text-xs"
                        >
                            {{ auth()->user()->hasPremium() ? 'Przedłuż Premium' : 'Aktywuj Premium' }}
                        </button>
                    </div>
                </div>

                {{-- 30 Days VIP --}}
                <div class="bg-gradient-to-b from-stone-900/90 to-stone-950 border border-amber-800/40 rounded-2xl p-7 text-center shadow-xl hover:border-amber-500/60 transition-all flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-bold text-amber-500/70 uppercase tracking-widest">Maksymalny Pakiet</span>
                        <h3 class="text-2xl font-bold text-amber-200 mb-1 mt-1">30 Dni VIP</h3>
                        <p class="text-stone-400 text-xs mb-6">Najlepszy stosunek wartości do ceny</p>
                        <div class="text-4xl mb-6">🥇</div>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-amber-300 flex items-center justify-center gap-1.5 mb-6">
                            800 <span class="text-xl">💎</span>
                        </div>
                        <button
                            wire:click="buyPremium(30, 800)"
                            class="w-full py-3 rounded-xl border-2 border-amber-600/80 hover:border-amber-400 text-amber-300 font-bold tracking-wider hover:bg-amber-600/20 transition-all uppercase text-xs"
                        >
                            {{ auth()->user()->hasPremium() ? 'Przedłuż Premium' : 'Aktywuj Premium' }}
                        </button>
                    </div>
                </div>
            </div>
        </section>

        {{-- SECTION: ZWOJE I RESETY --}}
        <section id="sekcja-zwoje" data-shop-section class="shrink-0 w-full snap-start px-4 sm:px-6 lg:px-8 pb-16">
            <div class="max-w-5xl mx-auto">
                <div class="text-center mb-8">
                    <h2 class="text-2xl sm:text-3xl font-black text-amber-200 mb-2">Zwoje i Magiczne Przedmioty</h2>
                    <p class="text-stone-400 text-sm max-w-xl mx-auto mb-4">Zakupione Zwoje trafiają prosto do plecaka aktywnej postaci. Możesz ich użyć w dowolnym momencie.</p>

                    <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-xl bg-amber-950/60 border border-amber-500/40 text-amber-300 text-xs font-semibold shadow-md">
                        <i class="fa-solid fa-bag-shopping text-amber-400 text-base"></i>
                        <span>Lokalizacja Zwojów w grze: <strong>Profil &rarr; Plecak</strong></span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Zwój Resetu Umiejętności --}}
                    <div class="bg-stone-900/90 border border-amber-800/40 rounded-2xl p-6 shadow-xl hover:border-amber-500/60 transition-all flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-16 h-16 rounded-xl bg-stone-950 border-2 border-amber-500/60 flex items-center justify-center p-1.5 shadow-[0_0_15px_rgba(245,158,11,0.25)] shrink-0 overflow-hidden">
                                    <img
                                        src="{{ asset('assets/items/zwoj-resetu-umiejetnosci.png') }}"
                                        alt="Zwój Resetu Umiejętności"
                                        class="w-full h-full object-contain drop-shadow-md"
                                        onerror="this.onerror=null; this.src='{{ asset('assets/items/czysty-pergamin.png') }}';"
                                    />
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-amber-200">Zwój Resetu Umiejętności</h3>
                                    <span class="inline-block px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-500/40 text-[10px] font-bold uppercase tracking-wider mt-1">Przedmiot Zużywalny</span>
                                </div>
                            </div>
                            <p class="text-xs text-stone-300 leading-relaxed mb-6">Pozwala zresetować umiejętności bojowe aktywnej postaci i odzyskać wszystkie zainwestowane Punkty Umiejętności (Skill Points) do ponownego przydzielenia.</p>
                        </div>
                        <div class="flex items-center justify-between pt-4 border-t border-stone-800">
                            <div class="text-xl font-black text-amber-300 flex items-center gap-1">
                                50 <span class="text-sm">💎</span>
                            </div>
                            <button
                                wire:click="buyScroll('01k4jpx94j70x2vv10b835scr1', 50)"
                                class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-stone-950 font-extrabold text-xs uppercase tracking-wider shadow-md hover:shadow-amber-500/30 transition-all"
                            >
                                Kup Zwój
                            </button>
                        </div>
                    </div>

                    {{-- Zwój Resetu Atrybutów --}}
                    <div class="bg-stone-900/90 border border-amber-800/40 rounded-2xl p-6 shadow-xl hover:border-amber-500/60 transition-all flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-16 h-16 rounded-xl bg-stone-950 border-2 border-amber-500/60 flex items-center justify-center p-1.5 shadow-[0_0_15px_rgba(245,158,11,0.25)] shrink-0 overflow-hidden">
                                    <img
                                        src="{{ asset('assets/items/zwoj-resetu-atrybutow.png') }}"
                                        alt="Zwój Resetu Atrybutów"
                                        class="w-full h-full object-contain drop-shadow-md"
                                        onerror="this.onerror=null; this.src='{{ asset('assets/items/czysty-pergamin.png') }}';"
                                    />
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-amber-200">Zwój Resetu Atrybutów</h3>
                                    <span class="inline-block px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-500/40 text-[10px] font-bold uppercase tracking-wider mt-1">Przedmiot Zużywalny</span>
                                </div>
                            </div>
                            <p class="text-xs text-stone-300 leading-relaxed mb-6">Pozwala zresetować przydzielone atrybuty (Siła, Inteligencja, Witalność, Zręczność) aktywnej postaci i odzyskać całą pulę Punktów Postaci.</p>
                        </div>
                        <div class="flex items-center justify-between pt-4 border-t border-stone-800">
                            <div class="text-xl font-black text-amber-300 flex items-center gap-1">
                                50 <span class="text-sm">💎</span>
                            </div>
                            <button
                                wire:click="buyScroll('01k4jpx94j70x2vv10b835scr2', 50)"
                                class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-stone-950 font-extrabold text-xs uppercase tracking-wider shadow-md hover:shadow-amber-500/30 transition-all"
                            >
                                Kup Zwój
                            </button>
                        </div>
                    </div>

                    {{-- Zwój Pełnego Resetu --}}
                    <div class="bg-stone-900/90 border border-amber-800/40 rounded-2xl p-6 shadow-xl hover:border-amber-500/60 transition-all flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-16 h-16 rounded-xl bg-stone-950 border-2 border-amber-500/60 flex items-center justify-center p-1.5 shadow-[0_0_15px_rgba(245,158,11,0.25)] shrink-0 overflow-hidden">
                                    <img
                                        src="{{ asset('assets/items/zwoj-pelnego-resetu.png') }}"
                                        alt="Zwój Pełnego Resetu"
                                        class="w-full h-full object-contain drop-shadow-md"
                                        onerror="this.onerror=null; this.src='{{ asset('assets/items/czysty-pergamin.png') }}';"
                                    />
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-amber-200">Zwój Pełnego Resetu</h3>
                                    <span class="inline-block px-2 py-0.5 rounded bg-purple-500/20 text-purple-300 border border-purple-500/40 text-[10px] font-bold uppercase tracking-wider mt-1">Pakiet Zbiorczy</span>
                                </div>
                            </div>
                            <p class="text-xs text-stone-300 leading-relaxed mb-6">Pozwala zresetować zarówno przydzielone atrybuty, jak i umiejętności bojowe aktywnej postaci za jednym zleceniem.</p>
                        </div>
                        <div class="flex items-center justify-between pt-4 border-t border-stone-800">
                            <div class="text-xl font-black text-amber-300 flex items-center gap-1">
                                90 <span class="text-sm">💎</span>
                            </div>
                            <button
                                wire:click="buyScroll('01k4jpx94j70x2vv10b835scr3', 90)"
                                class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-stone-950 font-extrabold text-xs uppercase tracking-wider shadow-md hover:shadow-amber-500/30 transition-all"
                            >
                                Kup Zwój
                            </button>
                        </div>
                    </div>

                    {{-- Zwój Areny Walki --}}
                    <div class="bg-stone-900/90 border border-amber-800/40 rounded-2xl p-6 shadow-xl hover:border-amber-500/60 transition-all flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-16 h-16 rounded-xl bg-stone-950 border-2 border-amber-500/60 flex items-center justify-center p-1.5 shadow-[0_0_15px_rgba(245,158,11,0.25)] shrink-0 overflow-hidden">
                                    <img
                                        src="{{ asset('assets/items/zwoj-areny-walki.png') }}"
                                        alt="Zwój Areny Walki"
                                        class="w-full h-full object-contain drop-shadow-md"
                                        onerror="this.onerror=null; this.src='{{ asset('assets/items/czysty-pergamin.png') }}';"
                                    />
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-amber-200">Zwój Areny Walki</h3>
                                    <span class="inline-block px-2 py-0.5 rounded bg-blue-500/20 text-blue-300 border border-blue-500/40 text-[10px] font-bold uppercase tracking-wider mt-1">Odnawialny</span>
                                </div>
                            </div>
                            <p class="text-xs text-stone-300 leading-relaxed mb-6">Przywraca 1 wykorzystaną próbę walki na Arenie w danym dniu dla aktywnej postaci.</p>
                        </div>
                        <div class="flex items-center justify-between pt-4 border-t border-stone-800">
                            <div class="text-xl font-black text-amber-300 flex items-center gap-1">
                                30 <span class="text-sm">💎</span>
                            </div>
                            <button
                                wire:click="buyScroll('01k4jpx94j70x2vv10b835scr4', 30)"
                                class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-stone-950 font-extrabold text-xs uppercase tracking-wider shadow-md hover:shadow-amber-500/30 transition-all"
                            >
                                Kup Zwój
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- SECTION: AWATARY --}}
        <section id="sekcja-awatary" data-shop-section class="shrink-0 w-full snap-start px-4 sm:px-6 lg:px-8 pb-4">
            <div class="text-center mb-8">
                <h2 class="text-2xl sm:text-3xl font-black text-amber-200 mb-2">Awatary Premium</h2>
                <p class="text-stone-400 text-sm">Wyróżnij swoje konto unikalnym awatarem</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-5 max-w-5xl mx-auto">
                @foreach($premiumAvatars as $avatar)
                    @php
                        $isOwned = in_array($avatar, $user->unlocked_avatars ?? []);
                    @endphp
                    <div class="group relative bg-stone-900/90 border {{ $isOwned ? 'border-emerald-500/60 shadow-[0_0_15px_rgba(16,185,129,0.2)]' : 'border-amber-800/40 hover:border-amber-500/60 hover:shadow-[0_0_20px_rgba(245,158,11,0.2)]' }} rounded-2xl p-4 text-center transition-all duration-300 flex flex-col items-center justify-between">

                        <div class="w-full flex flex-col items-center">
                            <div class="w-24 h-24 rounded-full overflow-hidden border-2 {{ $isOwned ? 'border-emerald-400' : 'border-amber-500' }} mb-3 p-0.5 bg-stone-950 shadow-md group-hover:scale-105 transition-transform duration-200">
                                <img src="{{ asset('img/avatars/premium/' . $avatar . '.png') }}" alt="{{ $avatar }}" class="w-full h-full object-cover rounded-full">
                            </div>
                            <h4 class="text-amber-200 font-bold text-xs mb-4 capitalize truncate max-w-full">{{ str_replace('_', ' ', $avatar) }}</h4>
                        </div>

                        <div class="w-full">
                            @if($isOwned)
                                <div class="w-full py-2 bg-emerald-950/80 text-emerald-300 font-extrabold text-xs rounded-xl border border-emerald-500/50 flex items-center justify-center gap-1.5">
                                    <i class="fa-solid fa-check text-emerald-400"></i>
                                    <span>Posiadany</span>
                                </div>
                            @else
                                <button
                                    wire:click="buyAvatar('{{ $avatar }}')"
                                    class="w-full py-2 bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-stone-950 font-black text-xs uppercase tracking-wider rounded-xl shadow-md transition-all flex items-center justify-center gap-1"
                                >
                                    <span>150</span>
                                    <span class="text-xs">💎</span>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if(empty($premiumAvatars))
                <div class="text-center text-stone-500 italic py-12 bg-stone-900/40 rounded-2xl border border-stone-800">
                    Brak dostępnych awatarów premium.
                </div>
            @endif
        </section>

        </div>
    </div>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&display=swap');

        /* Custom Scrollbar for Tabs */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        #shop-carousel-thumb {
            transition: width 0.15s ease, left 0.15s ease;
            touch-action: none;
        }
        #shop-carousel-track:active #shop-carousel-thumb,
        #shop-carousel-thumb:active {
            transition: none;
        }

        html {
            scroll-behavior: smooth;
        }

        .shop-nav-btn.is-active-shop-tab {
            background-color: rgba(69, 26, 3, 0.8);
            color: #fde68a;
            border-top: 2px solid rgba(245, 158, 11, 0.6);
            border-left: 1px solid rgba(245, 158, 11, 0.6);
            border-right: 1px solid rgba(245, 158, 11, 0.6);
            box-shadow: 0 -5px 15px rgba(245, 158, 11, 0.15);
        }

        /* Ambient Orbs */
        .glow-orb {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }

        .glow-orb-1 {
            width: 280px;
            height: 280px;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.12) 0%, transparent 70%);
            top: 15%;
            right: 5%;
            animation: glow-pulse 8s infinite ease-in-out;
        }

        .glow-orb-2 {
            width: 220px;
            height: 220px;
            background: radial-gradient(circle, rgba(217, 119, 6, 0.1) 0%, transparent 70%);
            bottom: 25%;
            left: 5%;
            animation: glow-pulse 6s infinite ease-in-out reverse;
        }

        .glow-orb-3 {
            width: 180px;
            height: 180px;
            background: radial-gradient(circle, rgba(250, 204, 21, 0.15) 0%, transparent 70%);
            top: 50%;
            left: 45%;
            animation: glow-pulse 10s infinite ease-in-out;
        }

        @keyframes glow-pulse {
            0%, 100% {
                opacity: 0.3;
                transform: scale(1);
            }
            50% {
                opacity: 0.7;
                transform: scale(1.15);
            }
        }
    </style>

    <script>
        function initItemShopScrollNav() {
            if (window._itemShopScrollNavBound) return;
            window._itemShopScrollNavBound = true;

            const carousel = document.getElementById('shop-carousel');
            const navButtons = document.querySelectorAll('.shop-nav-jump[data-shop-nav]');
            const sections = [...document.querySelectorAll('[data-shop-section]')];
            const track = document.getElementById('shop-carousel-track');
            const thumb = document.getElementById('shop-carousel-thumb');

            if (!carousel) return;

            const setActive = (id) => {
                document.querySelectorAll('.shop-nav-btn').forEach((btn) => {
                    btn.classList.toggle('is-active-shop-tab', btn.getAttribute('data-shop-nav') === id);
                });
            };

            const updateThumb = () => {
                if (!track || !thumb) return;
                const scrollable = carousel.scrollWidth - carousel.clientWidth;
                if (scrollable <= 0) {
                    thumb.style.width = '100%';
                    thumb.style.left = '0%';
                    return;
                }
                const widthPct = Math.max(10, (carousel.clientWidth / carousel.scrollWidth) * 100);
                const maxLeftPct = 100 - widthPct;
                const leftPct = (carousel.scrollLeft / scrollable) * maxLeftPct;
                thumb.style.width = widthPct + '%';
                thumb.style.left = leftPct + '%';
            };

            navButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const target = document.getElementById(btn.getAttribute('data-shop-nav'));
                    if (target) {
                        carousel.scrollTo({ left: target.offsetLeft, behavior: 'smooth' });
                        setActive(btn.getAttribute('data-shop-nav'));
                    }
                });
            });

            let scrollSpyTimeout = null;
            carousel.addEventListener('scroll', () => {
                updateThumb();

                clearTimeout(scrollSpyTimeout);
                scrollSpyTimeout = setTimeout(() => {
                    const containerCenter = carousel.scrollLeft + carousel.clientWidth / 2;
                    let closest = sections[0];
                    let closestDistance = Infinity;

                    sections.forEach((section) => {
                        const sectionCenter = section.offsetLeft + section.offsetWidth / 2;
                        const distance = Math.abs(sectionCenter - containerCenter);
                        if (distance < closestDistance) {
                            closestDistance = distance;
                            closest = section;
                        }
                    });

                    if (closest) {
                        setActive(closest.id);
                    }
                }, 100);
            }, { passive: true });

            if (track && thumb) {
                let dragging = false;
                let dragStartX = 0;
                let dragStartScrollLeft = 0;

                thumb.addEventListener('pointerdown', (e) => {
                    dragging = true;
                    dragStartX = e.clientX;
                    dragStartScrollLeft = carousel.scrollLeft;
                    thumb.setPointerCapture(e.pointerId);
                    e.preventDefault();
                });

                thumb.addEventListener('pointermove', (e) => {
                    if (!dragging) return;
                    const scrollable = carousel.scrollWidth - carousel.clientWidth;
                    const draggableTrackWidth = Math.max(1, track.clientWidth - thumb.clientWidth);
                    const ratio = scrollable / draggableTrackWidth;
                    carousel.scrollLeft = dragStartScrollLeft + (e.clientX - dragStartX) * ratio;
                });

                const endDrag = () => { dragging = false; };
                thumb.addEventListener('pointerup', endDrag);
                thumb.addEventListener('pointercancel', endDrag);

                track.addEventListener('pointerdown', (e) => {
                    if (e.target === thumb) return;
                    const rect = track.getBoundingClientRect();
                    const clickRatio = (e.clientX - rect.left) / rect.width;
                    const scrollable = carousel.scrollWidth - carousel.clientWidth;
                    carousel.scrollTo({ left: clickRatio * scrollable, behavior: 'smooth' });
                });
            }

            window.addEventListener('resize', updateThumb);

            setActive('sekcja-reflinki');
            updateThumb();
        }

        document.addEventListener('livewire:init', () => {
            initItemShopScrollNav();
        });

        document.addEventListener('livewire:navigated', () => {
            window._itemShopScrollNavBound = false;
            initItemShopScrollNav();
        });
    </script>
</div>
