<div x-data="{ mouseX: 0, mouseY: 0 }"
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
                    <div class="w-13 h-13 sm:w-14 sm:h-14 rounded-2xl bg-gradient-to-br from-amber-700 via-amber-900 to-stone-950 border-2 border-amber-500/60 flex items-center justify-center text-3xl shadow-[0_0_20px_rgba(245,158,11,0.4)] shrink-0">
                        📜
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

                <button wire:click="backToHub" @click="$dispatch('location-leave')" @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                        class="w-full sm:w-auto bg-gradient-to-r from-stone-900 to-amber-950/80 hover:from-amber-900/80 hover:to-amber-900 text-amber-200 border border-amber-700/60 hover:border-amber-400/90 px-5 py-2.5 rounded-xl font-bold shadow-lg transition-all duration-200 flex items-center justify-center gap-2 medieval-font text-sm hover:scale-105 active:scale-95 group">
                    <span class="transform group-hover:-translate-x-1 transition-transform">🏰</span> Powrót do miasta
                </button>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if(session()->has('message'))
            <div class="bg-gradient-to-r from-emerald-950/90 to-green-900/90 border-2 border-emerald-500/70 text-emerald-100 px-5 py-3.5 rounded-xl mb-6 backdrop-blur-md shadow-lg flex items-center gap-3 animate-fade-in">
                <span class="text-xl">✅</span>
                <div class="font-semibold text-sm sm:text-base">{{ session('message') }}</div>
            </div>
        @endif
        @if(session()->has('error'))
            <div class="bg-gradient-to-r from-red-950/90 to-rose-900/90 border-2 border-red-500/70 text-red-100 px-5 py-3.5 rounded-xl mb-6 backdrop-blur-md shadow-lg flex items-center gap-3 animate-fade-in">
                <span class="text-xl">⚠️</span>
                <div class="font-semibold text-sm sm:text-base">{{ session('error') }}</div>
            </div>
        @endif

        {{-- Navigation Tabs Styled as Wooden / Metal Banners --}}
        <div class="flex flex-wrap gap-3 mb-8">
            <button wire:click="setTab('quests')" @click="$dispatch('play-audio', { type: 'tab' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                    class="relative px-6 py-3 rounded-xl font-bold text-sm sm:text-base border transition-all duration-300 flex items-center gap-2.5 medieval-font {{ $activeTab === 'quests' ? 'bg-gradient-to-b from-amber-800 to-amber-950 text-amber-100 border-amber-400 shadow-[0_0_25px_rgba(245,158,11,0.35)] scale-105 z-10' : 'bg-stone-900/80 text-stone-400 border-stone-800 hover:bg-stone-800/90 hover:text-amber-200 hover:border-amber-700/60' }}">
                <span>📍</span> Misje w Gildii
                @if($activeQuests->contains(fn($cq) => $cq->status->value === 'completed'))
                    <span class="flex h-3 w-3 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                @endif
            </button>

            <button wire:click="setTab('achievements')" @click="$dispatch('play-audio', { type: 'tab' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                    class="relative px-6 py-3 rounded-xl font-bold text-sm sm:text-base border transition-all duration-300 flex items-center gap-2.5 medieval-font {{ $activeTab === 'achievements' ? 'bg-gradient-to-b from-amber-800 to-amber-950 text-amber-100 border-amber-400 shadow-[0_0_25px_rgba(245,158,11,0.35)] scale-105 z-10' : 'bg-stone-900/80 text-stone-400 border-stone-800 hover:bg-stone-800/90 hover:text-amber-200 hover:border-amber-700/60' }} {{ $gameStage == 29 ? 'ring-4 ring-amber-500 animate-pulse z-20' : '' }}">
                <span>🏆</span> Osiągnięcia Bohatera
            </button>
        </div>

        @if($activeTab === 'quests')
            {{-- Guild Quests Layout --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                {{-- Available Quests Container (Wooden Board Frame) --}}
                <div class="bg-gradient-to-b from-stone-900/95 via-amber-950/40 to-stone-900/95 border-2 border-amber-800/60 rounded-2xl p-5 sm:p-6 shadow-2xl backdrop-blur-md relative">
                    <div class="flex items-center justify-between mb-6 border-b border-amber-800/50 pb-3">
                        <h2 class="text-2xl font-bold text-amber-300 medieval-font flex items-center gap-2">
                            <span>📌</span> Dostępne Wyzwania
                        </h2>
                        <span class="text-xs bg-amber-950/80 text-amber-300 border border-amber-700/60 px-2.5 py-1 rounded-lg font-bold">
                            {{ count($availableQuests) }} wolnych zleceń
                        </span>
                    </div>
                    
                    @forelse($availableQuests as $quest)
                        {{-- Quest Parchment Flyer Card --}}
                        <div class="relative bg-gradient-to-b from-[#241a13] to-[#18110b] border-2 border-amber-800/70 hover:border-amber-500/80 rounded-xl p-5 mb-5 shadow-xl transition-all duration-300 hover:scale-[1.01] group overflow-hidden">
                            {{-- Brass pin header decoration --}}
                            <div class="absolute -top-1 left-1/2 -translate-x-1/2 w-4 h-4 rounded-full bg-gradient-to-br from-amber-400 via-amber-600 to-stone-900 border border-amber-900 shadow-md z-10"></div>
                            
                            <div class="flex justify-between items-start mb-3 pt-1">
                                <h3 class="text-xl font-bold text-amber-200 group-hover:text-amber-100 transition-colors medieval-font">
                                    {{ $quest->name }}
                                </h3>
                                <span class="text-xs font-bold bg-amber-950 text-amber-300 border border-amber-700/60 px-2.5 py-1 rounded-md shadow-inner shrink-0 ml-2">
                                    Poziom {{ $quest->required_level }}
                                </span>
                            </div>
                            
                            <p class="text-sm text-amber-200/80 mb-4 leading-relaxed font-serif italic">
                                "{{ $quest->description }}"
                            </p>
                            
                            {{-- Requirement Box --}}
                            <div class="text-sm text-amber-100 bg-stone-950/80 p-3 rounded-lg mb-3 border border-amber-900/60 font-semibold flex items-center gap-2 shadow-inner">
                                <span class="text-amber-400 text-lg">🎯</span>
                                <span>Cel: {{ $this->getQuestRequirement($quest) }}</span>
                            </div>

                            {{-- Hint Box --}}
                            @if($hint = $this->getQuestHint($quest))
                                <div class="text-xs text-amber-300/90 bg-amber-950/50 p-2.5 rounded-lg mb-3 border border-amber-800/40 flex items-center gap-2">
                                    <span>💡</span>
                                    <span>{{ $hint }}</span>
                                </div>
                            @endif

                            {{-- Rewards Badges --}}
                            <div class="flex flex-wrap gap-2.5 mb-4 text-xs font-bold">
                                @if($quest->reward_gold > 0)
                                    <span class="bg-amber-950/80 text-yellow-300 px-3 py-1.5 rounded-lg border border-yellow-600/50 flex items-center gap-1 shadow">
                                        💰 {{ number_format($quest->reward_gold) }} złota
                                    </span>
                                @endif
                                @if($quest->reward_exp > 0)
                                    <span class="bg-blue-950/80 text-sky-300 px-3 py-1.5 rounded-lg border border-sky-600/50 flex items-center gap-1 shadow">
                                        ✨ {{ number_format($quest->reward_exp) }} XP
                                    </span>
                                @endif
                            </div>

                            <button wire:click="acceptQuest('{{ $quest->id }}')" @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                                    class="w-full bg-gradient-to-r from-amber-700 via-amber-600 to-emerald-600 hover:from-amber-600 hover:to-emerald-500 text-white font-bold py-2.5 px-4 rounded-xl shadow-lg transition-all duration-200 medieval-font flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-[0.98] border border-amber-400/40 {{ $gameStage == 24 ? 'ring-4 ring-amber-500 animate-pulse relative z-20' : '' }}">
                                <span>📜</span> Przyjmij Wyzwanie
                            </button>
                        </div>
                    @empty
                        <div class="text-center py-12 px-4 bg-stone-950/40 rounded-xl border border-amber-900/30">
                            <div class="text-4xl mb-3">🕯️</div>
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
                                <span>⚔️</span> Trwające Misje
                            </h2>
                            <span class="text-xs bg-sky-950/80 text-sky-300 border border-sky-700/60 px-2.5 py-1 rounded-lg font-bold">
                                {{ count($activeQuests) }} w trakcie
                            </span>
                        </div>
                        
                        @forelse($activeQuests as $cq)
                            @php $quest = $cq->quest; @endphp
                            <div class="relative bg-gradient-to-b from-[#101a26] to-[#0c131d] border-2 border-sky-700/60 hover:border-sky-400/80 rounded-xl p-5 mb-5 shadow-xl transition-all duration-300 hover:scale-[1.01] group overflow-hidden">
                                <div class="flex justify-between items-start mb-3">
                                    <h3 class="text-xl font-bold text-sky-200 group-hover:text-sky-100 transition-colors medieval-font">
                                        {{ $quest->name }}
                                    </h3>
                                    @if($cq->status->value === 'completed')
                                        <span class="text-xs font-bold bg-emerald-900/90 text-emerald-200 border border-emerald-500/70 px-3 py-1 rounded-md shadow-md animate-pulse flex items-center gap-1">
                                            <span>✓</span> Ukończone
                                        </span>
                                    @else
                                        <span class="text-xs font-bold bg-sky-950 text-sky-300 border border-sky-700/60 px-2.5 py-1 rounded-md shadow-inner">
                                            {{ $cq->progress }} / {{ $quest->target_amount }}
                                        </span>
                                    @endif
                                </div>
                                
                                <p class="text-sm text-sky-200/80 mb-4 leading-relaxed font-serif italic">
                                    "{{ $quest->description }}"
                                </p>
                                
                                <div class="text-sm text-white bg-stone-950/80 p-3 rounded-lg mb-3 border border-sky-900/60 font-semibold flex items-center gap-2 shadow-inner">
                                    <span class="text-sky-400 text-lg">🎯</span>
                                    <span>Cel: {{ $this->getQuestRequirement($quest) }}</span>
                                </div>

                                @if($hint = $this->getQuestHint($quest))
                                    <div class="text-xs text-sky-300/90 bg-sky-950/50 p-2.5 rounded-lg mb-3 border border-sky-800/40 flex items-center gap-2">
                                        <span>💡</span>
                                        <span>{{ $hint }}</span>
                                    </div>
                                @endif
                                
                                <div class="flex flex-wrap gap-2.5 mb-4 text-xs font-bold">
                                    @if($quest->reward_gold > 0)
                                        <span class="bg-amber-950/80 text-yellow-300 px-3 py-1.5 rounded-lg border border-yellow-600/50 flex items-center gap-1 shadow">
                                            💰 {{ number_format($quest->reward_gold) }} złota
                                        </span>
                                    @endif
                                    @if($quest->reward_exp > 0)
                                        <span class="bg-blue-950/80 text-sky-300 px-3 py-1.5 rounded-lg border border-sky-600/50 flex items-center gap-1 shadow">
                                            ✨ {{ number_format($quest->reward_exp) }} XP
                                        </span>
                                    @endif
                                </div>
                                
                                @if($cq->status->value === 'completed' || $quest->type->value === 'gathering')
                                    <button wire:click="claimReward('{{ $cq->id }}')" @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                                            class="w-full bg-gradient-to-r from-amber-600 via-yellow-500 to-amber-600 hover:from-amber-500 hover:to-yellow-400 text-stone-950 font-extrabold py-2.5 px-4 rounded-xl shadow-[0_0_20px_rgba(245,158,11,0.4)] transition-all duration-200 medieval-font flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-[0.98] uppercase tracking-wider {{ $gameStage == 27 ? 'ring-4 ring-amber-500 animate-pulse relative z-20' : '' }}">
                                        <span>🎁</span> Odbierz Nagrodę
                                    </button>
                                @else
                                    {{-- Progress Bar --}}
                                    <div class="mt-3">
                                        <div class="flex justify-between text-xs text-sky-300/80 font-bold mb-1">
                                            <span>Postęp misji</span>
                                            <span>{{ round(($cq->progress / max(1, $quest->target_amount)) * 100) }}%</span>
                                        </div>
                                        <div class="w-full bg-stone-950 rounded-full h-3 border border-sky-900/80 shadow-inner overflow-hidden">
                                            <div class="bg-gradient-to-r from-sky-600 to-cyan-400 h-full rounded-full transition-all duration-500 shadow-[0_0_10px_rgba(56,189,248,0.5)]" style="width: {{ min(100, ($cq->progress / max(1, $quest->target_amount)) * 100) }}%"></div>
                                        </div>
                                    </div>
                                @endif
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
                            <span>📜</span> Kronika Odebranych Wyzwań
                        </h2>
                        <div class="max-h-56 overflow-y-auto pr-2 space-y-2.5 custom-scrollbar">
                            @forelse($completedQuests as $cq)
                                <div class="bg-stone-950/70 border border-stone-800/70 rounded-xl p-3 flex justify-between items-center transition-colors hover:border-amber-900/50">
                                    <div class="flex items-center gap-2.5">
                                        <span class="text-emerald-400 text-sm">✓</span>
                                        <span class="text-stone-300 font-bold text-sm medieval-font">{{ $cq->quest->name }}</span>
                                    </div>
                                    <span class="text-stone-500 text-xs font-semibold px-2 py-0.5 bg-stone-900 rounded border border-stone-800">Ukończono</span>
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
                        <span class="text-3xl">🏆</span>
                        <div>
                            <h2 class="text-2xl font-bold text-amber-300 medieval-font">Księga Osiągnięć Bohatera</h2>
                            <p class="text-xs text-amber-300/70">Wypełniaj legendarne kamienie milowe i zdobywaj unikalne tytuły oraz statystyki</p>
                        </div>
                    </div>
                    <div class="text-sm font-extrabold bg-gradient-to-r from-amber-950 to-stone-900 text-amber-300 px-5 py-2.5 rounded-xl border border-amber-600/60 shadow-inner flex items-center gap-2">
                        <span>⭐</span> Punkty Osiągnięć: <span class="text-amber-100 text-lg">{{ number_format($character->achievement_points ?? 0) }}</span>
                    </div>
                </div>

                @if(empty($achievements) || $achievements->isEmpty())
                    <div class="text-center py-12 px-4 bg-stone-950/40 rounded-xl border border-amber-900/30">
                        <div class="text-4xl mb-3">🛡️</div>
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
                                            <span>{{ $isCompleted ? '🏅' : '📜' }}</span>
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
                                            <div class="text-xs font-bold text-amber-300 bg-amber-950/80 px-2.5 py-1 rounded-lg border border-amber-700/60 shadow">🏆 {{ $achievement->reward_points }} Pkt</div>
                                        @endif
                                        @if($achievement->reward_exp > 0)
                                            <div class="text-xs font-bold text-sky-300 bg-sky-950/80 px-2.5 py-1 rounded-lg border border-sky-700/60 shadow">✨ {{ number_format($achievement->reward_exp) }} XP</div>
                                        @endif
                                        @if($achievement->reward_gold > 0)
                                            <div class="text-xs font-bold text-yellow-300 bg-yellow-950/80 px-2.5 py-1 rounded-lg border border-yellow-700/60 shadow">💰 {{ number_format($achievement->reward_gold) }} Gold</div>
                                        @endif
                                        @if($achievement->reward_title_id)
                                            <div class="text-xs font-bold text-purple-300 bg-purple-950/80 px-2.5 py-1 rounded-lg border border-purple-700/60 shadow" title="{{ $achievement->title?->name }}">👑 Tytuł</div>
                                        @endif
                                        @if($achievement->reward_item_template_id)
                                            <div class="text-xs font-bold text-emerald-300 bg-emerald-950/80 px-2.5 py-1 rounded-lg border border-emerald-700/60 shadow" title="{{ $achievement->itemTemplate?->name }}">📦 Przedmiot</div>
                                        @endif
                                        @if($achievement->stats_bonus)
                                            <div class="text-xs font-bold text-rose-300 bg-rose-950/80 px-2.5 py-1 rounded-lg border border-rose-700/60 shadow" title="Bonusowe statystyki z tego osiągnięcia">📈 Statystyki</div>
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
                                                    class="w-full px-4 py-2 bg-gradient-to-r from-emerald-600 to-green-500 hover:from-emerald-500 hover:to-green-400 text-white text-xs sm:text-sm font-extrabold rounded-xl shadow-[0_0_15px_rgba(34,197,94,0.4)] transition-all duration-200 transform hover:scale-105 active:scale-95 medieval-font uppercase tracking-wider">
                                                🎁 Odbierz Nagrodę
                                            </button>
                                        @elseif($rewarded)
                                            <div class="w-full text-center py-1.5 text-xs text-amber-400/80 font-bold uppercase tracking-widest border border-amber-900/50 rounded-xl bg-amber-950/40">
                                                ✓ Odebrano
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
    </div>

    @php
        $hasCompletedQuest = $activeQuests->contains(fn($cq) => $cq->status->value === 'completed');
    @endphp

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

