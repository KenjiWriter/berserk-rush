<div x-data="{ mouseX: 0, mouseY: 0 }"
     @mousemove="if(window.innerWidth >= 1024) { mouseX = $event.clientX; mouseY = $event.clientY; }"
     class="min-h-screen bg-stone-950 text-amber-100 relative py-8 px-3 sm:px-6 font-sans overflow-x-hidden selection:bg-amber-800 selection:text-amber-100">

    {{-- Dark Fantasy Ambient Lighting Overlay --}}
    <div class="fixed inset-0 bg-gradient-to-b from-stone-950/80 via-amber-950/20 to-stone-950/95 pointer-events-none"></div>
    <div class="fixed inset-0 bg-[radial-gradient(circle_at_center,transparent_0%,rgba(12,10,9,0.85)_100%)] pointer-events-none"></div>

    <div class="max-w-5xl mx-auto relative z-10">
        {{-- Wood Board Decorative Header --}}
        <div class="relative bg-gradient-to-r from-stone-900 via-amber-950/90 to-stone-900 border-2 border-amber-800/70 rounded-2xl p-5 sm:p-6 mb-8 shadow-[0_10px_35px_rgba(0,0,0,0.8)] backdrop-blur-md overflow-hidden">
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/wood-pattern.png')] opacity-20"></div>
            
            {{-- Metallic rivets on frame corners --}}
            <div class="absolute top-3 left-3 w-3 h-3 rounded-full bg-gradient-to-br from-amber-500 to-stone-800 border border-amber-950 shadow"></div>
            <div class="absolute top-3 right-3 w-3 h-3 rounded-full bg-gradient-to-br from-amber-500 to-stone-800 border border-amber-950 shadow"></div>
            <div class="absolute bottom-3 left-3 w-3 h-3 rounded-full bg-gradient-to-br from-amber-500 to-stone-800 border border-amber-950 shadow"></div>
            <div class="absolute bottom-3 right-3 w-3 h-3 rounded-full bg-gradient-to-br from-amber-500 to-stone-800 border border-amber-950 shadow"></div>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 relative z-10">
                <div class="flex items-center gap-4 text-center sm:text-left">
                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-gradient-to-br from-red-700 via-amber-900 to-stone-950 border-2 border-amber-500/70 flex items-center justify-center text-2xl sm:text-3xl text-amber-300 shadow-[0_0_25px_rgba(220,38,38,0.4)] shrink-0">
                        <i class="fa-solid fa-flag"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-bold bg-gradient-to-b from-amber-100 via-amber-300 to-amber-500 bg-clip-text text-transparent medieval-font drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)]">
                            Gildie Królestwa
                        </h1>
                        <p class="text-xs sm:text-sm text-amber-300/70 font-medium tracking-wide">
                            Zjednoczcie siły, toczcie wojny i wznoście potęgę waszego Zakonu.
                        </p>
                    </div>
                </div>

                <button wire:click="goTo('hub')" @click="$dispatch('location-leave'); $dispatch('play-audio', { type: 'tab' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                        class="w-full sm:w-auto bg-gradient-to-r from-stone-900 to-amber-950/80 hover:from-amber-900/80 hover:to-amber-900 text-amber-200 border border-amber-700/60 hover:border-amber-400/90 px-5 py-2.5 rounded-xl font-bold shadow-lg transition-all duration-200 flex items-center justify-center gap-2 medieval-font text-sm hover:scale-105 active:scale-95 group">
                    <i class="fa-solid fa-archway group-hover:-translate-x-0.5 transition-transform text-amber-400"></i> Powrót do miasta
                </button>
            </div>
        </div>

        @if($viewMode === 'list')
            {{-- GUILDS DIRECTORY LIST --}}
            <div class="bg-gradient-to-b from-stone-900/95 via-amber-950/30 to-stone-900/95 border-2 border-amber-800/60 rounded-2xl p-5 sm:p-7 shadow-2xl backdrop-blur-md relative">
                {{-- Decorative Frame Accents --}}
                <div class="absolute top-2 left-2 text-amber-600/40 text-xs font-serif select-none pointer-events-none">❖</div>
                <div class="absolute top-2 right-2 text-amber-600/40 text-xs font-serif select-none pointer-events-none">❖</div>
                <div class="absolute bottom-2 left-2 text-amber-600/40 text-xs font-serif select-none pointer-events-none">❖</div>
                <div class="absolute bottom-2 right-2 text-amber-600/40 text-xs font-serif select-none pointer-events-none">❖</div>

                {{-- Header Actions --}}
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 mb-6 border-b border-amber-800/50 pb-4">
                    <div>
                        <h2 class="text-2xl font-bold text-amber-300 medieval-font flex items-center gap-2.5">
                            <i class="fa-solid fa-shield-halved text-amber-400"></i> Dostępne Przymierza
                        </h2>
                        <p class="text-xs text-amber-400/60">Dołącz do istniejącego Zakonu lub załóż nową gildię w królestwie</p>
                    </div>

                    <button wire:click="setViewMode('create')" @click="$dispatch('play-audio', { type: 'tab' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                            class="bg-gradient-to-r from-red-800 via-amber-900 to-red-900 hover:from-red-700 hover:to-amber-800 text-amber-100 border border-amber-500/60 hover:border-amber-300 px-5 py-2.5 rounded-xl font-bold shadow-[0_0_20px_rgba(185,28,28,0.4)] hover:shadow-[0_0_30px_rgba(245,158,11,0.5)] transition-all duration-200 text-sm flex items-center justify-center gap-2 medieval-font hover:scale-105 active:scale-95">
                        <i class="fa-solid fa-plus text-amber-300"></i> Załóż Nową Gildię
                    </button>
                </div>

                {{-- Search Bar --}}
                <div class="mb-6 relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-amber-500/70 text-sm">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="searchQuery"
                           placeholder="Szukaj gildii po nazwie..."
                           class="w-full bg-stone-950/90 border border-amber-800/60 rounded-xl pl-11 pr-4 py-3 text-sm text-amber-100 placeholder-amber-600/60 focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-500/30 transition shadow-inner">
                </div>

                @error('join')
                    <div class="bg-red-950/80 border-2 border-red-500/70 text-red-100 px-4 py-3 rounded-xl mb-5 text-sm backdrop-blur-md shadow-lg flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-red-400"></i> <span>{{ $message }}</span>
                    </div>
                @enderror

                {{-- Cards Container --}}
                <div class="space-y-4">
                    @forelse($this->guilds as $guild)
                        <div class="bg-gradient-to-r from-stone-900/90 via-amber-950/20 to-stone-900/90 border border-amber-800/40 hover:border-amber-500/70 rounded-xl p-4 sm:p-5 shadow-lg hover:shadow-[0_4px_20px_rgba(245,158,11,0.15)] transition-all duration-300 flex flex-col md:flex-row md:items-center justify-between gap-4 group">
                            
                            {{-- Info Left --}}
                            <div class="flex items-start sm:items-center gap-4">
                                {{-- Emblem Shield --}}
                                <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-gradient-to-br from-amber-900/80 via-stone-900 to-stone-950 border border-amber-500/50 flex flex-col items-center justify-center shrink-0 shadow-inner group-hover:border-amber-400 transition-colors">
                                    <i class="fa-solid fa-shield-halved text-amber-400 text-lg sm:text-xl"></i>
                                    <span class="text-[9px] font-bold text-amber-300 tracking-tighter uppercase mt-0.5">Lvl {{ $guild->level }}</span>
                                </div>

                                <div class="space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-lg sm:text-xl font-bold text-amber-200 medieval-font group-hover:text-amber-300 transition-colors">
                                            {{ $guild->name }}
                                        </h3>
                                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-amber-950/80 text-amber-400 border border-amber-700/60">
                                            Poziom {{ $guild->level }}
                                        </span>
                                    </div>

                                    <p class="text-xs text-amber-300/70 italic">
                                        {{ $guild->title ?? 'Brak tytułu Zakonu' }}
                                    </p>

                                    {{-- Badges --}}
                                    <div class="flex flex-wrap items-center gap-3 text-xs text-stone-400 pt-1">
                                        <span class="flex items-center gap-1.5 bg-stone-950/60 px-2.5 py-1 rounded-lg border border-stone-800">
                                            <i class="fa-solid fa-medal text-amber-500"></i> Min. Lvl: <strong class="text-amber-200">{{ $guild->min_level }}</strong>
                                        </span>
                                        <span class="flex items-center gap-1.5 bg-stone-950/60 px-2.5 py-1 rounded-lg border border-stone-800">
                                            <i class="fa-solid fa-users text-amber-500"></i> Członkowie: <strong class="text-amber-200">{{ $guild->members_count }}/{{ $guild->getMaxMembers() }}</strong>
                                        </span>
                                        <span class="flex items-center gap-1.5 bg-stone-950/60 px-2.5 py-1 rounded-lg border border-stone-800">
                                            @if($guild->is_public)
                                                <i class="fa-solid fa-lock-open text-emerald-400"></i> <span class="text-emerald-400 font-semibold">Otwarta</span>
                                            @else
                                                <i class="fa-solid fa-lock text-red-400"></i> <span class="text-red-400 font-semibold">Na Zaproszenie</span>
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Action Button --}}
                            <div class="self-end md:self-center shrink-0">
                                @if($guild->is_public)
                                    <button wire:click="joinGuild('{{ $guild->id }}')"
                                            @click="$dispatch('play-audio', { type: 'tab' })"
                                            wire:loading.attr="disabled" wire:target="joinGuild('{{ $guild->id }}')"
                                            wire:loading.class="opacity-50 cursor-not-allowed" wire:target="joinGuild('{{ $guild->id }}')"
                                            class="bg-gradient-to-r from-emerald-800 to-teal-900 hover:from-emerald-700 hover:to-teal-800 text-emerald-100 border border-emerald-500/60 hover:border-emerald-400 px-5 py-2 rounded-xl text-sm font-bold shadow-[0_0_15px_rgba(16,185,129,0.3)] hover:shadow-[0_0_20px_rgba(16,185,129,0.5)] transition-all duration-200 flex items-center gap-2 medieval-font hover:scale-105 active:scale-95">
                                        <span wire:loading.remove wire:target="joinGuild('{{ $guild->id }}')" class="flex items-center gap-1.5">
                                            <i class="fa-solid fa-khanda text-emerald-300"></i> Dołącz do Gildii
                                        </span>
                                        <span wire:loading wire:target="joinGuild('{{ $guild->id }}')" class="flex items-center gap-2">
                                            <svg class="animate-spin h-4 w-4 text-emerald-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                            Dołączanie...
                                        </span>
                                    </button>
                                @else
                                    <div class="px-4 py-2 bg-stone-950/80 border border-stone-800 text-stone-500 rounded-xl text-xs font-semibold italic flex items-center gap-2">
                                        <i class="fa-solid fa-lock text-stone-500"></i> Wymagane Zaproszenie
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12 px-4 bg-stone-950/60 border border-amber-900/30 rounded-2xl">
                            <i class="fa-solid fa-scroll text-amber-500 text-4xl mb-3 block"></i>
                            <h3 class="text-xl font-bold text-amber-300 medieval-font mb-1">Brak Aktywnych Gildii</h3>
                            <p class="text-xs text-amber-400/60 max-w-md mx-auto mb-5">Nie odnaleziono gildii pasujących do wyszukiwania. Zostań legendarnym Założycielem i stwórz pierwszy Zakon w królestwie!</p>
                            <button wire:click="setViewMode('create')" @click="$dispatch('play-audio', { type: 'tab' })"
                                    class="bg-gradient-to-r from-amber-800 to-amber-950 text-amber-100 border border-amber-500 px-5 py-2.5 rounded-xl font-bold text-xs medieval-font hover:scale-105 transition flex items-center gap-2 mx-auto">
                                <i class="fa-solid fa-flag text-amber-400"></i> Załóż Pierwszą Gildię
                            </button>
                        </div>
                    @endforelse
                </div>
            </div>

        @elseif($viewMode === 'create')
            {{-- CREATE GUILD FORM (ROYAL CHARTER) --}}
            <div class="bg-gradient-to-b from-stone-900/95 via-amber-950/40 to-stone-900/95 border-2 border-amber-800/70 rounded-2xl p-6 sm:p-8 shadow-[0_15px_40px_rgba(0,0,0,0.9)] backdrop-blur-md max-w-xl mx-auto relative overflow-hidden">
                
                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-amber-800/60 pb-4 mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-amber-900/60 border border-amber-500/60 flex items-center justify-center text-xl text-amber-300 shadow">
                            <i class="fa-solid fa-scroll"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-amber-200 medieval-font drop-shadow">Akt Założycielski Gildii</h2>
                            <p class="text-xs text-amber-400/70">Formowanie Nowego Zakonu Królestwa</p>
                        </div>
                    </div>

                    <button wire:click="setViewMode('list')" @click="$dispatch('play-audio', { type: 'tab' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                            class="text-amber-400/70 hover:text-amber-200 text-xs font-semibold bg-stone-950/60 hover:bg-stone-900 border border-stone-800 hover:border-amber-700/60 px-3 py-1.5 rounded-lg transition flex items-center gap-1.5">
                        <i class="fa-solid fa-xmark"></i> Anuluj
                    </button>
                </div>

                @error('create')
                    <div class="bg-red-950/80 border-2 border-red-500/70 text-red-100 px-4 py-3 rounded-xl mb-5 text-sm shadow-lg flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-red-400"></i> <span>{{ $message }}</span>
                    </div>
                @enderror

                <div class="space-y-5">
                    {{-- Guild Name --}}
                    <div>
                        <label class="block text-amber-400 text-xs font-bold mb-1.5 uppercase tracking-wider medieval-font">
                            Nazwa Gildii / Zakonu *
                        </label>
                        <input type="text" wire:model="newGuildName"
                               placeholder="np. Nocne Jastrzębie"
                               class="w-full bg-stone-950/90 border border-amber-800/60 rounded-xl px-4 py-2.5 text-sm text-amber-100 placeholder-amber-600/50 focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-500/30 transition">
                        @error('newGuildName') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Guild Title / Motto --}}
                    <div>
                        <label class="block text-amber-400 text-xs font-bold mb-1.5 uppercase tracking-wider medieval-font">
                            Dewiza / Krótki Opis
                        </label>
                        <input type="text" wire:model="newGuildTitle"
                               placeholder="np. Mieczem i Tarczą chronimy słabszych"
                               class="w-full bg-stone-950/90 border border-amber-800/60 rounded-xl px-4 py-2.5 text-sm text-amber-100 placeholder-amber-600/50 focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-500/30 transition">
                        @error('newGuildTitle') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Level & Public --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-amber-400 text-xs font-bold mb-1.5 uppercase tracking-wider medieval-font">
                                Wymagany Poziom
                            </label>
                            <input type="number" wire:model="newGuildMinLevel" min="1" max="100"
                                   class="w-full bg-stone-950/90 border border-amber-800/60 rounded-xl px-4 py-2.5 text-sm text-amber-100 focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-500/30 transition">
                            @error('newGuildMinLevel') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-amber-400 text-xs font-bold mb-1.5 uppercase tracking-wider medieval-font">
                                Tryb Rekrutacji
                            </label>
                            <select wire:model="newGuildIsPublic"
                                    class="w-full bg-stone-950/90 border border-amber-800/60 rounded-xl px-4 py-2.5 text-sm text-amber-100 focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-500/30 transition">
                                <option value="1">Publiczna (Każdy dołącza)</option>
                                <option value="0">Zamknięta (Na zaproszenie)</option>
                            </select>
                        </div>
                    </div>

                    {{-- Recruitment Mode Cards --}}
                    <div>
                        <label class="block text-amber-400/80 text-[11px] font-semibold mb-2 uppercase tracking-wider">
                            Wybierz tryb przyjmowania nowych członków:
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" wire:click="$set('newGuildIsPublic', 1)" @click="$dispatch('play-audio', { type: 'hover' })"
                                    class="p-3 rounded-xl border-2 transition-all text-left flex flex-col justify-between {{ $newGuildIsPublic ? 'bg-amber-950/60 border-amber-400 text-amber-100 shadow-[0_0_15px_rgba(245,158,11,0.25)]' : 'bg-stone-950/50 border-stone-800 text-stone-400 hover:border-stone-700' }}">
                                <div class="flex items-center gap-2 mb-1">
                                    <i class="fa-solid fa-lock-open text-amber-400 text-base"></i>
                                    <span class="font-bold text-xs medieval-font">Bramy Otwarte</span>
                                </div>
                                <span class="text-[10px] text-amber-300/60 leading-tight">Każdy gracz spełniający wymóg poziomu może dołączyć natychmiast.</span>
                            </button>

                            <button type="button" wire:click="$set('newGuildIsPublic', 0)" @click="$dispatch('play-audio', { type: 'hover' })"
                                    class="p-3 rounded-xl border-2 transition-all text-left flex flex-col justify-between {{ !$newGuildIsPublic ? 'bg-amber-950/60 border-amber-400 text-amber-100 shadow-[0_0_15px_rgba(245,158,11,0.25)]' : 'bg-stone-950/50 border-stone-800 text-stone-400 hover:border-stone-700' }}">
                                <div class="flex items-center gap-2 mb-1">
                                    <i class="fa-solid fa-lock text-amber-400 text-base"></i>
                                    <span class="font-bold text-xs medieval-font">Zakon Zamknięty</span>
                                </div>
                                <span class="text-[10px] text-amber-300/60 leading-tight">Dołączenie wymaga bezpośredniego zaproszenia przez lidera lub oficera.</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Fee & Submit Button --}}
                <div class="mt-8 border-t border-amber-800/50 pt-6">
                    <div class="flex items-center justify-between bg-stone-950/80 rounded-xl p-4 border border-amber-800/50 mb-5 shadow-inner">
                        <span class="text-xs font-semibold text-amber-300/80 medieval-font">Koszt wpisu do Ksiąg Królewskich:</span>
                        <span class="text-cyan-300 font-bold text-base flex items-center gap-1.5 drop-shadow">
                            150 <i class="fa-solid fa-gem text-cyan-400 text-lg"></i>
                        </span>
                    </div>

                    <button wire:click="createGuild"
                            @click="$dispatch('play-audio', { type: 'click' })"
                            wire:loading.attr="disabled" wire:target="createGuild"
                            wire:loading.class="opacity-50 cursor-not-allowed" wire:target="createGuild"
                            class="w-full bg-gradient-to-r from-red-800 via-amber-900 to-red-900 hover:from-red-700 hover:to-amber-800 text-amber-100 border border-amber-500 py-3.5 rounded-xl font-bold shadow-[0_0_25px_rgba(185,28,28,0.5)] transition-all duration-200 flex items-center justify-center gap-2 medieval-font text-base hover:scale-[1.02] active:scale-95">
                        <span wire:loading.remove wire:target="createGuild" class="flex items-center gap-2">
                            <i class="fa-solid fa-flag text-amber-300"></i> Pieczętuj i Utwórz Gildię
                        </span>
                        <span wire:loading wire:target="createGuild" class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5 text-amber-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Pieczętowanie Aktu...
                        </span>
                    </button>
                </div>
            </div>

        @elseif($viewMode === 'panel')
            {{-- GUILD PANEL VIEW --}}
            @php $guild = $character->guild; @endphp
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Left Column: Guild Stats & Info --}}
                <div class="md:col-span-1 space-y-6">
                    <div class="bg-gradient-to-b from-stone-900/95 via-amber-950/30 to-stone-900/95 border-2 border-amber-800/60 rounded-2xl p-6 shadow-2xl backdrop-blur-md">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-lg bg-amber-900/60 border border-amber-500/60 flex items-center justify-center text-lg text-amber-300 shadow">
                                <i class="fa-solid fa-flag"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-amber-300 medieval-font drop-shadow">{{ $guild->name }}</h2>
                                <p class="text-xs text-amber-400/60 italic">{{ $guild->title ?? 'Brak tytułu Zakonu' }}</p>
                            </div>
                        </div>

                        <div class="space-y-2.5 text-xs sm:text-sm pt-2">
                            <div class="flex justify-between border-b border-amber-800/40 pb-1.5">
                                <span class="text-stone-400">Poziom Gildii:</span>
                                <span class="text-amber-200 font-bold">Lvl {{ $guild->level }}</span>
                            </div>
                            <div class="flex justify-between border-b border-amber-800/40 pb-1.5">
                                <span class="text-stone-400">Wymagany Poziom:</span>
                                <span class="text-amber-200">{{ $guild->min_level }}</span>
                            </div>
                            <div class="flex justify-between border-b border-amber-800/40 pb-1.5">
                                <span class="text-stone-400">Liczba Członków:</span>
                                <span class="text-amber-200 font-semibold">{{ $guild->members()->count() }} / {{ $guild->getMaxMembers() }}</span>
                            </div>
                            <div class="flex justify-between border-b border-amber-800/40 pb-1.5">
                                <span class="text-stone-400">Skarbiec (Złoto):</span>
                                <span class="text-yellow-400 font-semibold">{{ number_format($guild->gold) }} / {{ number_format($guild->getMaxGold()) }}</span>
                            </div>
                            <div class="flex justify-between pb-1">
                                <span class="text-stone-400">Skarbiec (Diamenty):</span>
                                <span class="text-cyan-400 font-semibold flex items-center gap-1">
                                    {{ number_format($guild->gems) }} / {{ number_format($guild->getMaxGems()) }} <i class="fa-solid fa-gem text-cyan-400 text-xs"></i>
                                </span>
                            </div>
                        </div>

                        {{-- EXP Progress --}}
                        <div class="mt-5 bg-stone-950/80 rounded-xl p-3 border border-amber-800/40 text-center text-xs text-stone-400">
                            <div class="flex justify-between items-center mb-1 text-[11px]">
                                <span class="text-amber-300 font-medium">Postęp EXP Gildii</span>
                                @php
                                    $req = $guild->getRequiredXpForNextLevel();
                                    $pct = $req ? min(100, ($guild->xp / $req) * 100) : 100;
                                @endphp
                                <span class="text-amber-400 font-bold">{{ number_format($pct, 1) }}%</span>
                            </div>
                            <div class="w-full bg-stone-900 h-2.5 rounded-full overflow-hidden border border-stone-800">
                                <div class="bg-gradient-to-r from-amber-600 to-amber-400 h-full transition-all duration-300" style="width: {{ $pct }}%"></div>
                            </div>
                            <div class="mt-1.5 text-[10px] text-amber-200/70 font-mono">{{ number_format($guild->xp) }} / {{ $req ? number_format($req) : 'MAX' }}</div>
                        </div>
                    </div>

                    @error('leave')
                        <div class="bg-red-950/80 border-2 border-red-500/70 text-red-100 px-4 py-3 rounded-xl text-xs">
                            {{ $message }}
                        </div>
                    @enderror

                    <button wire:click="leaveGuild"
                            wire:loading.attr="disabled" wire:target="leaveGuild"
                            wire:loading.class="opacity-50 cursor-not-allowed" wire:target="leaveGuild"
                            class="w-full bg-red-950/60 hover:bg-red-900/80 border border-red-700/80 hover:border-red-500 text-red-200 py-2.5 rounded-xl text-xs font-bold transition-all medieval-font shadow flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="leaveGuild" class="flex items-center gap-2">
                            <i class="fa-solid fa-right-from-bracket text-red-400"></i> Opuść Gildię
                        </span>
                        <span wire:loading wire:target="leaveGuild" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-red-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Opuszczanie...
                        </span>
                    </button>
                </div>

                {{-- Right Column: Tabs (Members / Wars / Logs) & Passive Bonuses --}}
                <div class="md:col-span-2 space-y-6">
                    @php $myMember = $guild->members->where('character_id', $character->id)->first(); @endphp

                    <div class="bg-gradient-to-b from-stone-900/95 via-amber-950/30 to-stone-900/95 border-2 border-amber-800/60 rounded-2xl p-6 shadow-2xl backdrop-blur-md">
                        {{-- Tabs Navigation --}}
                        <div class="flex flex-wrap items-center gap-2 border-b border-amber-800/50 pb-3 mb-5">
                            <button wire:click="setPanelTab('members')" @click="$dispatch('play-audio', { type: 'tab' })"
                                    class="px-4 py-2 rounded-xl text-sm font-bold medieval-font transition-all flex items-center gap-2 {{ $panelTab === 'members' ? 'bg-amber-950/80 text-amber-200 border border-amber-500/70 shadow-[0_0_15px_rgba(245,158,11,0.2)]' : 'text-stone-400 hover:text-amber-300 hover:bg-stone-900/60' }}">
                                <i class="fa-solid fa-users text-amber-400"></i> Członkowie Gildii
                            </button>
                            <button wire:click="setPanelTab('wars')" @click="$dispatch('play-audio', { type: 'tab' })"
                                    class="px-4 py-2 rounded-xl text-sm font-bold medieval-font transition-all flex items-center gap-2 {{ $panelTab === 'wars' ? 'bg-amber-950/80 text-amber-200 border border-amber-500/70 shadow-[0_0_15px_rgba(245,158,11,0.2)]' : 'text-stone-400 hover:text-amber-300 hover:bg-stone-900/60' }}">
                                <i class="fa-solid fa-khanda text-amber-400"></i> Wojny Gildii
                            </button>
                            @if($myMember && $myMember->role === 'leader')
                                <button wire:click="setPanelTab('logs')" @click="$dispatch('play-audio', { type: 'tab' })"
                                        class="px-4 py-2 rounded-xl text-sm font-bold medieval-font transition-all flex items-center gap-2 {{ $panelTab === 'logs' ? 'bg-amber-950/80 text-amber-200 border border-amber-500/70 shadow-[0_0_15px_rgba(245,158,11,0.2)]' : 'text-stone-400 hover:text-amber-300 hover:bg-stone-900/60' }}">
                                    <i class="fa-solid fa-scroll text-amber-400"></i> Logi Gildii
                                </button>
                            @endif
                        </div>
                        
                        @if($panelTab === 'members')
                            <div class="mb-3">
                                @error('roster')
                                    <div class="bg-red-950/80 border border-red-500 text-red-200 px-4 py-2 rounded-xl mb-4 text-xs">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-amber-300/70 flex items-center justify-between">
                                    <span>Skład Drużyny Wojennej:</span>
                                    <span class="font-bold text-amber-200">{{ count($guild->war_team ?? []) }}/5 Członków</span>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs sm:text-sm text-stone-300">
                                    <thead class="text-[11px] uppercase bg-stone-950/80 text-amber-400/80 border-b border-amber-800/50">
                                        <tr>
                                            <th class="px-3 py-2.5 medieval-font">Gracz</th>
                                            <th class="px-3 py-2.5 medieval-font">Rola</th>
                                            <th class="px-3 py-2.5 medieval-font">Poz.</th>
                                            @if($myMember && in_array($myMember->role, ['leader', 'commander']))
                                                <th class="px-3 py-2.5 medieval-font text-right">Zarządzanie</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-amber-900/30">
                                        @php $warTeam = $guild->war_team ?? []; @endphp
                                        @foreach($guild->members()->with('character')->get() as $member)
                                            @php $inWar = in_array($member->character_id, $warTeam); @endphp
                                            <tr class="hover:bg-amber-950/20 transition-colors">
                                                <td class="px-3 py-2.5 font-bold text-amber-300 flex items-center gap-2">
                                                    {{ $member->character->name }}
                                                    @if($inWar)
                                                        <i class="fa-solid fa-khanda text-red-400 text-xs" title="W drużynie wojennej"></i>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2.5 capitalize text-amber-500 font-semibold">{{ $member->role }}</td>
                                                <td class="px-3 py-2.5 text-stone-300 font-mono">{{ $member->character->level }}</td>
                                                @if($myMember && in_array($myMember->role, ['leader', 'commander']))
                                                    <td class="px-3 py-2.5 text-right space-x-1 space-y-1">
                                                        @if($myMember->role === 'leader')
                                                            <button wire:click="toggleWarRoster('{{ $member->character_id }}')"
                                                                    wire:loading.attr="disabled"
                                                                    class="text-[11px] border px-2 py-1 rounded-lg transition-all {{ $inWar ? 'border-red-600/70 text-red-300 hover:bg-red-950/50' : 'border-amber-700/60 text-amber-300 hover:bg-amber-950/50' }}">
                                                                {{ $inWar ? 'Wycofaj z wojny' : 'Dodaj do wojny' }}
                                                            </button>
                                                        @endif
                                                        @if($member->character_id !== $character->id && $myMember->role === 'leader')
                                                            <button wire:click="changeRole('{{ $member->character_id }}', 'leader')"
                                                                    wire:confirm="Czy na pewno chcesz przekazać przywództwo gildii tej osobie?"
                                                                    wire:loading.attr="disabled"
                                                                    class="text-[11px] border border-amber-500/70 text-amber-300 hover:bg-amber-900/50 px-2 py-1 rounded-lg transition">
                                                                Przekaż Lidera
                                                            </button>
                                                        @endif
                                                        @if($member->character_id !== $character->id && ($myMember->role === 'leader' || ($myMember->role === 'commander' && !in_array($member->role, ['leader', 'commander']))))
                                                            <select wire:change="changeRole('{{ $member->character_id }}', $event.target.value)" class="text-[11px] bg-stone-950 border border-amber-800/60 rounded-lg px-2 py-1 text-amber-200 focus:outline-none focus:border-amber-400">
                                                                <option value="" disabled selected>Zmień rolę</option>
                                                                @if($myMember->role === 'leader')
                                                                    <option value="commander" {{ $member->role === 'commander' ? 'disabled' : '' }}>Dowódca</option>
                                                                @endif
                                                                <option value="elder" {{ $member->role === 'elder' ? 'disabled' : '' }}>Starszy</option>
                                                                <option value="member" {{ $member->role === 'member' ? 'disabled' : '' }}>Członek</option>
                                                                <option value="novice" {{ $member->role === 'novice' ? 'disabled' : '' }}>Nowicjusz</option>
                                                            </select>
                                                        @endif
                                                        @if($member->character_id !== $character->id && $member->role !== 'leader' && !($myMember->role === 'commander' && $member->role === 'commander'))
                                                            <button wire:click="kickMember('{{ $member->character_id }}')"
                                                                    wire:confirm="Czy na pewno chcesz wyrzucić tego gracza z gildii?"
                                                                    wire:loading.attr="disabled"
                                                                    class="text-[11px] border border-red-600/70 text-red-400 hover:bg-red-950/50 px-2 py-1 rounded-lg transition">
                                                                Wyrzuć
                                                            </button>
                                                        @endif
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @elseif($panelTab === 'logs')
                            <div class="overflow-x-auto max-h-80 overflow-y-auto">
                                <table class="w-full text-left text-xs text-stone-300">
                                    <thead class="text-[11px] uppercase bg-stone-950/90 text-amber-400/80 sticky top-0 border-b border-amber-800/50">
                                        <tr>
                                            <th class="px-3 py-2">Data</th>
                                            <th class="px-3 py-2">Gracz</th>
                                            <th class="px-3 py-2">Akcja</th>
                                            <th class="px-3 py-2">Wartość</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-amber-900/20">
                                        @forelse($this->logs as $log)
                                            <tr class="hover:bg-amber-950/20 transition-colors">
                                                <td class="px-3 py-2 font-mono text-stone-500">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                                                <td class="px-3 py-2 font-bold text-amber-300">{{ $log->character->name }}</td>
                                                <td class="px-3 py-2">
                                                    @if($log->action === 'donate_exp') <span class="text-emerald-400">Dotacja EXP</span>
                                                    @elseif($log->action === 'donate_gold') <span class="text-yellow-400">Wpłata Złota</span>
                                                    @elseif($log->action === 'donate_gems') <span class="text-cyan-400">Wpłata Diamentów</span>
                                                    @else {{ $log->action }} @endif
                                                </td>
                                                <td class="px-3 py-2 font-mono text-amber-200">+{{ number_format($log->amount) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="px-4 py-6 text-center text-stone-500 italic">Brak zapisów w księgach logów.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @elseif($panelTab === 'wars')
                            <div class="space-y-4">
                                @forelse($this->wars as $war)
                                    <div class="bg-stone-950/70 border border-amber-800/40 rounded-xl p-4">
                                        <div class="flex justify-between items-center mb-3">
                                            <div class="flex items-center gap-3">
                                                <span class="font-bold text-sm sm:text-base medieval-font {{ $war->challenger_guild_id === $guild->id ? 'text-cyan-400' : 'text-red-400' }}">
                                                    {{ $war->challengerGuild->name }}
                                                </span>
                                                <span class="text-amber-500/60 font-bold text-xs">VS</span>
                                                <span class="font-bold text-sm sm:text-base medieval-font {{ $war->defender_guild_id === $guild->id ? 'text-cyan-400' : 'text-red-400' }}">
                                                    {{ $war->defenderGuild->name }}
                                                </span>
                                            </div>
                                            <div>
                                                @if($war->status === 'finished')
                                                    @if($war->winner_guild_id === $guild->id)
                                                        <span class="text-emerald-400 font-bold border border-emerald-500/50 bg-emerald-950/60 px-2.5 py-1 rounded-lg text-xs">Zwycięstwo</span>
                                                    @else
                                                        <span class="text-red-400 font-bold border border-red-500/50 bg-red-950/60 px-2.5 py-1 rounded-lg text-xs">Porażka</span>
                                                    @endif
                                                @else
                                                    <span class="text-amber-400 font-bold border border-amber-500/50 bg-amber-950/60 px-2.5 py-1 rounded-lg text-xs">W trakcie</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="text-[11px] text-stone-500 mb-3 border-b border-amber-900/30 pb-2 font-mono">
                                            Data starcia: {{ $war->created_at->format('Y-m-d H:i') }}
                                        </div>
                                        
                                        @if($war->fights->count() > 0)
                                            <div class="grid grid-cols-5 gap-2">
                                                @foreach($war->fights as $fight)
                                                    @php 
                                                        $amIChallenger = $war->challenger_guild_id === $guild->id;
                                                        $myCharId = $amIChallenger ? $fight->challenger_character_id : $fight->defender_character_id;
                                                        $won = $fight->winner_character_id === $myCharId;
                                                    @endphp
                                                    <a href="{{ route('city.arena.combat.gvg', ['character' => $character, 'gvgId' => $fight->id]) }}" wire:navigate class="block border {{ $won ? 'border-emerald-600/50 bg-emerald-950/30 hover:bg-emerald-900/50' : 'border-red-600/50 bg-red-950/30 hover:bg-red-900/50' }} rounded-lg p-2 text-center transition">
                                                        <div class="text-[10px] text-stone-400 mb-1">Runda {{ $fight->fight_order }}</div>
                                                        <div class="text-xs font-bold {{ $won ? 'text-emerald-400' : 'text-red-400' }} mb-1">{{ $won ? 'Wygrana' : 'Przegrana' }}</div>
                                                        <div class="text-[10px] text-amber-300 truncate flex items-center justify-center gap-1">
                                                            <i class="fa-solid fa-play text-[9px]"></i> Obejrzyj
                                                        </div>
                                                    </a>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-xs text-stone-500 italic text-center py-2">Brak rozegranych rund walki.</div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="text-center py-8 text-stone-500 italic text-xs">Twoja gildia nie brała jeszcze udziału w wojnach gildii.</div>
                                @endforelse
                            </div>
                        @endif
                    </div>

                    {{-- Passive Bonuses --}}
                    <div class="bg-gradient-to-b from-stone-900/95 via-amber-950/30 to-stone-900/95 border-2 border-amber-800/60 rounded-2xl p-6 shadow-2xl backdrop-blur-md">
                        <h3 class="text-lg font-bold text-amber-300 medieval-font border-b border-amber-800/50 pb-2 mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-wand-magic-sparkles text-amber-400"></i> Bonusy Pasywne Zakonu
                        </h3>
                        
                        @error('upgrade')
                            <div class="bg-red-950/80 border border-red-500 text-red-200 px-4 py-2 rounded-xl mb-4 text-xs">{{ $message }}</div>
                        @enderror

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- EXP Bonus Card --}}
                            <div class="bg-stone-950/80 p-4 rounded-xl border border-amber-800/40 flex flex-col justify-between">
                                <div>
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="text-emerald-400 font-bold text-base medieval-font">Bonus EXP: +{{ $guild->bonus_xp_level }}%</div>
                                        <span class="text-[10px] font-semibold text-amber-300 bg-amber-950/80 border border-amber-700/60 px-2 py-0.5 rounded-md">Poziom {{ $guild->bonus_xp_level }}/20</span>
                                    </div>
                                    <div class="text-xs text-stone-400 mb-4">Zwiększa punkty doświadczenia ze wszystkich walk w grze dla całego Zakonu.</div>
                                </div>
                                @if($myMember && $myMember->role === 'leader')
                                    @if($guild->bonus_xp_level < 20)
                                        @php
                                            $costGold = (int)(10000 * pow($guild->bonus_xp_level + 1, 1.5));
                                            $costGems = (int)(100 * pow($guild->bonus_xp_level + 1, 1.2));
                                        @endphp
                                        <div class="flex gap-2">
                                            <button wire:click="upgradeBonus('xp', 'gold')"
                                                wire:loading.attr="disabled" wire:target="upgradeBonus"
                                                wire:loading.class="opacity-50 cursor-not-allowed" wire:target="upgradeBonus"
                                                class="flex-1 bg-stone-900 hover:bg-stone-800 border border-amber-800/60 hover:border-amber-500 rounded-xl p-2 transition flex flex-col items-center justify-center">
                                                <span wire:loading.remove wire:target="upgradeBonus('xp', 'gold')" class="flex flex-col items-center">
                                                    <span class="text-xs text-amber-200 font-bold mb-0.5">Ulepsz (+1%)</span>
                                                    <span class="text-[10px] font-mono {{ $guild->gold >= $costGold ? 'text-yellow-400' : 'text-red-400' }}">{{ number_format($costGold) }} Złota</span>
                                                </span>
                                                <span wire:loading wire:target="upgradeBonus('xp', 'gold')">
                                                    <svg class="animate-spin h-4 w-4 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                </span>
                                            </button>
                                            <button wire:click="upgradeBonus('xp', 'gems')"
                                                wire:loading.attr="disabled" wire:target="upgradeBonus"
                                                wire:loading.class="opacity-50 cursor-not-allowed" wire:target="upgradeBonus"
                                                class="flex-1 bg-stone-900 hover:bg-stone-800 border border-amber-800/60 hover:border-amber-500 rounded-xl p-2 transition flex flex-col items-center justify-center">
                                                <span wire:loading.remove wire:target="upgradeBonus('xp', 'gems')" class="flex flex-col items-center">
                                                    <span class="text-xs text-amber-200 font-bold mb-0.5">Ulepsz (+1%)</span>
                                                    <span class="text-[10px] font-mono flex items-center gap-1 {{ $guild->gems >= $costGems ? 'text-cyan-400' : 'text-red-400' }}">{{ number_format($costGems) }} <i class="fa-solid fa-gem text-[9px]"></i></span>
                                                </span>
                                                <span wire:loading wire:target="upgradeBonus('xp', 'gems')">
                                                    <svg class="animate-spin h-4 w-4 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                </span>
                                            </button>
                                        </div>
                                    @else
                                        <div class="w-full bg-emerald-950/60 border border-emerald-700/60 text-emerald-300 rounded-xl p-2 text-center text-xs font-bold medieval-font">Maksymalny Poziom</div>
                                    @endif
                                @endif
                            </div>

                            {{-- Gold Bonus Card --}}
                            <div class="bg-stone-950/80 p-4 rounded-xl border border-amber-800/40 flex flex-col justify-between">
                                <div>
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="text-yellow-400 font-bold text-base medieval-font">Bonus Złota: +{{ $guild->bonus_gold_level }}%</div>
                                        <span class="text-[10px] font-semibold text-amber-300 bg-amber-950/80 border border-amber-700/60 px-2 py-0.5 rounded-md">Poziom {{ $guild->bonus_gold_level }}/20</span>
                                    </div>
                                    <div class="text-xs text-stone-400 mb-4">Zwiększa ilość zdobywanego złota podczas potyczek dla wszystkich członków.</div>
                                </div>
                                @if($myMember && $myMember->role === 'leader')
                                    @if($guild->bonus_gold_level < 20)
                                        @php
                                            $costGold = (int)(10000 * pow($guild->bonus_gold_level + 1, 1.5));
                                            $costGems = (int)(100 * pow($guild->bonus_gold_level + 1, 1.2));
                                        @endphp
                                        <div class="flex gap-2">
                                            <button wire:click="upgradeBonus('gold', 'gold')"
                                                wire:loading.attr="disabled" wire:target="upgradeBonus"
                                                wire:loading.class="opacity-50 cursor-not-allowed" wire:target="upgradeBonus"
                                                class="flex-1 bg-stone-900 hover:bg-stone-800 border border-amber-800/60 hover:border-amber-500 rounded-xl p-2 transition flex flex-col items-center justify-center">
                                                <span wire:loading.remove wire:target="upgradeBonus('gold', 'gold')" class="flex flex-col items-center">
                                                    <span class="text-xs text-amber-200 font-bold mb-0.5">Ulepsz (+1%)</span>
                                                    <span class="text-[10px] font-mono {{ $guild->gold >= $costGold ? 'text-yellow-400' : 'text-red-400' }}">{{ number_format($costGold) }} Złota</span>
                                                </span>
                                                <span wire:loading wire:target="upgradeBonus('gold', 'gold')">
                                                    <svg class="animate-spin h-4 w-4 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                </span>
                                            </button>
                                            <button wire:click="upgradeBonus('gold', 'gems')"
                                                wire:loading.attr="disabled" wire:target="upgradeBonus"
                                                wire:loading.class="opacity-50 cursor-not-allowed" wire:target="upgradeBonus"
                                                class="flex-1 bg-stone-900 hover:bg-stone-800 border border-amber-800/60 hover:border-amber-500 rounded-xl p-2 transition flex flex-col items-center justify-center">
                                                <span wire:loading.remove wire:target="upgradeBonus('gold', 'gems')" class="flex flex-col items-center">
                                                    <span class="text-xs text-amber-200 font-bold mb-0.5">Ulepsz (+1%)</span>
                                                    <span class="text-[10px] font-mono flex items-center gap-1 {{ $guild->gems >= $costGems ? 'text-cyan-400' : 'text-red-400' }}">{{ number_format($costGems) }} <i class="fa-solid fa-gem text-[9px]"></i></span>
                                                </span>
                                                <span wire:loading wire:target="upgradeBonus('gold', 'gems')">
                                                    <svg class="animate-spin h-4 w-4 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                </span>
                                            </button>
                                        </div>
                                    @else
                                        <div class="w-full bg-yellow-950/60 border border-yellow-700/60 text-yellow-300 rounded-xl p-2 text-center text-xs font-bold medieval-font">Maksymalny Poziom</div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
