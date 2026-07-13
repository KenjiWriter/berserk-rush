<div class="min-h-screen bg-gradient-to-b from-slate-800/90 via-slate-700/90 to-slate-800/90 text-amber-100 relative overflow-hidden" style="font-family: 'Cinzel', serif;">
    {{-- Background image --}}
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat"
        style="background-image: url('{{ asset('img/homepage-background.png') }}');">
    </div>

    {{-- Dark overlay for readability --}}
    <div class="absolute inset-0 bg-gradient-to-b from-slate-900/60 via-slate-800/70 to-slate-900/60"></div>

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

    <div class="relative max-w-6xl mx-auto py-10 px-4 z-10">
        
        {{-- Back Button --}}
        <div class="mb-6">
            <a href="{{ route('homepage') }}" wire:navigate class="text-amber-500 hover:text-amber-300 transition-colors font-bold flex items-center gap-2 w-max">
                <span class="text-xl">🔙</span> Wróć do ekranu głównego
            </a>
        </div>
        {{-- Header --}}
        <div class="text-center mb-10">
            <h1 class="text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-amber-200 via-yellow-400 to-amber-600 drop-shadow-lg mb-4">
                Sklep Premium
            </h1>
            <p class="text-amber-600/80 text-lg">Zdobądź przewagę w świecie Berserk Rush</p>
        </div>

        {{-- Status Bar --}}
        <div class="bg-black/40 backdrop-blur-md border border-amber-800/50 rounded-xl p-4 mb-8 flex flex-col md:flex-row items-center justify-between shadow-[0_0_20px_rgba(20,10,5,0.8)]">
            <div class="flex items-center gap-4 mb-4 md:mb-0">
                <div class="w-12 h-12 rounded-full bg-amber-900 flex items-center justify-center border-2 border-amber-500 shadow-[0_0_15px_rgba(250,204,21,0.3)]">
                    <span class="text-2xl">💎</span>
                </div>
                <div>
                    <p class="text-amber-500/80 text-xs font-bold uppercase tracking-wider">Twoje Gemy</p>
                    <p class="text-2xl font-bold text-amber-300">{{ number_format($user->gems) }}</p>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <div>
                    <p class="text-amber-500/80 text-xs font-bold uppercase tracking-wider text-right">Status Premium</p>
                    @if($user->hasPremium())
                        <p class="text-lg font-bold text-yellow-400 flex items-center justify-end gap-2">
                            <span class="animate-pulse">✨</span> 
                            Aktywne do {{ $user->premium_until->format('d.m.Y H:i') }}
                        </p>
                    @else
                        <p class="text-lg font-bold text-stone-400">Nieaktywne</p>
                    @endif
                </div>
                <div class="w-12 h-12 rounded-full bg-stone-900 flex items-center justify-center border-2 {{ $user->hasPremium() ? 'border-yellow-400 shadow-[0_0_15px_rgba(250,204,21,0.5)]' : 'border-stone-600' }}">
                    <span class="text-2xl {{ $user->hasPremium() ? '' : 'grayscale opacity-50' }}">👑</span>
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="flex justify-center mb-10 border-b border-amber-900/50 pb-px gap-8">
            <button 
                wire:click="setTab('gems')" 
                class="px-6 py-3 font-bold text-lg uppercase tracking-wider transition-all duration-300 relative {{ $activeTab === 'gems' ? 'text-amber-300' : 'text-amber-700/60 hover:text-amber-500' }}"
            >
                Kup Gemy
                @if($activeTab === 'gems')
                    <div class="absolute bottom-0 left-0 w-full h-1 bg-amber-400 shadow-[0_0_10px_rgba(250,204,21,0.8)] rounded-t-full"></div>
                @endif
            </button>
            <button 
                wire:click="setTab('premium')" 
                class="px-6 py-3 font-bold text-lg uppercase tracking-wider transition-all duration-300 relative {{ $activeTab === 'premium' ? 'text-amber-300' : 'text-amber-700/60 hover:text-amber-500' }}"
            >
                Konto VIP
                @if($activeTab === 'premium')
                    <div class="absolute bottom-0 left-0 w-full h-1 bg-amber-400 shadow-[0_0_10px_rgba(250,204,21,0.8)] rounded-t-full"></div>
                @endif
            </button>
        </div>

        {{-- Tab Content: Gems --}}
        @if($activeTab === 'gems')
            @if(request()->has('success'))
                <div class="mb-8 p-4 bg-green-900/30 border border-green-500/50 rounded-xl text-green-300 text-center backdrop-blur-sm">
                    Płatność zakończona sukcesem! Gemy zostaną dodane do Twojego konta w ciągu kilku minut.
                </div>
            @endif
            @if(request()->has('cancel'))
                <div class="mb-8 p-4 bg-red-900/30 border border-red-500/50 rounded-xl text-red-300 text-center backdrop-blur-sm">
                    Płatność została anulowana.
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @forelse($packages as $pkg)
                    <div class="group relative bg-gradient-to-b from-stone-900/90 to-black border border-amber-800/40 rounded-2xl p-6 text-center shadow-xl hover:shadow-[0_0_30px_rgba(217,119,6,0.3)] hover:border-amber-500/60 transition-all duration-300 transform hover:-translate-y-2 overflow-hidden flex flex-col justify-between">
                        {{-- Hover glow effect --}}
                        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-32 h-32 bg-amber-500/20 blur-[50px] opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>

                        <div>
                            <div class="text-4xl mb-4 group-hover:scale-110 transition-transform">💎</div>
                            <h3 class="text-2xl font-bold text-amber-200 mb-1">{{ $pkg->name }}</h3>
                            <p class="text-amber-500/80 font-bold mb-6">+{{ number_format($pkg->gem_amount) }} Gemów</p>
                        </div>
                        
                        <div>
                            <div class="text-3xl font-extrabold text-white mb-6">
                                {{ number_format($pkg->price_in_cents / 100, 2) }} <span class="text-lg text-amber-600/70">{{ $pkg->currency }}</span>
                            </div>
                            <button 
                                wire:click="buyGems('{{ $pkg->id }}')" 
                                class="w-full py-3 rounded-lg bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-white font-bold tracking-wider shadow-[0_0_15px_rgba(217,119,6,0.5)] transition-colors"
                            >
                                Kup Teraz
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center text-amber-700/60 italic py-10">
                        Obecnie brak dostępnych pakietów w sklepie.
                    </div>
                @endforelse
            </div>
        @endif

        {{-- Tab Content: Premium --}}
        @if($activeTab === 'premium')
            <div class="mb-12 text-center max-w-2xl mx-auto">
                <h2 class="text-3xl font-bold text-amber-300 mb-4">Korzyści Konta Premium</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-left">
                    <div class="bg-black/50 border border-amber-900/50 p-4 rounded-xl flex items-center gap-4">
                        <div class="text-3xl">⚔️</div>
                        <div>
                            <h4 class="font-bold text-amber-200">Większy Drop</h4>
                            <p class="text-sm text-amber-600/80">+20% więcej zdobywanego Złota</p>
                        </div>
                    </div>
                    <div class="bg-black/50 border border-amber-900/50 p-4 rounded-xl flex items-center gap-4">
                        <div class="text-3xl">🌟</div>
                        <div>
                            <h4 class="font-bold text-amber-200">Szybszy Exp</h4>
                            <p class="text-sm text-amber-600/80">+20% więcej zdobywanego Doświadczenia</p>
                        </div>
                    </div>
                    <div class="bg-black/50 border border-amber-900/50 p-4 rounded-xl flex items-center gap-4 md:col-span-2 justify-center">
                        <div class="text-3xl">👑</div>
                        <div>
                            <h4 class="font-bold text-yellow-400 drop-shadow-[0_0_8px_rgba(250,204,21,0.8)]">Złoty Nick na Czacie</h4>
                            <p class="text-sm text-amber-600/80">Wyróżnij się wśród innych graczy (Glow Particles)</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                {{-- 3 Days --}}
                <div class="relative bg-gradient-to-b from-stone-900/90 to-black border border-amber-800/40 rounded-2xl p-8 text-center shadow-xl hover:shadow-[0_0_30px_rgba(217,119,6,0.3)] hover:border-amber-500/60 transition-all duration-300 flex flex-col justify-between">
                    <div>
                        <h3 class="text-2xl font-bold text-amber-200 mb-2">3 Dni</h3>
                        <p class="text-amber-600/60 text-sm mb-6">W sam raz na weekend</p>
                        <div class="text-4xl mb-6">🥉</div>
                    </div>
                    <div>
                        <div class="text-3xl font-bold text-amber-300 flex items-center justify-center gap-2 mb-6">
                            100 <span class="text-xl">💎</span>
                        </div>
                        <button 
                            wire:click="buyPremium(3, 100)"
                            class="w-full py-3 rounded-lg border-2 border-amber-600 text-amber-300 font-bold tracking-wider hover:bg-amber-600/20 transition-colors"
                        >
                            {{ auth()->user()->hasPremium() ? 'Przedłuż Premium' : 'Aktywuj Premium' }}
                        </button>
                    </div>
                </div>

                {{-- 14 Days --}}
                <div class="relative bg-gradient-to-b from-amber-900/40 to-black border-2 border-yellow-500 rounded-2xl p-8 text-center shadow-[0_0_30px_rgba(250,204,21,0.2)] transform md:-translate-y-4 flex flex-col justify-between z-10">
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-gradient-to-r from-yellow-600 to-amber-500 text-black font-extrabold px-4 py-1 rounded-full text-xs uppercase tracking-widest shadow-lg">Najpopularniejsze</div>
                    <div>
                        <h3 class="text-3xl font-bold text-yellow-400 mb-2 mt-2">14 Dni</h3>
                        <p class="text-amber-500/80 text-sm mb-6">Optymalny wybór dla aktywnych</p>
                        <div class="text-5xl mb-6 drop-shadow-[0_0_15px_rgba(250,204,21,0.6)] animate-pulse">👑</div>
                    </div>
                    <div>
                        <div class="text-4xl font-bold text-yellow-400 flex items-center justify-center gap-2 mb-6">
                            400 <span class="text-2xl">💎</span>
                        </div>
                        <button 
                            wire:click="buyPremium(14, 400)"
                            class="w-full py-4 rounded-lg bg-gradient-to-r from-yellow-500 to-amber-500 hover:from-yellow-400 hover:to-amber-400 text-black font-extrabold tracking-wider shadow-[0_0_20px_rgba(250,204,21,0.4)] transition-all transform hover:scale-105"
                        >
                            {{ auth()->user()->hasPremium() ? 'Przedłuż Premium' : 'Aktywuj Premium' }}
                        </button>
                    </div>
                </div>

                {{-- 30 Days --}}
                <div class="relative bg-gradient-to-b from-stone-900/90 to-black border border-amber-800/40 rounded-2xl p-8 text-center shadow-xl hover:shadow-[0_0_30px_rgba(217,119,6,0.3)] hover:border-amber-500/60 transition-all duration-300 flex flex-col justify-between">
                    <div>
                        <h3 class="text-2xl font-bold text-amber-200 mb-2">30 Dni</h3>
                        <p class="text-amber-600/60 text-sm mb-6">Miesiąc potęgi</p>
                        <div class="text-4xl mb-6">🥇</div>
                    </div>
                    <div>
                        <div class="text-3xl font-bold text-amber-300 flex items-center justify-center gap-2 mb-6">
                            800 <span class="text-xl">💎</span>
                        </div>
                        <button 
                            wire:click="buyPremium(30, 800)"
                            class="w-full py-3 rounded-lg border-2 border-amber-600 text-amber-300 font-bold tracking-wider hover:bg-amber-600/20 transition-colors"
                        >
                            {{ auth()->user()->hasPremium() ? 'Przedłuż Premium' : 'Aktywuj Premium' }}
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&display=swap');

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
            0%, 100% {
                opacity: 0.3;
                transform: scale(1);
            }
            50% {
                opacity: 0.6;
                transform: scale(1.1);
            }
        }
    </style>
</div>
