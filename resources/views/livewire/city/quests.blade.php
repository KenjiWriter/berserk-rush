<div x-data="{ mouseX: 0, mouseY: 0, showTutorialModal: false, activeModalTab: 'stage' }"
     @mousemove="if(window.innerWidth >= 1024) { mouseX = $event.clientX; mouseY = $event.clientY; }"
     @reward-claimed.window="let a = new Audio('/storage/sound/An_uplifting,_ascend_%232-1783933793835.mp3'); a.volume = 0.5; a.play().catch(e => console.log(e))"
     class="min-h-screen bg-stone-950 text-amber-100 relative py-8 px-3 sm:px-6 font-sans overflow-x-hidden selection:bg-amber-800 selection:text-amber-100">
    @php
        $gameStage = auth()->user()->game_stage;
    @endphp

    {{-- Notice Board Background Image with subtle Parallax & Vignette --}}
    <div class="fixed inset-0 bg-cover bg-center bg-no-repeat pointer-events-none transition-transform duration-300 ease-out opacity-35 mix-blend-luminosity brightness-75 scale-105"
         :style="`background-image: url('${'{{ asset('img/quest-board-bg.png') }}'}'); transform: scale(1.05) translate(${(mouseX - window.innerWidth/2) * 0.006}px, ${(mouseY - window.innerHeight/2) * 0.006}px);`">
    </div>

    {{-- Dark Fantasy Ambient Lighting Overlay --}}
    <div class="fixed inset-0 bg-gradient-to-b from-stone-950/80 via-amber-950/30 to-stone-950/95 pointer-events-none"></div>
    <div class="fixed inset-0 bg-[radial-gradient(circle_at_center,transparent_0%,rgba(12,10,9,0.85)_100%)] pointer-events-none"></div>

    <div class="w-full relative z-10">
        {{-- Wood Board Decorative Header --}}
        <div class="relative bg-gradient-to-r from-stone-900 via-amber-950/90 to-stone-900 border-2 border-amber-800/70 rounded-2xl p-5 sm:p-6 mb-8 shadow-[0_10px_35px_rgba(0,0,0,0.8)] backdrop-blur-md overflow-hidden">
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/wood-pattern.png')] opacity-20"></div>
            
            {{-- Metallic rivets on frame corners --}}
            <div class="absolute top-3 left-3 w-3 h-3 rounded-full bg-gradient-to-br from-amber-500 to-stone-800 border border-amber-950 shadow"></div>
            <div class="absolute top-3 right-3 w-3 h-3 rounded-full bg-gradient-to-br from-amber-500 to-stone-800 border border-amber-950 shadow"></div>
            <div class="absolute bottom-3 left-3 w-3 h-3 rounded-full bg-gradient-to-br from-amber-500 to-stone-800 border border-amber-950 shadow"></div>
            <div class="absolute bottom-3 right-3 w-3 h-3 rounded-full bg-gradient-to-br from-amber-500 to-stone-800 border border-amber-950 shadow"></div>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 relative z-10">
                <div class="flex items-center gap-3.5 text-center sm:text-left">
                    <div class="w-13 h-13 sm:w-14 sm:h-14 rounded-2xl bg-gradient-to-br from-amber-700 via-amber-900 to-stone-950 border-2 border-amber-500/60 flex items-center justify-center text-2xl shadow-[0_0_20px_rgba(245,158,11,0.4)] shrink-0">
                        <i class="fa-solid fa-scroll text-amber-400"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-bold bg-gradient-to-b from-amber-100 via-amber-300 to-amber-500 bg-clip-text text-transparent medieval-font drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)]">
                            Tablica Wyzwań
                        </h1>
                        <p class="text-xs sm:text-sm text-amber-300/70 font-medium tracking-wide">
                            Miejska tablica ogłoszeń i księga osiągnięć bohaterów
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap sm:flex-nowrap items-center gap-3 w-full sm:w-auto">
                    <button @click="showTutorialModal = true" @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                            class="w-full sm:w-auto bg-gradient-to-r from-amber-900/90 via-amber-800 to-amber-950/90 hover:from-amber-800 hover:to-amber-900 text-amber-200 border border-amber-500/70 hover:border-amber-400 px-4 py-2.5 rounded-xl font-bold shadow-lg transition-all duration-200 flex items-center justify-center gap-2 medieval-font text-sm hover:scale-105 active:scale-95 relative group {{ $gameStage < 34 ? 'ring-2 ring-amber-400/80 animate-pulse' : '' }}">
                        <i class="fa-solid fa-scroll text-amber-300"></i> Fabuła & Samouczek
                        @if($gameStage < 34)
                            <span class="text-[10px] bg-amber-500 text-stone-950 px-2 py-0.5 rounded-full font-black uppercase tracking-wider ml-1">Etap {{ $gameStage }}</span>
                        @endif
                    </button>

                    <button wire:click="backToHub" @click="$dispatch('location-leave', { text: 'Podróż do Miasta...', icon: 'fa-solid fa-archway', url: '{{ route('city.hub', $character->id) }}' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                            class="w-full sm:w-auto bg-gradient-to-r from-stone-900 to-amber-950/80 hover:from-amber-900/80 hover:to-amber-900 text-amber-200 border border-amber-700/60 hover:border-amber-400/90 px-5 py-2.5 rounded-xl font-bold shadow-lg transition-all duration-200 flex items-center justify-center gap-2 medieval-font text-sm hover:scale-105 active:scale-95 group">
                        <i class="fa-solid fa-archway text-amber-400 transform group-hover:-translate-x-1 transition-transform"></i> Powrót do miasta
                    </button>
                </div>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if(session()->has('message'))
            <div class="bg-gradient-to-r from-emerald-950/90 to-green-900/90 border-2 border-emerald-500/70 text-emerald-100 px-5 py-3.5 rounded-xl mb-6 backdrop-blur-md shadow-lg flex items-center gap-3 animate-fade-in">
                <i class="fa-solid fa-circle-check text-emerald-400 text-xl"></i>
                <div class="font-semibold text-sm sm:text-base">{{ session('message') }}</div>
            </div>
        @endif
        @if(session()->has('error'))
            <div class="bg-gradient-to-r from-red-950/90 to-rose-900/90 border-2 border-red-500/70 text-red-100 px-5 py-3.5 rounded-xl mb-6 backdrop-blur-md shadow-lg flex items-center gap-3 animate-fade-in">
                <i class="fa-solid fa-triangle-exclamation text-red-400 text-xl"></i>
                <div class="font-semibold text-sm sm:text-base">{{ session('error') }}</div>
            </div>
        @endif

        {{-- Navigation Tabs Styled as Wooden / Metal Banners --}}
        <div class="flex flex-wrap gap-3 mb-8">
            <button wire:click="setTab('quests')" @click="$dispatch('play-audio', { type: 'tab' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                    class="relative px-6 py-3 rounded-xl font-bold text-sm sm:text-base border transition-all duration-300 flex items-center gap-2.5 medieval-font {{ $activeTab === 'quests' ? 'bg-gradient-to-b from-amber-800 to-amber-950 text-amber-100 border-amber-400 shadow-[0_0_25px_rgba(245,158,11,0.35)] scale-105 z-10' : 'bg-stone-900/80 text-stone-400 border-stone-800 hover:bg-stone-800/90 hover:text-amber-200 hover:border-amber-700/60' }}">
                <i class="fa-solid fa-location-dot text-amber-400"></i> Misje w Gildii
                @if($activeQuests->contains(fn($cq) => $cq->status->value === 'completed'))
                    <span class="flex h-3 w-3 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                @endif
            </button>

            <button wire:click="setTab('achievements')" @click="$dispatch('play-audio', { type: 'tab' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                    class="relative px-6 py-3 rounded-xl font-bold text-sm sm:text-base border transition-all duration-300 flex items-center gap-2.5 medieval-font {{ $activeTab === 'achievements' ? 'bg-gradient-to-b from-amber-800 to-amber-950 text-amber-100 border-amber-400 shadow-[0_0_25px_rgba(245,158,11,0.35)] scale-105 z-10' : 'bg-stone-900/80 text-stone-400 border-stone-800 hover:bg-stone-800/90 hover:text-amber-200 hover:border-amber-700/60' }} {{ $gameStage == 29 ? 'ring-4 ring-amber-500 animate-pulse z-20' : '' }}">
                <i class="fa-solid fa-trophy text-amber-400"></i> Osiągnięcia Bohatera
            </button>
        </div>

        @if($activeTab === 'quests')
            {{-- Guild Quests Layout --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                {{-- Available Quests Container (Wooden Board Frame) --}}
                <div class="bg-gradient-to-b from-stone-900/95 via-amber-950/40 to-stone-900/95 border-2 border-amber-800/60 rounded-2xl p-5 sm:p-6 shadow-2xl backdrop-blur-md relative">
                    <div class="flex items-center justify-between mb-6 border-b border-amber-800/50 pb-3">
                        <h2 class="text-2xl font-bold text-amber-300 medieval-font flex items-center gap-2">
                            <i class="fa-solid fa-thumbtack text-amber-400"></i> Dostępne Wyzwania
                        </h2>
                        <span class="text-xs bg-amber-950/80 text-amber-300 border border-amber-700/60 px-2.5 py-1 rounded-lg font-bold">
                            {{ count($availableQuests) }} wolnych zleceń
                        </span>
                    </div>
                    
                    @forelse($availableQuests as $quest)
                        {{-- Quest Ticket: pinned note, collapsed to name + level until opened --}}
                        <div x-data="{ open: {{ $gameStage == 24 ? 'true' : 'false' }} }"
                             class="relative bg-[#1c140d] border-2 border-amber-900/60 rounded-xl mb-4 shadow-lg transition-colors duration-300 overflow-hidden"
                             :class="open ? 'border-amber-500/80' : 'hover:border-amber-700/80'">
                            {{-- Brass pin header decoration --}}
                            <div class="absolute -top-1 left-1/2 -translate-x-1/2 w-4 h-4 rounded-full bg-stone-800 border border-amber-900 shadow-md z-10"></div>

                            {{-- Collapsed row: name + level only --}}
                            <button type="button" @click="open = !open" @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                                    class="w-full flex items-center justify-between gap-3 px-5 py-4 text-left">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="shrink-0 w-9 h-9 rounded-lg bg-stone-950 border border-amber-800/60 flex items-center justify-center text-amber-500">
                                        <i class="fa-solid fa-scroll"></i>
                                    </span>
                                    <h3 class="truncate text-lg font-bold text-amber-200 medieval-font">{{ $quest->name }}</h3>
                                </div>
                                <div class="flex items-center gap-2.5 shrink-0">
                                    <span class="text-xs font-bold bg-stone-950 text-amber-300 border border-amber-700/60 px-2.5 py-1 rounded-md">
                                        Poziom {{ $quest->required_level }}
                                    </span>
                                    <span class="hidden sm:flex items-center gap-1.5 text-xs font-bold text-amber-300/90 bg-stone-800 border border-amber-700/50 px-3 py-1.5 rounded-md">
                                        <span x-text="open ? 'Zwiń' : 'Otwórz'"></span>
                                        <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-300" :class="open ? '-rotate-180' : ''"></i>
                                    </span>
                                </div>
                            </button>

                            {{-- Unrolling parchment panel --}}
                            <div class="overflow-hidden transition-[max-height] duration-500 ease-out" :class="open ? 'max-h-[900px]' : 'max-h-0'">
                                <div class="px-5 pb-5 pt-1 border-t border-amber-900/50 transition-opacity duration-300" :class="open ? 'opacity-100 delay-150' : 'opacity-0'">

                                        {{-- Scroll rod divider --}}
                                        <div class="flex items-center gap-2 my-3 opacity-70">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-700"></span>
                                            <span class="flex-1 h-px bg-amber-800/60"></span>
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-700"></span>
                                        </div>

                                        <p class="text-sm text-amber-200/80 mb-4 leading-relaxed font-serif italic">
                                            "{{ $quest->description }}"
                                        </p>

                                        {{-- Requirement Box --}}
                                        <div class="text-sm text-amber-100 bg-stone-950/80 p-3 rounded-lg mb-3 border border-amber-900/60 font-semibold flex items-center gap-2 shadow-inner">
                                            <i class="fa-solid fa-bullseye text-amber-400 text-base"></i>
                                            <span>Cel: {{ $this->getQuestRequirement($quest) }}</span>
                                        </div>

                                        {{-- Hint Box --}}
                                        @if($hint = $this->getQuestHint($quest))
                                            <div class="text-xs text-amber-300/90 bg-amber-950/50 p-2.5 rounded-lg mb-3 border border-amber-800/40 flex items-center gap-2">
                                                <i class="fa-solid fa-lightbulb text-amber-300"></i>
                                                <span>{{ $hint }}</span>
                                            </div>
                                        @endif

                                        {{-- Rewards Badges --}}
                                        <div class="flex flex-wrap gap-2.5 mb-4 text-xs font-bold">
                                            @if($quest->reward_gold > 0)
                                                <span class="bg-stone-950 text-yellow-300 px-3 py-1.5 rounded-lg border border-yellow-700/50 flex items-center gap-1">
                                                    <i class="fa-solid fa-coins text-yellow-400"></i> {{ number_format($quest->reward_gold) }} złota
                                                </span>
                                            @endif
                                            @if($quest->reward_exp > 0)
                                                <span class="bg-stone-950 text-sky-300 px-3 py-1.5 rounded-lg border border-sky-700/50 flex items-center gap-1">
                                                    <i class="fa-solid fa-sparkles text-sky-300"></i> {{ number_format($quest->reward_exp) }} XP
                                                </span>
                                            @endif
                                        </div>

                                        <button wire:click="acceptQuest('{{ $quest->id }}')" @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                                                class="w-full bg-amber-700 hover:bg-amber-600 active:bg-amber-800 text-amber-50 font-bold py-2.5 px-4 rounded-xl transition-colors duration-200 medieval-font flex items-center justify-center gap-2 border border-amber-400/40 {{ $gameStage == 24 ? 'ring-4 ring-amber-500 animate-pulse relative z-20' : '' }}">
                                            <i class="fa-solid fa-scroll text-amber-200"></i> Przyjmij Wyzwanie
                                        </button>
                                    </div>
                                </div>
                        </div>
                    @empty
                        <div class="text-center py-12 px-4 bg-stone-950/40 rounded-xl border border-amber-900/30">
                            <div class="text-3xl text-amber-500/80 mb-3"><i class="fa-solid fa-fire-flame-curved"></i></div>
                            <p class="text-amber-400/80 italic font-medium">Brak nowych zleceń na Twoim poziomie. Wróć tu po zdobyciu kolejnych poziomów!</p>
                        </div>
                    @endforelse
                </div>

                {{-- Active Quests & Completed History --}}
                <div class="space-y-8">
                    
                    {{-- Active Quests Box --}}
                    <div class="bg-gradient-to-b from-stone-900/95 via-stone-900/90 to-stone-950/95 border-2 border-sky-800/60 rounded-2xl p-5 sm:p-6 shadow-2xl backdrop-blur-md relative">
                        <div class="flex items-center justify-between mb-6 border-b border-sky-800/50 pb-3">
                            <h2 class="text-2xl font-bold text-sky-300 medieval-font flex items-center gap-2">
                                <i class="fa-solid fa-khanda text-sky-400"></i> Trwające Misje
                            </h2>
                            <span class="text-xs bg-sky-950/80 text-sky-300 border border-sky-700/60 px-2.5 py-1 rounded-lg font-bold">
                                {{ count($activeQuests) }} w trakcie
                            </span>
                        </div>
                        
                        @forelse($activeQuests as $cq)
                            @php
                                $quest = $cq->quest;
                                $currentProgress = $this->getQuestProgress($cq);
                                $isReadyToClaim = $this->isQuestReadyToClaim($cq);
                                $percent = min(100, ($currentProgress / max(1, $quest->target_amount)) * 100);
                            @endphp
                            <div x-data="{ open: {{ $gameStage == 27 ? 'true' : 'false' }} }"
                                 class="relative bg-[#0e1620] border-2 border-sky-900/60 rounded-xl mb-4 shadow-lg transition-colors duration-300 overflow-hidden"
                                 :class="open ? 'border-sky-400/80' : 'hover:border-sky-700/80'">

                                {{-- Collapsed row: name + level + status only --}}
                                <button type="button" @click="open = !open" @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                                        class="w-full flex items-center justify-between gap-3 px-5 py-4 text-left">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="shrink-0 w-9 h-9 rounded-lg bg-stone-950 border border-sky-800/60 flex items-center justify-center text-sky-400">
                                            <i class="fa-solid fa-khanda"></i>
                                        </span>
                                        <h3 class="truncate text-lg font-bold text-sky-200 medieval-font">{{ $quest->name }}</h3>
                                    </div>
                                    <div class="flex items-center gap-2.5 shrink-0">
                                        <span class="text-xs font-bold bg-stone-950 text-sky-300 border border-sky-700/60 px-2.5 py-1 rounded-md">
                                            Poziom {{ $quest->required_level }}
                                        </span>
                                        @if($isReadyToClaim)
                                            <span class="hidden sm:flex items-center gap-1.5 text-xs font-bold bg-emerald-950 text-emerald-300 border border-emerald-600/60 px-2.5 py-1 rounded-md animate-pulse">
                                                <i class="fa-solid fa-check"></i> Gotowe
                                            </span>
                                        @else
                                            <span class="hidden sm:inline text-xs font-bold bg-stone-950 text-sky-300/90 border border-sky-800/50 px-2.5 py-1 rounded-md">
                                                {{ $currentProgress }} / {{ $quest->target_amount }}
                                            </span>
                                        @endif
                                        <i class="fa-solid fa-chevron-down text-sky-500 text-[10px] transition-transform duration-300" :class="open ? '-rotate-180' : ''"></i>
                                    </div>
                                </button>

                                {{-- Slim progress strip, visible even collapsed --}}
                                @unless($isReadyToClaim)
                                    <div class="h-1 bg-stone-950 mx-5 mb-3 rounded-full overflow-hidden">
                                        <div class="bg-sky-500 h-full rounded-full transition-all duration-500" style="width: {{ $percent }}%"></div>
                                    </div>
                                @endunless

                                {{-- Unrolling parchment panel --}}
                                <div class="overflow-hidden transition-[max-height] duration-500 ease-out" :class="open ? 'max-h-[900px]' : 'max-h-0'">
                                    <div class="px-5 pb-5 pt-1 border-t border-sky-900/50 transition-opacity duration-300" :class="open ? 'opacity-100 delay-150' : 'opacity-0'">

                                            <div class="flex items-center gap-2 my-3 opacity-70">
                                                <span class="w-1.5 h-1.5 rounded-full bg-sky-700"></span>
                                                <span class="flex-1 h-px bg-sky-800/60"></span>
                                                <span class="w-1.5 h-1.5 rounded-full bg-sky-700"></span>
                                            </div>

                                            <p class="text-sm text-sky-200/80 mb-4 leading-relaxed font-serif italic">
                                                "{{ $quest->description }}"
                                            </p>

                                            <div class="text-sm text-white bg-stone-950/80 p-3 rounded-lg mb-3 border border-sky-900/60 font-semibold flex items-center gap-2 shadow-inner">
                                                <i class="fa-solid fa-bullseye text-sky-400 text-base"></i>
                                                <span>Cel: {{ $this->getQuestRequirement($quest) }}</span>
                                            </div>

                                            @if($hint = $this->getQuestHint($quest))
                                                <div class="text-xs text-sky-300/90 bg-sky-950/50 p-2.5 rounded-lg mb-3 border border-sky-800/40 flex items-center gap-2">
                                                    <i class="fa-solid fa-lightbulb text-sky-300"></i>
                                                    <span>{{ $hint }}</span>
                                                </div>
                                            @endif

                                            <div class="flex flex-wrap gap-2.5 mb-4 text-xs font-bold">
                                                @if($quest->reward_gold > 0)
                                                    <span class="bg-stone-950 text-yellow-300 px-3 py-1.5 rounded-lg border border-yellow-700/50 flex items-center gap-1">
                                                        <i class="fa-solid fa-coins text-yellow-400"></i> {{ number_format($quest->reward_gold) }} złota
                                                    </span>
                                                @endif
                                                @if($quest->reward_exp > 0)
                                                    <span class="bg-stone-950 text-sky-300 px-3 py-1.5 rounded-lg border border-sky-700/50 flex items-center gap-1">
                                                        <i class="fa-solid fa-sparkles text-sky-300"></i> {{ number_format($quest->reward_exp) }} XP
                                                    </span>
                                                @endif
                                            </div>

                                            @if($isReadyToClaim)
                                                <button wire:click="claimReward('{{ $cq->id }}')" @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                                                        class="w-full bg-amber-600 hover:bg-amber-500 active:bg-amber-700 text-stone-950 font-extrabold py-2.5 px-4 rounded-xl transition-colors duration-200 medieval-font flex items-center justify-center gap-2 uppercase tracking-wider border border-amber-300/60 {{ $gameStage == 27 ? 'ring-4 ring-amber-500 animate-pulse relative z-20' : '' }}">
                                                    @if($quest->type->value === 'gathering')
                                                        <i class="fa-solid fa-box text-stone-950"></i> Oddaj przedmioty
                                                    @else
                                                        <i class="fa-solid fa-gift text-stone-950"></i> Odbierz Nagrodę
                                                    @endif
                                                </button>
                                            @else
                                                <div class="flex justify-between text-xs text-sky-300/80 font-bold mb-1">
                                                    <span>Postęp {{ $quest->type->value === 'gathering' ? 'zbierania' : 'misji' }}</span>
                                                    <span>{{ round($percent) }}%</span>
                                                </div>
                                                <div class="w-full bg-stone-950 rounded-full h-3 border border-sky-900/80 shadow-inner overflow-hidden">
                                                    <div class="bg-sky-500 h-full rounded-full transition-all duration-500" style="width: {{ $percent }}%"></div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                        @empty
                            <div class="text-center py-8 px-4 bg-stone-950/40 rounded-xl border border-sky-900/30">
                                <p class="text-sky-400/70 italic font-medium">Obecnie nie wykonujesz żadnych misji. Wybierz wyzwanie z listy po lewej stronie!</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Completed Quests History Ledger --}}
                    <div class="bg-gradient-to-b from-stone-900/95 to-stone-950/95 border-2 border-stone-800/80 rounded-2xl p-5 sm:p-6 shadow-xl backdrop-blur-md">
                        <h2 class="text-xl font-bold text-stone-300 medieval-font mb-4 flex items-center gap-2 border-b border-stone-800 pb-2">
                            <i class="fa-solid fa-scroll text-amber-400"></i> Kronika Odebranych Wyzwań
                        </h2>
                        <div class="max-h-56 overflow-y-auto pr-2 space-y-2.5 custom-scrollbar">
                            @forelse($completedQuests as $cq)
                                <div class="bg-stone-950/70 border border-stone-800/70 rounded-xl p-3 flex justify-between items-center gap-3 transition-colors hover:border-amber-900/50">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <i class="fa-solid fa-check text-emerald-400 text-sm shrink-0"></i>
                                        <span class="text-stone-300 font-bold text-sm medieval-font truncate">{{ $cq->quest->name }}</span>
                                        <span class="hidden sm:inline text-[10px] font-bold text-stone-500 border border-stone-800 px-1.5 py-0.5 rounded shrink-0">Poz. {{ $cq->quest->required_level }}</span>
                                    </div>
                                    <span class="text-stone-500 text-xs font-semibold px-2 py-0.5 bg-stone-900 rounded border border-stone-800 shrink-0">Ukończono</span>
                                </div>
                            @empty
                                <p class="text-center text-stone-500 italic py-4 text-sm">Brak ukończonych misji w kronice.</p>
                            @endforelse
                        </div>
                    </div>

                </div>
            </div>
        @else
            {{-- Hero Achievements Section --}}
            <div class="bg-gradient-to-b from-stone-900/95 via-amber-950/30 to-stone-950/95 border-2 border-amber-800/70 rounded-2xl p-5 sm:p-6 shadow-2xl backdrop-blur-md">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-6 border-b border-amber-800/50 pb-4">
                    <div class="flex items-center gap-3 text-center sm:text-left">
                        <i class="fa-solid fa-trophy text-amber-400 text-3xl"></i>
                        <div>
                            <h2 class="text-2xl font-bold text-amber-300 medieval-font">Księga Osiągnięć Bohatera</h2>
                            <p class="text-xs text-amber-300/70">Wypełniaj legendarne kamienie milowe i zdobywaj unikalne tytuły oraz statystyki</p>
                        </div>
                    </div>
                    <div class="text-sm font-extrabold bg-gradient-to-r from-amber-950 to-stone-900 text-amber-300 px-5 py-2.5 rounded-xl border border-amber-600/60 shadow-inner flex items-center gap-2">
                        <i class="fa-solid fa-star text-amber-400"></i> Punkty Osiągnięć: <span class="text-amber-100 text-lg">{{ number_format($character->achievement_points ?? 0) }}</span>
                    </div>
                </div>

                @if(empty($achievements) || $achievements->isEmpty())
                    <div class="text-center py-12 px-4 bg-stone-950/40 rounded-xl border border-amber-900/30">
                        <div class="text-3xl text-amber-500/80 mb-3"><i class="fa-solid fa-shield-halved"></i></div>
                        <p class="text-amber-400/80 italic font-medium">Brak dostępnych osiągnięć w tej kategrii.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-4">
                        @foreach($achievements as $achievement)
                            @php
                                $ca = $achievement->characterAchievements->first();
                                $progress = $ca ? $ca->progress : 0;
                                $completedAt = $ca ? $ca->completed_at : null;
                                $rewarded = $ca ? $ca->rewarded : false;
                                
                                $percent = min(100, ($progress / max(1, $achievement->target_value)) * 100);
                                $isCompleted = $completedAt !== null;
                                $canClaim = $isCompleted && !$rewarded;
                            @endphp
                            
                            <div class="border-2 {{ $isCompleted ? 'border-amber-500/60 bg-gradient-to-r from-[#241a12] to-[#1a120b]' : 'border-stone-800 bg-stone-900/60' }} rounded-xl p-5 transition-all duration-300 hover:border-amber-500/80 shadow-lg">
                                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-3">
                                    <div class="flex-1">
                                        <h4 class="font-bold text-white text-lg flex items-center gap-2 medieval-font">
                                            <i class="fa-solid {{ $isCompleted ? 'fa-award text-amber-400' : 'fa-scroll text-stone-400' }}"></i>
                                            <span class="{{ $isCompleted ? 'text-amber-200' : 'text-stone-300' }}">{{ $achievement->name }}</span>
                                            @if($isCompleted && $rewarded) 
                                                <span class="text-amber-400 text-xs px-2.5 py-0.5 rounded border border-amber-500/60 bg-amber-950/80 uppercase font-bold tracking-wider">Mistrz</span> 
                                            @endif
                                        </h4>
                                        <p class="text-sm text-stone-400 mt-1 font-serif italic">{{ $achievement->description }}</p>
                                    </div>

                                    {{-- Rewards Chips --}}
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if($achievement->reward_points > 0)
                                            <div class="text-xs font-bold text-amber-300 bg-amber-950/80 px-2.5 py-1 rounded-lg border border-amber-700/60 shadow flex items-center gap-1"><i class="fa-solid fa-trophy text-amber-400"></i> {{ $achievement->reward_points }} Pkt</div>
                                        @endif
                                        @if($achievement->reward_exp > 0)
                                            <div class="text-xs font-bold text-sky-300 bg-sky-950/80 px-2.5 py-1 rounded-lg border border-sky-700/60 shadow flex items-center gap-1"><i class="fa-solid fa-sparkles text-sky-300"></i> {{ number_format($achievement->reward_exp) }} XP</div>
                                        @endif
                                        @if($achievement->reward_gold > 0)
                                            <div class="text-xs font-bold text-yellow-300 bg-yellow-950/80 px-2.5 py-1 rounded-lg border border-yellow-700/60 shadow flex items-center gap-1"><i class="fa-solid fa-coins text-yellow-400"></i> {{ number_format($achievement->reward_gold) }} Gold</div>
                                        @endif
                                        @if($achievement->reward_title_id)
                                            <div class="text-xs font-bold text-purple-300 bg-purple-950/80 px-2.5 py-1 rounded-lg border border-purple-700/60 shadow flex items-center gap-1" title="{{ $achievement->title?->name }}"><i class="fa-solid fa-crown text-purple-400"></i> Tytuł</div>
                                        @endif
                                        @if($achievement->reward_item_template_id)
                                            <div class="text-xs font-bold text-emerald-300 bg-emerald-950/80 px-2.5 py-1 rounded-lg border border-emerald-700/60 shadow flex items-center gap-1" title="{{ $achievement->itemTemplate?->name }}"><i class="fa-solid fa-box text-emerald-400"></i> Przedmiot</div>
                                        @endif
                                        @if($achievement->stats_bonus)
                                            <div class="text-xs font-bold text-rose-300 bg-rose-950/80 px-2.5 py-1 rounded-lg border border-rose-700/60 shadow flex items-center gap-1" title="Bonusowe statystyki z tego osiągnięcia"><i class="fa-solid fa-chart-line text-rose-400"></i> Statystyki</div>
                                        @endif
                                    </div>
                                </div>
                                
                                {{-- Progress & Claim Action --}}
                                <div class="flex flex-col sm:flex-row items-center gap-4 mt-2">
                                    <div class="w-full sm:flex-1 bg-stone-950 rounded-full h-3.5 border border-stone-800 shadow-inner overflow-hidden">
                                        <div class="bg-gradient-to-r from-amber-600 via-amber-500 to-yellow-400 h-full rounded-full transition-all duration-500 shadow-[0_0_10px_rgba(245,158,11,0.4)]" style="width: {{ $percent }}%"></div>
                                    </div>
                                    <div class="text-xs text-amber-400/90 font-mono w-full sm:w-28 text-center sm:text-right font-bold">
                                        {{ number_format($progress) }} / {{ number_format($achievement->target_value) }}
                                    </div>
                                    
                                    <div class="w-full sm:w-auto min-w-[130px]">
                                        @if($canClaim)
                                            <button wire:click="claimAchievement('{{ $ca->id }}')" @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                                                    class="w-full px-4 py-2 bg-gradient-to-r from-emerald-600 to-green-500 hover:from-emerald-500 hover:to-green-400 text-white text-xs sm:text-sm font-extrabold rounded-xl shadow-[0_0_15px_rgba(34,197,94,0.4)] transition-all duration-200 transform hover:scale-105 active:scale-95 medieval-font uppercase tracking-wider flex items-center justify-center gap-1.5">
                                                <i class="fa-solid fa-gift"></i> Odbierz Nagrodę
                                            </button>
                                        @elseif($rewarded)
                                            <div class="w-full text-center py-1.5 text-xs text-amber-400/80 font-bold uppercase tracking-widest border border-amber-900/50 rounded-xl bg-amber-950/40 flex items-center justify-center gap-1">
                                                <i class="fa-solid fa-check text-amber-400"></i> Odebrano
                                            </div>
                                        @else
                                            <div class="w-full text-center py-1.5 text-xs text-stone-500 font-bold uppercase tracking-widest border border-stone-800 rounded-xl bg-stone-950/40">
                                                W Trakcie
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    @php
        $stageInfo = [
            0 => ['title' => 'Stworzenie Postaci', 'desc' => 'Musisz stworzyć swoją pierwszą postać.', 'action' => 'Przejdź do ekranu tworzenia postaci i wpisz jej nazwę.', 'location' => 'Ekran Tworzenia Postaci'],
            1 => ['title' => 'Rozdanie Punktów Początkowych', 'desc' => 'Określ predyspozycje swojej postaci.', 'action' => 'Rozdziel punkty atrybutów według swojego stylu gry i zatwierdź.', 'location' => 'Ekran Tworzenia Postaci'],
            2 => ['title' => 'Wejście do Obozu', 'desc' => 'Wybierz bohatera, aby wejść do Głównego Obozu.', 'action' => 'Kliknij na stworzoną postać na liście, aby wejść do gry.', 'location' => 'Wybór Postaci'],
            3 => ['title' => 'Spotkanie z Kapitana', 'desc' => 'Kapitan wręcza Ci Twój pierwszy ekwipunek.', 'action' => 'Kliknij "Tak jest, Kapitanie!" na dole ekranu w Głównym Obozie.', 'location' => 'Główny Obóz (Hub)'],
            4 => ['title' => 'Przejście do Profilu', 'desc' => 'Czas obejrzeć i założyć nową broń.', 'action' => 'Kliknij przycisk "Profil" w menu miejsca w Głównym Obozie.', 'location' => 'Główny Obóz (Hub)'],
            5 => ['title' => 'Wyposażenie Zardzewiałego Miecza', 'desc' => 'Musisz założyć Zardzewiały Miecz z plecaka.', 'action' => 'W widoku Profilu najedź na Zardzewiały Miecz w plecaku i kliknij go, by wyposażyć.', 'location' => 'Profil Postaci'],
            6 => ['title' => 'Odbiór Doświadczenia', 'desc' => 'Założyłeś miecz! Kapitan nagradza Cię punktami XP.', 'action' => 'Kliknij przycisk Kapitana na dole widoku Profilu.', 'location' => 'Profil Postaci'],
            7 => ['title' => 'Powrót do Obozu', 'desc' => 'Wróć do obozu z bronią w ręku.', 'action' => 'Kliknij przycisk "Powrót do miasta".', 'location' => 'Profil Postaci'],
            8 => ['title' => 'Wyprawa poza Mury', 'desc' => 'Kapitan wysyła Cię na pierwszą walkę.', 'action' => 'W Głównym Obozie wybierz zakładkę "Przygoda".', 'location' => 'Główny Obóz (Hub)'],
            9 => ['title' => 'Wybór Pierwszej Mapy', 'desc' => 'Wybierz pierwszą dostępną mapę (poziomy 0-15).', 'action' => 'Kliknij pierwszą mapę na liście (Mroczny Las).', 'location' => 'Centrum Przygód'],
            10 => ['title' => 'Wejście na Mapę', 'desc' => 'Przygotowanie do walki z potworem.', 'action' => 'Kliknij pierwszego przeciwnika na mapie.', 'location' => 'Mapa Wypraw'],
            11 => ['title' => 'Pierwsza Walka', 'desc' => 'Stocz walkę bez trybu automatycznego.', 'action' => 'Kliknij przycisk "Rozpocznij Walkę".', 'location' => 'Ekran Walki'],
            12 => ['title' => 'Wygrana i Odbiór Hełmu', 'desc' => 'Pokonałeś pierwszego potwora!', 'action' => 'Kliknij przycisk u Kapitana na dole ekranu walki, by odebrać Hełm.', 'location' => 'Ekran Walki'],
            13 => ['title' => 'Powrót do Miasta i Profilu', 'desc' => 'Zdobyłeś poziom! Czas rozdać punkty.', 'action' => 'Wróć do Miasta, przejdź do Profilu i rozdaj punkty atrybutów.', 'location' => 'Główny Obóz / Profil'],
            14 => ['title' => 'Rozdanie Punktów Atrybutów', 'desc' => 'Rozdziel przydzielone punkty po awansie.', 'action' => 'Kliknij plusy przy atrybutach w Profilu i zapisz.', 'location' => 'Profil Postaci'],
            15 => ['title' => 'Odbiór Nagrody 150 Golda', 'desc' => 'Kapitan przyznaje Ci złoto za postępy.', 'action' => 'Kliknij dymek Kapitana w Profilu, by odebrać 150 sztuk złota.', 'location' => 'Profil Postaci'],
            16 => ['title' => 'Wizyta u Handlarza', 'desc' => 'Pora odwiedzić kowala/handlarza.', 'action' => 'Wróć do Głównego Obozu i kliknij kafel "Handlarz".', 'location' => 'Główny Obóz (Hub)'],
            17 => ['title' => 'Instrukcja u Handlarza', 'desc' => 'Handlarz objaśnia kupno i ulepszanie broni.', 'action' => 'Zapoznaj się z dymkiem Kapitana u Handlarza i kliknij dalej.', 'location' => 'Sklep Handlarza'],
            18 => ['title' => 'Zakup Miecza Nowicjusza', 'desc' => 'Musisz kupić nową broń u Handlarza.', 'action' => 'Znajdź "Miecz Nowicjusza" w sklepie i kliknij "Kup".', 'location' => 'Sklep Handlarza'],
            19 => ['title' => 'Potwierdzenie Zakupu', 'desc' => 'Kupiłeś nową broń!', 'action' => 'Potwierdź dymek Kapitana u Handlarza.', 'location' => 'Sklep Handlarza'],
            20 => ['title' => 'Odbiór Skórzanej Zbroi', 'desc' => 'Finał pierwszego etapu samouczka.', 'action' => 'Wróć do Głównego Obozu i odbierz Skórzaną Zbroję od Kapitana.', 'location' => 'Główny Obóz (Hub)'],
            21 => ['title' => 'Swobodny Rozwój (Do 5 Poziomu)', 'desc' => 'Eksploruj świat i zdobywaj doświadczenie.', 'action' => 'Zdobądź 5 poziom postaci, aby odblokować Tablicę Wyzwań.', 'location' => 'Przygoda / Mapa'],
            22 => ['title' => 'Odblokowana Tablica Wyzwań', 'desc' => 'Kapitan informuje o nowej Tablicy Wyzwań.', 'action' => 'Przejdź z Głównego Obozu na Tablicę Wyzwań (/quests).', 'location' => 'Główny Obóz (Hub)'],
            23 => ['title' => 'Wejście na Tablicę Wyzwań', 'desc' => 'Jesteś na Tablicy Wyzwań.', 'action' => 'Przeczytaj wskazówki Kapitana na dole ekranu Tablicy Wyzwań.', 'location' => 'Tablica Wyzwań (/quests)'],
            24 => ['title' => 'Przyjęcie Pierwszej Misji', 'desc' => 'Musisz podjąć pierwsze wyzwanie.', 'action' => 'Kliknij przycisk "Przyjmij Wyzwanie" przy dowolnym wolnym zleceniu po lewej stronie.', 'location' => 'Tablica Wyzwań (/quests)'],
            25 => ['title' => 'Realizacja Zadania', 'desc' => 'Wykonaj cel podjętej misji.', 'action' => 'Pokonaj wymagane potwory w Przygodzie lub zbierz przedmioty, a następnie wróć tutaj.', 'location' => 'Przygoda / Mapa'],
            26 => ['title' => 'Oddanie Misji i Odbiór Nagrody', 'desc' => 'Misja ukończona!', 'action' => 'Kliknij "Odbierz Nagrodę" lub "Oddaj przedmioty" przy aktywnej misji po prawej stronie.', 'location' => 'Tablica Wyzwań (/quests)'],
            27 => ['title' => 'Przejście do Osiągnięć', 'desc' => 'Kapitan zachęca do zobaczenia Osiągnięć.', 'action' => 'Kliknij zakładkę "Osiągnięcia Bohatera" na górze Tablicy Wyzwań.', 'location' => 'Tablica Wyzwań (/quests)'],
            28 => ['title' => 'Przegląd Osiągnięć', 'desc' => 'Jesteś w zakładce Osiągnięć.', 'action' => 'Zapoznaj się z osiągnięciami i kliknij przycisk Kapitana.', 'location' => 'Tablica Wyzwań (/quests)'],
            29 => ['title' => 'Koniec Etapu Wyzwań', 'desc' => 'Ukończono samouczek misji.', 'action' => 'Wróć do Głównego Obozu.', 'location' => 'Główny Obóz (Hub)'],
            30 => ['title' => 'Czarodziej w Obozie', 'desc' => 'W obozie pojawił się Czarodziej.', 'action' => 'Kliknij kafel "Czarodziej" w Głównym Obozie.', 'location' => 'Główny Obóz (Hub)'],
            31 => ['title' => 'Wizyta u Czarodzieja', 'desc' => 'Kapitan przedstawia tajniki zaklinania.', 'action' => 'Zapoznaj się z dymkiem Kapitana u Czarodzieja.', 'location' => 'Chata Czarodzieja'],
            32 => ['title' => 'Zaklęcie Przedmiotu', 'desc' => 'Wykonaj pomyślne zaklinanie.', 'action' => 'Wybierz przedmiot i kliknij "Zaklnij przedmiot".', 'location' => 'Chata Czarodzieja'],
            33 => ['title' => 'Odbiór Finałowej Nagrody', 'desc' => 'Przedmiot zaklęty z sukcesem!', 'action' => 'Kliknij przycisk Kapitana, by odebrać 200 EXP i 250 Golda.', 'location' => 'Chata Czarodzieja'],
            34 => ['title' => 'Samouczek Ukończony!', 'desc' => 'Osiągnąłeś pełne przeszkolenie w Berserk Rush!', 'action' => 'Jesteś wolnym bohaterem. Ruszaj podbijać świat!', 'location' => 'Wszędzie']
        ];
        $currentStageData = $stageInfo[$gameStage] ?? [
            'title' => 'Samouczek Ukończony',
            'desc' => 'Ukończyłeś cały samouczek!',
            'action' => 'Możesz swobodnie eksplorować świat, wykonywać misje i walczyć z World Bossami.',
            'location' => 'Cała Kraina'
        ];
        $hasCompletedQuest = $activeQuests->contains(fn($cq) => $cq->status->value === 'completed');
    @endphp

    {{-- Story & Tutorial Recovery Modal --}}
    <div x-show="showTutorialModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-[150] flex items-center justify-center p-3 sm:p-6 bg-black/80 backdrop-blur-md overflow-y-auto"
         style="display: none;"
         @keydown.escape.window="showTutorialModal = false">
        
        <div class="relative bg-gradient-to-b from-[#1e1711] via-[#150f0a] to-[#0d0805] border-2 border-amber-600/80 rounded-2xl sm:rounded-3xl p-5 sm:p-8 max-w-4xl w-full shadow-[0_20px_70px_rgba(0,0,0,0.95),0_0_40px_rgba(245,158,11,0.25)] text-amber-100 my-auto max-h-[90vh] flex flex-col justify-between overflow-hidden">
            
            {{-- Parchment texture pattern overlay --}}
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/wood-pattern.png')] opacity-15 pointer-events-none"></div>
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(245,158,11,0.15),transparent_70%)] pointer-events-none"></div>

            {{-- Metallic Corner Rivets --}}
            <div class="absolute top-3 left-3 w-3 h-3 rounded-full bg-gradient-to-br from-amber-400 via-amber-600 to-stone-900 border border-amber-950 shadow"></div>
            <div class="absolute top-3 right-3 w-3 h-3 rounded-full bg-gradient-to-br from-amber-400 via-amber-600 to-stone-900 border border-amber-950 shadow"></div>
            <div class="absolute bottom-3 left-3 w-3 h-3 rounded-full bg-gradient-to-br from-amber-400 via-amber-600 to-stone-900 border border-amber-950 shadow"></div>
            <div class="absolute bottom-3 right-3 w-3 h-3 rounded-full bg-gradient-to-br from-amber-400 via-amber-600 to-stone-900 border border-amber-950 shadow"></div>

            {{-- Modal Header --}}
            <div class="flex items-center justify-between border-b border-amber-800/60 pb-4 mb-5 relative z-10">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-600 to-amber-900 border border-amber-400 flex items-center justify-center text-xl shadow-lg shrink-0">
                        <i class="fa-solid fa-scroll text-amber-300"></i>
                    </div>
                    <div>
                        <h2 class="text-xl sm:text-2xl font-bold bg-gradient-to-r from-amber-100 via-amber-300 to-amber-500 bg-clip-text text-transparent medieval-font">
                            Fabuła Krainy & Przewodnik Samouczka
                        </h2>
                        <p class="text-xs text-amber-300/70 font-medium">
                            Księga wiedzy, historia Obozu oraz pomoc dla zagubionych bohaterów
                        </p>
                    </div>
                </div>

                <button @click="showTutorialModal = false" class="w-9 h-9 rounded-xl bg-stone-900/80 border border-amber-700/60 text-amber-300 hover:text-white hover:bg-amber-900/60 hover:border-amber-400 transition-all flex items-center justify-center font-bold text-lg">
                    ✕
                </button>
            </div>

            {{-- Modal Navigation Tabs --}}
            <div class="flex flex-wrap gap-2 mb-5 relative z-10 border-b border-amber-900/40 pb-3">
                <button @click="activeModalTab = 'stage'"
                        :class="activeModalTab === 'stage' ? 'bg-amber-800 text-amber-100 border-amber-400 shadow-md' : 'bg-stone-950/60 text-amber-400/70 border-stone-800 hover:text-amber-200'"
                        class="px-4 py-2 rounded-xl border text-xs sm:text-sm font-bold medieval-font transition-all flex items-center gap-1.5">
                    <i class="fa-solid fa-bullseye text-amber-400"></i> Twój Obecny Etap (GameStage {{ $gameStage }})
                </button>
                <button @click="activeModalTab = 'story'"
                        :class="activeModalTab === 'story' ? 'bg-amber-800 text-amber-100 border-amber-400 shadow-md' : 'bg-stone-950/60 text-amber-400/70 border-stone-800 hover:text-amber-200'"
                        class="px-4 py-2 rounded-xl border text-xs sm:text-sm font-bold medieval-font transition-all flex items-center gap-1.5">
                    <i class="fa-solid fa-book-open text-amber-400"></i> Fabuła Berserk Rush
                </button>
                <button @click="activeModalTab = 'help'"
                        :class="activeModalTab === 'help' ? 'bg-amber-800 text-amber-100 border-amber-400 shadow-md' : 'bg-stone-950/60 text-amber-400/70 border-stone-800 hover:text-amber-200'"
                        class="px-4 py-2 rounded-xl border text-xs sm:text-sm font-bold medieval-font transition-all flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-question text-amber-400"></i> Zgubiłeś się? (Pełny Przewodnik)
                </button>
            </div>

            {{-- Modal Body Content --}}
            <div class="overflow-y-auto pr-2 custom-scrollbar flex-1 space-y-5 relative z-10 max-h-[55vh]">
                
                {{-- TAB 1: Current Stage --}}
                <div x-show="activeModalTab === 'stage'" class="space-y-4">
                    {{-- Progress Banner --}}
                    <div class="bg-gradient-to-r from-stone-900 via-amber-950/60 to-stone-900 border border-amber-700/60 rounded-xl p-4 shadow-inner flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-amber-400/80 font-mono">Stan Samouczka</span>
                            <h3 class="text-lg font-bold text-amber-200 medieval-font flex items-center gap-2">
                                @if($gameStage >= 34)
                                    <i class="fa-solid fa-award text-amber-400"></i> Samouczek Ukończony (Etap 34/34)
                                @else
                                    W Trakcie Treningu — Etap {{ $gameStage }} z 34
                                @endif
                            </h3>
                        </div>
                        <div class="w-full sm:w-48 bg-stone-950 rounded-full h-4 border border-amber-800 shadow-inner overflow-hidden">
                            <div class="bg-gradient-to-r from-amber-600 to-yellow-400 h-full rounded-full transition-all duration-500 shadow-[0_0_12px_rgba(245,158,11,0.5)]"
                                 style="width: {{ min(100, round(($gameStage / 34) * 100)) }}%"></div>
                        </div>
                    </div>

                    {{-- Current Step Action Box --}}
                    <div class="bg-gradient-to-b from-[#251b14] to-[#18110b] border-2 border-amber-500/70 rounded-xl p-5 shadow-xl relative overflow-hidden">
                        <div class="absolute top-0 right-0 bg-amber-600 text-stone-950 text-[10px] font-black uppercase px-3 py-1 rounded-bl-xl medieval-font">
                            Krok {{ $gameStage }}
                        </div>

                        <div class="flex items-start gap-3.5 mb-3">
                            <i class="fa-solid fa-hand-point-right text-amber-400 text-2xl mt-1 shrink-0"></i>
                            <div>
                                <h4 class="text-xl font-bold text-amber-200 medieval-font">{{ $currentStageData['title'] }}</h4>
                                <p class="text-sm text-amber-300/80 italic font-serif">{{ $currentStageData['desc'] }}</p>
                            </div>
                        </div>

                        <div class="space-y-2 mt-4 text-sm bg-stone-950/80 p-4 rounded-lg border border-amber-900/60">
                            <div class="flex items-center gap-2 text-amber-300">
                                <span><i class="fa-solid fa-location-dot text-amber-400 mr-1"></i><strong>Lokalizacja:</strong></span>
                                <span class="text-amber-100 font-semibold bg-amber-950/80 px-2.5 py-0.5 rounded border border-amber-800/60">{{ $currentStageData['location'] }}</span>
                            </div>
                            <div class="flex items-start gap-2 text-amber-200 mt-2">
                                <i class="fa-solid fa-bullseye text-amber-400 text-base shrink-0 mt-0.5"></i>
                                <div>
                                    <strong>Co należy zrobić:</strong>
                                    <p class="text-amber-100 mt-0.5 font-medium leading-relaxed">{{ $currentStageData['action'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TAB 2: Story / Fabuła --}}
                <div x-show="activeModalTab === 'story'" class="space-y-4" style="display: none;">
                    <div class="bg-stone-950/70 border border-amber-800/60 rounded-xl p-5 space-y-4 text-amber-100/90 leading-relaxed font-serif text-sm sm:text-base">
                        <div class="flex items-center gap-3 border-b border-amber-800/40 pb-3">
                            <i class="fa-solid fa-khanda text-amber-400 text-xl"></i>
                            <h3 class="text-lg font-bold text-amber-300 medieval-font">Księga Cieni i Męstwa</h3>
                        </div>

                        <p>
                            Witaj w brutalnym świecie <strong>Berserk Rush</strong>. Niegdyś kwitnące królestwo zostało obrócone w pył przez starożytną plagę potworów, która przebudziła się w najgłębszych ostępach Mrocznego Lasu. Ocalałe niedobitki ludzkości wycofały się pod osłonę starego <strong>Kapitana Obozu</strong>, tworząc ostatni bastion nadziei.
                        </p>

                        <p>
                            Jako jeden z nielicznych rekrutów obdarzonych wolą walki, przyjmujesz wyzwanie. W naszym obozie nie ma ustalonych przeznaczeń — to Ty decydujesz, czy opanujesz sztukę ciężkiego oręża, szybkich pchnięć, czy zaklinania magiczną energią u miejscowego Czarodzieja.
                        </p>

                        <div class="bg-amber-950/40 border-l-4 border-amber-500 p-3.5 rounded-r-xl italic text-amber-200/90 text-xs sm:text-sm">
                            "Mieszczanie i gildie pozostawiają zlecenia na Tablicy Wyzwań. Ruszaj poza bezpieczne mury obozu, pokonuj stwory, zdobywaj złoto i legendarne przedmioty, by stawić czoła potężnym World Bossom!"
                        </div>
                    </div>
                </div>

                {{-- TAB 3: Full Help Guide for Lost Players --}}
                <div x-show="activeModalTab === 'help'" class="space-y-4" style="display: none;">
                    <div class="bg-amber-950/30 border border-amber-800/60 rounded-xl p-4 text-xs sm:text-sm text-amber-200">
                        <span class="font-bold text-amber-300"><i class="fa-solid fa-lightbulb text-amber-300 mr-1.5"></i>Jak działa system samouczka?</span>
                        <p class="mt-1 leading-relaxed text-amber-200/80">
                            Samouczek prowadzi Cię krok po kroku przez kluczowe meandry gry. Kapitan wyświetla dymki dialogowe na dole ekranu w odpowiednich widokach. Jeśli zamkniesz okienko lub zapomnisz, co zrobić, skorzystaj z poniższego wykazu etapów:
                        </p>
                    </div>

                    <div class="space-y-3 text-xs sm:text-sm">
                        <div class="border border-stone-800 bg-stone-950/80 rounded-xl p-3.5">
                            <h4 class="font-bold text-amber-300 medieval-font text-base mb-1">1. Początek & Profil (Etapy 0 – 8)</h4>
                            <p class="text-amber-200/80">Stwórz postać → Wejdź do Głównego Obozu → Odbierz Zardzewiały Miecz od Kapitana → Przejdź do Profilu → Wyposaż Miecz z plecaka → Odbierz doświadczenie od Kapitana.</p>
                        </div>

                        <div class="border border-stone-800 bg-stone-950/80 rounded-xl p-3.5">
                            <h4 class="font-bold text-amber-300 medieval-font text-base mb-1">2. Dziewicza Wyprawa & Walka (Etapy 9 – 13)</h4>
                            <p class="text-amber-200/80">Przejdź do zakładki Przygoda → Wybierz mapę (0-15) → Rozpocznij pierwszą walkę → Pokonaj potwora → Odbierz Hełm od Kapitana po walce.</p>
                        </div>

                        <div class="border border-stone-800 bg-stone-950/80 rounded-xl p-3.5">
                            <h4 class="font-bold text-amber-300 medieval-font text-base mb-1">3. Rozwój & Handlarz (Etapy 14 – 21)</h4>
                            <p class="text-amber-200/80">Wróć do Miasta i Profilu → Rozdziel punkty atrybutów → Odbierz 150 sztuk złota → Przejdź do Handlarza → Kup Miecz Nowicjusza → Odbierz Skórzaną Zbroję w Obozie.</p>
                        </div>

                        <div class="border border-stone-800 bg-stone-950/80 rounded-xl p-3.5">
                            <h4 class="font-bold text-amber-300 medieval-font text-base mb-1">4. Tablica Wyzwań & Osiągnięcia (Etapy 22 – 30)</h4>
                            <p class="text-amber-200/80">Wbij 5 poziom postaci → Odwiedź Tablicę Wyzwań (/quests) → Przyjmij pierwszą misję → Wykonaj ją w Przygodzie → Oddaj misję i odebrnij nagrodę → Przejdź do Osiągnięć Bohatera.</p>
                        </div>

                        <div class="border border-stone-800 bg-stone-950/80 rounded-xl p-3.5">
                            <h4 class="font-bold text-amber-300 medieval-font text-base mb-1">5. Tajemnice Czarodzieja & Zaklinanie (Etapy 31 – 34)</h4>
                            <p class="text-amber-200/80">Przejdź z Obozu do Czarodzieja → Zaklnij pomyślnie dowolny przedmiot ze swojego ekwipunku → Odbierz nagrodę 200 XP oraz 250 Golda od Kapitana.</p>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Modal Footer --}}
            <div class="flex justify-end border-t border-amber-900/60 pt-4 mt-4 relative z-10">
                <button @click="showTutorialModal = false" class="bg-gradient-to-r from-amber-600 via-amber-500 to-yellow-600 hover:from-amber-500 hover:to-yellow-400 text-stone-950 font-extrabold py-2.5 px-6 rounded-xl shadow-[0_0_20px_rgba(245,158,11,0.4)] hover:scale-105 active:scale-95 transition-all duration-200 border border-amber-300 medieval-font text-xs sm:text-sm uppercase tracking-wider flex items-center gap-1.5">
                    Rozumiem, wracam do gry <i class="fa-solid fa-khanda"></i>
                </button>
            </div>
        </div>
    </div>

    @if($gameStage == 23 && $activeTab === 'quests')
        <livewire:global.tutorial-overlay :step="24" />
    @elseif($gameStage == 25 && $activeTab === 'quests')
        <livewire:global.tutorial-overlay :step="26" />
    @elseif($gameStage == 26 && $hasCompletedQuest)
        <livewire:global.tutorial-overlay :step="27" />
    @elseif($gameStage == 28)
        <livewire:global.tutorial-overlay :step="29" />
    @elseif($gameStage == 29 && $activeTab === 'achievements')
        <livewire:global.tutorial-overlay :step="30" />
    @endif
</div>

