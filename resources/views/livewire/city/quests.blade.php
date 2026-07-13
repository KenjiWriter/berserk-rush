<div x-data @reward-claimed.window="let a = new Audio('/storage/sound/An_uplifting,_ascend_%232-1783933793835.mp3'); a.volume = 0.5; a.play().catch(e => console.log(e))" class="min-h-screen bg-gradient-to-b from-teal-900/90 via-emerald-800/90 to-teal-900/90 text-amber-100 relative py-8 px-4 font-sans">
    <div class="max-w-5xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-4xl font-bold text-teal-300 medieval-font drop-shadow-lg">📜 Tablica Wyzwań</h1>
            <button wire:click="backToHub" @click="$dispatch('location-leave')" class="bg-slate-700 hover:bg-slate-600 text-amber-100 px-4 py-2 rounded-lg font-bold shadow transition-colors">
                🏰 Powrót do miasta
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

        <div class="flex gap-4 mb-6">
            <button wire:click="setTab('quests')" class="px-6 py-2 rounded-lg font-bold border {{ $activeTab === 'quests' ? 'bg-teal-700 text-white border-teal-500' : 'bg-black/40 text-teal-500 border-teal-900 hover:bg-teal-900/40' }} transition-colors">
                Misje w Gildii
            </button>
            <button wire:click="setTab('achievements')" class="px-6 py-2 rounded-lg font-bold border {{ $activeTab === 'achievements' ? 'bg-amber-700 text-white border-amber-500' : 'bg-black/40 text-amber-500 border-amber-900 hover:bg-amber-900/40' }} transition-colors">
                Osiągnięcia Bohatera
            </button>
        </div>

        @if($activeTab === 'quests')
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
                                
                                <div class="flex flex-wrap gap-2 mb-4 text-xs font-bold">
                                    @if($quest->reward_gold > 0)
                                        <span class="bg-yellow-900/50 text-yellow-400 px-2 py-1 rounded border border-yellow-700/50">💰 {{ $quest->reward_gold }}</span>
                                    @endif
                                    @if($quest->reward_exp > 0)
                                        <span class="bg-blue-900/50 text-blue-400 px-2 py-1 rounded border border-blue-700/50">✨ {{ $quest->reward_exp }} XP</span>
                                    @endif
                                </div>
                                
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
        @else
            <!-- Achievements -->
            <div class="bg-black/40 border-2 border-amber-700/50 rounded-xl p-6 shadow-xl backdrop-blur-sm">
                <div class="flex items-center justify-between mb-6 border-b border-amber-700/50 pb-4">
                    <h2 class="text-2xl font-bold text-amber-400">🏆 Księga Osiągnięć</h2>
                    <div class="text-sm font-bold bg-amber-900/50 text-amber-400 px-4 py-2 rounded-lg border border-amber-700/50 shadow-inner">
                        Punkty Osiągnięć: {{ $character->achievement_points ?? 0 }}
                    </div>
                </div>

                @if(empty($achievements) || $achievements->isEmpty())
                    <p class="text-amber-500 text-center py-8 italic bg-amber-950/30 rounded-lg border border-amber-900/30">
                        Nie posiadasz jeszcze żadnych odblokowanych osiągnięć w tej kategorii.
                    </p>
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
                            
                            <div class="border {{ $isCompleted ? 'border-amber-500/50 bg-amber-900/20' : 'border-gray-700 bg-gray-800/50' }} rounded-lg p-5 transition-all hover:bg-opacity-80">
                                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-3">
                                    <div class="flex-1">
                                        <h4 class="font-bold text-white text-lg flex items-center gap-2">
                                            {{ $achievement->name }}
                                            @if($isCompleted && $rewarded) 
                                                <span class="text-amber-400 text-xs px-2 py-0.5 rounded border border-amber-400/50 bg-amber-400/10 uppercase font-bold tracking-wider">Mistrz</span> 
                                            @endif
                                        </h4>
                                        <p class="text-sm text-gray-400 mt-1">{{ $achievement->description }}</p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 text-right">
                                        @if($achievement->reward_points > 0)
                                            <div class="text-xs font-bold text-amber-300 bg-amber-900/50 px-2 py-1 rounded border border-amber-700/50">🏆 {{ $achievement->reward_points }} Pkt.</div>
                                        @endif
                                        @if($achievement->reward_exp > 0)
                                            <div class="text-xs font-bold text-blue-300 bg-blue-900/50 px-2 py-1 rounded border border-blue-700/50">✨ {{ $achievement->reward_exp }} XP</div>
                                        @endif
                                        @if($achievement->reward_gold > 0)
                                            <div class="text-xs font-bold text-yellow-300 bg-yellow-900/50 px-2 py-1 rounded border border-yellow-700/50">💰 {{ $achievement->reward_gold }} Gold</div>
                                        @endif
                                        @if($achievement->reward_title_id)
                                            <div class="text-xs font-bold text-purple-300 bg-purple-900/50 px-2 py-1 rounded border border-purple-700/50" title="{{ $achievement->title?->name }}">👑 Tytuł</div>
                                        @endif
                                        @if($achievement->reward_item_template_id)
                                            <div class="text-xs font-bold text-emerald-300 bg-emerald-900/50 px-2 py-1 rounded border border-emerald-700/50" title="{{ $achievement->itemTemplate?->name }}">📦 Przedmiot</div>
                                        @endif
                                        @if($achievement->stats_bonus)
                                            <div class="text-xs font-bold text-red-300 bg-red-900/50 px-2 py-1 rounded border border-red-700/50" title="Bonusowe statystyki z tego osiągnięcia">📈 Statystyki</div>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-4">
                                    <div class="flex-1 bg-gray-900 rounded-full h-3 border border-gray-700 shadow-inner overflow-hidden">
                                        <div class="bg-gradient-to-r from-amber-600 to-amber-400 h-full transition-all duration-500 ease-out" style="width: {{ $percent }}%"></div>
                                    </div>
                                    <div class="text-xs text-amber-500/80 font-mono w-24 text-right">
                                        {{ $progress }} / {{ $achievement->target_value }}
                                    </div>
                                    
                                    <div class="min-w-[120px]">
                                        @if($canClaim)
                                            <button wire:click="claimAchievement('{{ $ca->id }}')" class="w-full px-4 py-1.5 bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-500 hover:to-emerald-400 text-white text-sm font-bold rounded shadow transition-all transform hover:scale-105 active:scale-95">
                                                Odbierz Nagrodę
                                            </button>
                                        @elseif($rewarded)
                                            <div class="w-full text-center py-1.5 text-sm text-amber-500/70 font-semibold uppercase tracking-widest border border-amber-900/30 rounded bg-amber-900/10">
                                                Odebrano
                                            </div>
                                        @else
                                            <div class="w-full text-center py-1.5 text-sm text-gray-500 font-semibold uppercase tracking-widest">
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
</div>
