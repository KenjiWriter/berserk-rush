<div class="min-h-screen bg-gradient-to-b from-purple-900 via-indigo-950 to-black text-amber-100 relative overflow-hidden font-sans">
    @php
        $gameStage = auth()->user()->game_stage;
    @endphp

    @if($gameStage == 31)
        <livewire:global.tutorial-overlay :step="32" />
    @elseif($gameStage == 33)
        <livewire:global.tutorial-overlay :step="34" :rewardXp="200" :rewardGold="250" />
    @endif

    {{-- Background particles / magic fog --}}
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-purple-900/40 via-transparent to-transparent opacity-50"></div>
    
    <div class="relative w-full px-6 md:px-10 lg:px-12 py-6 min-h-screen flex flex-col">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div class="bg-indigo-900/50 border border-purple-500/50 rounded-lg p-3 shadow-lg backdrop-blur-md">
                <h2 class="text-2xl font-bold text-purple-300 medieval-font flex items-center gap-2 drop-shadow-[0_0_10px_rgba(168,85,247,0.8)]">
                    <span class="text-3xl">🔮</span> Stół do Zaklinania
                </h2>
            </div>

            <div class="flex items-center gap-4">
                <div class="bg-black/60 border border-yellow-600/50 rounded-full px-5 py-2 font-bold text-yellow-400 flex gap-4 backdrop-blur-sm shadow-[0_0_15px_rgba(202,138,4,0.2)]">
                    <span class="flex items-center gap-1">🪙 {{ $character->gold }}</span>
                    <span class="flex items-center gap-1 text-blue-300">💎 {{ auth()->user()->gems ?? 0 }}</span>
                </div>
                <button wire:click="backToHub" @click="$dispatch('location-leave')"
                    class="bg-indigo-950/80 hover:bg-indigo-800 border border-indigo-500/50 text-indigo-200 font-bold py-2 px-5 rounded-full transition-all duration-300 transform hover:scale-105 shadow-[0_0_15px_rgba(99,102,241,0.3)] hover:shadow-[0_0_25px_rgba(99,102,241,0.6)] medieval-font">
                    🏰 Powrót
                </button>
            </div>
        </div>

        {{-- Main Interface --}}
        <div class="flex flex-col lg:flex-row gap-8 flex-1">
            
            {{-- ENCHANTING TABLE (CENTER) --}}
            <div class="lg:w-2/3 flex flex-col items-center justify-center relative min-h-[500px] border border-purple-800/30 rounded-2xl bg-black/40 backdrop-blur-sm p-8 shadow-2xl">
                
                {{-- Action Messages --}}
                @if($actionMessage)
                    <div class="absolute top-4 left-1/2 -translate-x-1/2 w-3/4 text-center p-3 rounded-lg border backdrop-blur-md z-50 animate-fade-in-down {{ $actionType === 'success' ? 'bg-green-900/80 border-green-500 text-green-200 shadow-[0_0_20px_rgba(34,197,94,0.5)]' : 'bg-red-900/80 border-red-500 text-red-200 shadow-[0_0_20px_rgba(239,68,68,0.5)]' }}">
                        <p class="font-bold text-lg">{{ $actionMessage }}</p>
                    </div>
                @endif

                @if($activeItem)
                    @php
                        $enchants = $activeItem->roll_stats['enchants'] ?? [];
                        $enchantCount = count($enchants);
                        $nextChance = [75, 50, 40, 30, 20][$enchantCount] ?? 0;
                        $rerollGoldCost = max(200, $enchantCount * 200);
                        $rerollGemCost = max(2, $enchantCount * 2);
                    @endphp

                    {{-- Magical Runes Ring --}}
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[350px] h-[350px] rounded-full border-2 border-dashed border-purple-500/30 animate-[spin_20s_linear_infinite] pointer-events-none"></div>
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[450px] h-[450px] rounded-full border border-indigo-500/20 animate-[spin_30s_linear_infinite_reverse] pointer-events-none">
                        <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 text-purple-400/50 text-2xl medieval-font blur-[1px]">ᚠ</div>
                        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2 text-purple-400/50 text-2xl medieval-font blur-[1px]">ᛞ</div>
                        <div class="absolute left-0 top-1/2 -translate-x-1/2 -translate-y-1/2 text-purple-400/50 text-2xl medieval-font blur-[1px]">ᚱ</div>
                        <div class="absolute right-0 top-1/2 translate-x-1/2 -translate-y-1/2 text-purple-400/50 text-2xl medieval-font blur-[1px]">ᚷ</div>
                    </div>

                    {{-- Item on Table --}}
                    <div class="relative z-10 flex flex-col items-center">
                        <div class="relative group cursor-pointer" wire:click="deselectItem">
                            {{-- Glow behind item based on enchants --}}
                            <div class="absolute inset-0 bg-purple-600 rounded-full blur-[40px] opacity-{{ min(100, max(20, $enchantCount * 25)) }} transition-all duration-500"></div>
                            
                            @if($actionType === 'success')
                                {{-- Epic particle burst --}}
                                <div class="absolute inset-0 z-20 pointer-events-none flex items-center justify-center">
                                    <div class="absolute w-32 h-32 rounded-full border-4 border-yellow-300 shadow-[0_0_50px_rgba(253,224,71,1)] animate-[ping-fast_0.5s_ease-out_forwards]"></div>
                                    <div class="absolute w-full h-full bg-yellow-300/30 blur-2xl rounded-full animate-[fade-out-slow_1.5s_ease-out_forwards]"></div>
                                    
                                    {{-- Floating stars --}}
                                    <div class="absolute text-yellow-300 text-2xl animate-[float-up-left_1s_ease-out_forwards]">✨</div>
                                    <div class="absolute text-yellow-200 text-xl animate-[float-up-right_1.2s_ease-out_forwards]">⭐</div>
                                    <div class="absolute text-white text-3xl animate-[float-down-left_0.9s_ease-out_forwards]">✨</div>
                                    <div class="absolute text-yellow-400 text-xl animate-[float-down-right_1.1s_ease-out_forwards]">⭐</div>
                                </div>
                            @endif

                            @if($activeItem->template->icon)
                                <img src="{{ route('assets.items', ['filename' => $activeItem->template->icon]) }}" 
                                     class="w-32 h-32 object-contain relative z-10 drop-shadow-[0_0_15px_rgba(168,85,247,0.8)] {{ $actionType === 'success' ? 'animate-[epic-success_1s_ease-out]' : 'animate-bounce-slow' }}" 
                                     alt="{{ $activeItem->template->name }}">
                            @else
                                <div class="w-32 h-32 bg-gray-800 rounded-lg flex items-center justify-center border-2 border-purple-500 shadow-[0_0_20px_rgba(168,85,247,0.5)] relative z-10 {{ $actionType === 'success' ? 'animate-[epic-success_1s_ease-out]' : 'animate-bounce-slow' }}">
                                    <span class="text-4xl">❓</span>
                                </div>
                            @endif
                            <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 bg-black/80 px-3 py-1 rounded-full text-xs text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                Kliknij, aby odłożyć
                            </div>
                        </div>

                        <h3 class="mt-8 font-bold text-2xl text-purple-200 drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)] medieval-font text-center">
                            {{ $activeItem->template->name }}
                            @if($activeItem->upgrade_level > 0)<span class="text-yellow-400">+{{ $activeItem->upgrade_level }}</span>@endif
                        </h3>

                        {{-- Current Enchants Display --}}
                        <div class="mt-4 bg-indigo-950/60 border border-purple-500/40 rounded-xl p-4 min-w-[300px] shadow-[inset_0_0_20px_rgba(0,0,0,0.5)] backdrop-blur-md">
                            <div class="text-purple-300 font-bold mb-2 text-center border-b border-purple-500/30 pb-2 flex items-center justify-center gap-2">
                                <span>✦</span> Magiczne Moce ({{ $enchantCount }}/5) <span>✦</span>
                            </div>
                            @if($enchantCount > 0)
                                <div class="space-y-2">
                                    @foreach($enchants as $bonusType => $bonusValue)
                                        <div class="flex justify-between items-center bg-black/40 rounded px-3 py-1.5 border border-purple-900/50">
                                            <span class="capitalize text-indigo-200">{{ str_replace('_', ' ', $bonusType) }}</span>
                                            <span class="font-bold text-yellow-300 text-lg drop-shadow-[0_0_5px_rgba(253,224,71,0.5)]">+{{ $bonusValue }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-gray-500 text-center italic py-4">Przedmiot czeka na nasycenie magią...</div>
                            @endif
                        </div>

                        {{-- Action Buttons --}}
                        <div class="mt-8 flex flex-col gap-4 w-full max-w-md relative z-20">
                            @if($enchantCount < 5)
                                <div class="text-center text-sm text-purple-300 mb-1">Szansa na sukces: <span class="font-bold text-white">{{ $nextChance }}%</span></div>
                                <div class="grid grid-cols-2 gap-4 {{ $gameStage == 32 ? 'ring-4 ring-amber-500 animate-pulse rounded-xl p-1 z-30' : '' }}">
                                    <button wire:click="enchant('gold')" wire:loading.attr="disabled" class="group relative overflow-hidden bg-gradient-to-br from-yellow-700 to-yellow-900 hover:from-yellow-600 hover:to-yellow-800 text-white font-bold py-3 px-4 rounded-xl shadow-[0_0_15px_rgba(202,138,4,0.4)] transition-all transform hover:scale-105 border border-yellow-500/50">
                                        <div class="absolute inset-0 bg-white/20 group-hover:translate-x-full -translate-x-full skew-x-12 transition-transform duration-700"></div>
                                        <span wire:loading.remove wire:target="enchant">Zaklnij (🪙 500)</span>
                                        <span wire:loading wire:target="enchant" class="animate-pulse">Zaklinanie...</span>
                                    </button>
                                    <button wire:click="enchant('gems')" wire:loading.attr="disabled" class="group relative overflow-hidden bg-gradient-to-br from-blue-700 to-blue-900 hover:from-blue-600 hover:to-blue-800 text-white font-bold py-3 px-4 rounded-xl shadow-[0_0_15px_rgba(59,130,246,0.4)] transition-all transform hover:scale-105 border border-blue-500/50">
                                        <div class="absolute inset-0 bg-white/20 group-hover:translate-x-full -translate-x-full skew-x-12 transition-transform duration-700"></div>
                                        <span wire:loading.remove wire:target="enchant">Zaklnij (💎 5)</span>
                                        <span wire:loading wire:target="enchant" class="animate-pulse">Zaklinanie...</span>
                                    </button>
                                </div>
                            @else
                                <div class="bg-purple-900/50 border border-purple-500/50 text-purple-200 font-bold py-3 px-4 rounded-xl text-center shadow-inner">
                                    Przedmiot osiągnął maksymalną moc.
                                </div>
                            @endif

                            @if($enchantCount > 0)
                                <div class="mt-4 pt-4 border-t border-purple-800/50">
                                    <div class="text-center text-sm text-gray-400 mb-2">Przelosuj wszystkie bonusy od nowa:</div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <button wire:click="reroll('gold')" wire:loading.attr="disabled" class="bg-slate-800 hover:bg-slate-700 text-yellow-500 border border-slate-600 hover:border-yellow-600/50 font-bold py-2 px-4 rounded-lg transition-colors text-sm shadow-md">
                                            <span wire:loading.remove wire:target="reroll">Reroll (🪙 {{ $rerollGoldCost }})</span>
                                            <span wire:loading wire:target="reroll">Przelosowywanie...</span>
                                        </button>
                                        <button wire:click="reroll('gems')" wire:loading.attr="disabled" class="bg-slate-800 hover:bg-slate-700 text-blue-400 border border-slate-600 hover:border-blue-600/50 font-bold py-2 px-4 rounded-lg transition-colors text-sm shadow-md">
                                            <span wire:loading.remove wire:target="reroll">Reroll (💎 {{ $rerollGemCost }})</span>
                                            <span wire:loading wire:target="reroll">Przelosowywanie...</span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    {{-- Empty State --}}
                    <div class="flex flex-col items-center justify-center text-center opacity-70 animate-pulse">
                        <div class="w-32 h-32 rounded-full border-2 border-dashed border-purple-500/50 flex items-center justify-center mb-6 shadow-[0_0_30px_rgba(168,85,247,0.2)]">
                            <span class="text-5xl text-purple-400">✨</span>
                        </div>
                        <h3 class="text-2xl font-bold text-purple-300 medieval-font mb-2">Stół do Zaklinania</h3>
                        <p class="text-indigo-200 max-w-sm">Wybierz przedmiot ze swojego ekwipunku, aby nasycić go starożytną magią.</p>
                    </div>
                @endif
            </div>

            {{-- INVENTORY / EQUIPPED ITEMS (SIDEBAR) --}}
            <div class="lg:w-1/3 flex flex-col bg-black/40 border border-indigo-900/50 rounded-2xl p-4 backdrop-blur-sm max-h-[800px]">
                <div class="mb-4 pb-2 border-b border-indigo-900/50">
                    <h3 class="font-bold text-lg text-indigo-300 flex items-center gap-2">
                        <span>🎒</span> Twój Ekwipunek
                    </h3>
                    <p class="text-xs text-gray-400 mt-1">Kliknij przedmiot, aby położyć go na stole.</p>
                </div>

                <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar space-y-3">
                    @forelse($enchantableItems as $item)
                        @php
                            $enchantsCount = count($item->roll_stats['enchants'] ?? []);
                            $isActive = $activeItemId === $item->id;
                        @endphp
                        <div wire:click="selectItemToEnchant('{{ $item->id }}')" 
                             class="relative flex items-center p-3 rounded-xl border transition-all cursor-pointer overflow-hidden group
                                    {{ $isActive ? 'bg-purple-900/40 border-purple-500 shadow-[0_0_15px_rgba(168,85,247,0.4)]' : 'bg-gray-900/60 border-gray-700 hover:border-purple-500/50 hover:bg-gray-800/80' }}">
                            
                            @if($isActive)
                                <div class="absolute inset-0 bg-gradient-to-r from-purple-600/20 to-transparent"></div>
                            @endif

                            @if($item->location === 'equipped')
                                <div class="absolute top-0 right-0 bg-blue-900/80 text-blue-200 text-[9px] font-bold px-1.5 py-0.5 rounded-bl-lg">
                                    Założone
                                </div>
                            @endif
                            
                            <div class="w-12 h-12 flex-shrink-0 bg-black/50 rounded-lg p-1 border border-gray-700 mr-3 relative">
                                @if($item->template->icon)
                                    <img src="{{ route('assets.items', ['filename' => $item->template->icon]) }}" class="w-full h-full object-contain" alt="">
                                @endif
                                @if($enchantsCount > 0)
                                    <div class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-purple-600 border border-purple-300 flex items-center justify-center text-[9px] font-bold text-white shadow-[0_0_5px_rgba(168,85,247,1)]">
                                        {{ $enchantsCount }}
                                    </div>
                                @endif
                                @if($item->stack_size > 1)
                                    <span class="absolute top-0 -left-1 text-white font-bold text-[9px] bg-black/80 px-1 py-0.5 rounded border border-gray-600 shadow">x{{ $item->stack_size }}</span>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0 z-10">
                                <div class="font-bold text-sm text-gray-200 truncate group-hover:text-purple-300 transition-colors">
                                    {{ $item->template->name }}
                                    @if($item->upgrade_level > 0)<span class="text-yellow-500">+{{ $item->upgrade_level }}</span>@endif
                                </div>
                                <div class="text-xs text-gray-500 capitalize">{{ $item->template->type }}</div>
                            </div>

                            @if($isActive)
                                <div class="ml-2 text-purple-400 animate-pulse">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500">
                            Brak pasujących przedmiotów.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&display=swap');
        .medieval-font { font-family: 'Cinzel', serif; }
        
        .animate-bounce-slow {
            animation: bounce-slow 4s infinite ease-in-out;
        }
        @keyframes bounce-slow {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .animate-fade-in-down {
            animation: fade-in-down 0.5s ease-out forwards;
        }
        @keyframes fade-in-down {
            0% { opacity: 0; transform: translate(-50%, -20px); }
            100% { opacity: 1; transform: translate(-50%, 0); }
        }

        /* Epic success animations */
        @keyframes epic-success {
            0% { transform: scale(1) rotate(0deg); filter: brightness(1) drop-shadow(0 0 15px rgba(168,85,247,0.8)); }
            20% { transform: scale(1.4) rotate(5deg); filter: brightness(2) drop-shadow(0 0 50px rgba(253,224,71,1)); }
            40% { transform: scale(1.1) rotate(-5deg); }
            60% { transform: scale(1.2) rotate(2deg); filter: brightness(1.5) drop-shadow(0 0 30px rgba(253,224,71,0.8)); }
            100% { transform: scale(1) rotate(0deg); filter: brightness(1) drop-shadow(0 0 15px rgba(168,85,247,0.8)); }
        }
        @keyframes ping-fast {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(3); opacity: 0; }
        }
        @keyframes fade-out-slow {
            0% { opacity: 1; transform: scale(0.5); }
            100% { opacity: 0; transform: scale(2); }
        }
        @keyframes float-up-left {
            0% { transform: translate(0, 0) scale(0.5); opacity: 1; }
            100% { transform: translate(-100px, -100px) scale(1.5) rotate(-45deg); opacity: 0; }
        }
        @keyframes float-up-right {
            0% { transform: translate(0, 0) scale(0.5); opacity: 1; }
            100% { transform: translate(100px, -120px) scale(1.2) rotate(45deg); opacity: 0; }
        }
        @keyframes float-down-left {
            0% { transform: translate(0, 0) scale(0.8); opacity: 1; }
            100% { transform: translate(-120px, 80px) scale(1.5) rotate(-30deg); opacity: 0; }
        }
        @keyframes float-down-right {
            0% { transform: translate(0, 0) scale(1); opacity: 1; }
            100% { transform: translate(110px, 90px) scale(0.8) rotate(60deg); opacity: 0; }
        }

        /* Custom Scrollbar for inventory */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(99, 102, 241, 0.5);
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(168, 85, 247, 0.8);
        }
    </style>
</div>
