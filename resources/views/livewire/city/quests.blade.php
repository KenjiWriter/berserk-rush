<div class="min-h-screen bg-gradient-to-b from-teal-900/90 via-emerald-800/90 to-teal-900/90 text-amber-100 relative py-8 px-4 font-sans">
    <div class="max-w-5xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-4xl font-bold text-teal-300 medieval-font drop-shadow-lg">📜 Tablica Wyzwań</h1>
            <button wire:click="backToHub" class="bg-slate-700 hover:bg-slate-600 text-amber-100 px-4 py-2 rounded-lg font-bold shadow transition-colors">
                Powrót do miasta
            </button>
        </div>

        @if(session()->has('message'))
            <div class="bg-green-600/80 border border-green-400 text-white px-4 py-3 rounded mb-6 backdrop-blur-sm">
                {{ session('message') }}
            </div>
        @endif
        @if(session()->has('error'))
            <div class="bg-red-600/80 border border-red-400 text-white px-4 py-3 rounded mb-6 backdrop-blur-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            {{-- Available Quests --}}
            <div class="bg-black/40 border-2 border-teal-700/50 rounded-xl p-6 shadow-xl backdrop-blur-sm">
                <h2 class="text-2xl font-bold text-teal-400 mb-6 border-b border-teal-700/50 pb-2">Dostępne Misje</h2>
                
                @forelse($availableQuests as $quest)
                    <div class="bg-teal-900/40 border border-teal-600/30 rounded-lg p-4 mb-4 hover:border-teal-500/50 transition-colors">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-xl font-bold text-teal-200">{{ $quest->name }}</h3>
                            <span class="text-xs bg-teal-800 text-teal-100 px-2 py-1 rounded">Lvl {{ $quest->required_level }}</span>
                        </div>
                        <p class="text-sm text-teal-100/80 mb-3">{{ $quest->description }}</p>
                        
                        <div class="text-sm text-white bg-teal-950/60 p-2 rounded mb-3 border border-teal-700/50 font-semibold shadow-inner">
                            🎯 Cel: {{ $this->getQuestRequirement($quest) }}
                        </div>

                        @if($hint = $this->getQuestHint($quest))
                            <div class="text-xs text-teal-300 bg-teal-900/60 p-2 rounded mb-3 border border-teal-700/50">
                                💡 {{ $hint }}
                            </div>
                        @endif

                        <div class="flex flex-wrap gap-2 mb-4 text-xs font-bold">
                            @if($quest->reward_gold > 0)
                                <span class="bg-yellow-900/50 text-yellow-400 px-2 py-1 rounded border border-yellow-700/50">💰 {{ $quest->reward_gold }}</span>
                            @endif
                            @if($quest->reward_exp > 0)
                                <span class="bg-blue-900/50 text-blue-400 px-2 py-1 rounded border border-blue-700/50">✨ {{ $quest->reward_exp }} XP</span>
                            @endif
                        </div>

                        <button wire:click="acceptQuest('{{ $quest->id }}')" class="w-full bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-500 hover:to-emerald-500 text-white font-bold py-2 rounded shadow transition-colors">
                            Przyjmij Wyzwanie
                        </button>
                    </div>
                @empty
                    <p class="text-center text-teal-500 italic py-8">Brak dostępnych misji na Twoim poziomie.</p>
                @endforelse
            </div>

            {{-- Active & Completed Quests --}}
            <div class="space-y-8">
                <div class="bg-black/40 border-2 border-blue-700/50 rounded-xl p-6 shadow-xl backdrop-blur-sm">
                    <h2 class="text-2xl font-bold text-blue-400 mb-6 border-b border-blue-700/50 pb-2">Trwające Misje</h2>
                    
                    @forelse($activeQuests as $cq)
                        @php $quest = $cq->quest; @endphp
                        <div class="bg-blue-900/40 border border-blue-600/30 rounded-lg p-4 mb-4 hover:border-blue-500/50 transition-colors">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="text-xl font-bold text-blue-200">{{ $quest->name }}</h3>
                                @if($cq->status->value === 'completed')
                                    <span class="text-xs bg-green-800 text-green-100 px-2 py-1 rounded shadow">Gotowe</span>
                                @else
                                    <span class="text-xs bg-blue-800 text-blue-100 px-2 py-1 rounded border border-blue-500/50">{{ $cq->progress }} / {{ $quest->target_amount }}</span>
                                @endif
                            </div>
                            <p class="text-sm text-blue-100/80 mb-3">{{ $quest->description }}</p>
                            
                            <div class="text-sm text-white bg-blue-950/60 p-2 rounded mb-3 border border-blue-700/50 font-semibold shadow-inner">
                                🎯 Cel: {{ $this->getQuestRequirement($quest) }}
                            </div>

                            @if($hint = $this->getQuestHint($quest))
                                <div class="text-xs text-blue-300 bg-blue-900/60 p-2 rounded mb-3 border border-blue-700/50">
                                    💡 {{ $hint }}
                                </div>
                            @endif
                            
                            @if($cq->status->value === 'completed' || $quest->type->value === 'gathering')
                                <button wire:click="claimReward('{{ $cq->id }}')" class="w-full bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-white font-bold py-2 rounded shadow transition-colors">
                                    Odbierz Nagrodę
                                </button>
                            @else
                                <div class="w-full bg-blue-950 rounded-full h-2 mt-2">
                                    <div class="bg-blue-500 h-2 rounded-full" style="width: {{ min(100, ($cq->progress / max(1, $quest->target_amount)) * 100) }}%"></div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-center text-blue-500 italic py-4">Obecnie nie wykonujesz żadnych misji.</p>
                    @endforelse
                </div>

                <div class="bg-black/40 border-2 border-slate-700/50 rounded-xl p-6 shadow-xl backdrop-blur-sm">
                    <h2 class="text-2xl font-bold text-slate-400 mb-6 border-b border-slate-700/50 pb-2">Odebrane Wyzwania</h2>
                    <div class="max-h-64 overflow-y-auto pr-2 space-y-2 custom-scrollbar">
                        @forelse($completedQuests as $cq)
                            <div class="bg-slate-800/40 border border-slate-700/50 rounded p-3 flex justify-between items-center">
                                <span class="text-slate-300 font-bold">{{ $cq->quest->name }}</span>
                                <span class="text-slate-500 text-xs">Zakończone</span>
                            </div>
                        @empty
                            <p class="text-center text-slate-500 italic py-2">Historia wyzwań jest pusta.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
