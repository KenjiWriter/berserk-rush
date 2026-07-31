<div>
    {{-- Main Container --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mt-2">

        {{-- Left: Inkubator Chamber (4 cols) --}}
        <div class="lg:col-span-5 xl:col-span-4 space-y-4">
            <div class="bg-gradient-to-b from-stone-900/95 via-slate-900/90 to-stone-950/95 border-2 border-amber-500/40 rounded-2xl p-5 shadow-[0_0_25px_rgba(0,0,0,0.8)] backdrop-blur-md relative overflow-hidden">
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-amber-500/10 via-transparent to-transparent pointer-events-none"></div>

                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-amber-500/20 pb-3 mb-4">
                    <h3 class="text-sm font-bold text-amber-200 uppercase tracking-wider flex items-center gap-2" style="font-family: 'Cinzel', serif;">
                        <i class="fa-solid fa-egg text-amber-400 text-lg drop-shadow-[0_0_8px_rgba(245,158,11,0.6)]"></i>
                        Inkubator
                    </h3>
                    <span class="text-[10px] uppercase tracking-widest px-2 py-0.5 rounded-full font-bold border {{ ($incubator && !$incubator->is_hatched && $incubator->egg_item_instance_id) ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 shadow-[0_0_10px_rgba(245,158,11,0.2)]' : 'bg-stone-800 text-stone-400 border-stone-700' }}">
                        {{ ($incubator && !$incubator->is_hatched && $incubator->egg_item_instance_id) ? 'Inkubacja' : 'Wolny' }}
                    </span>
                </div>

                @if($incubator && !$incubator->is_hatched && $incubator->egg_item_instance_id)
                    @php
                        $eggRarity = $incubator->getEffectiveRarity();
                        $rarityColorClass = match($eggRarity) {
                            'legendary' => 'from-amber-500/30 to-yellow-600/10 border-amber-400 text-yellow-300 shadow-[0_0_30px_rgba(245,158,11,0.4)]',
                            'epic' => 'from-purple-900/40 to-indigo-950/30 border-purple-500 text-purple-300 shadow-[0_0_30px_rgba(168,85,247,0.4)]',
                            'rare' => 'from-cyan-900/40 to-blue-950/30 border-cyan-400 text-cyan-300 shadow-[0_0_30px_rgba(6,182,212,0.4)]',
                            'uncommon' => 'from-emerald-900/40 to-green-950/30 border-emerald-400 text-emerald-300 shadow-[0_0_30px_rgba(16,185,129,0.4)]',
                            default => 'from-stone-800/60 to-stone-900/60 border-stone-600 text-stone-300 shadow-[0_0_15px_rgba(0,0,0,0.5)]',
                        };

                        $rarityLabel = match($eggRarity) {
                            'common' => 'Zwykłe',
                            'uncommon' => 'Nietypowe',
                            'rare' => 'Rzadkie',
                            'epic' => 'Epickie',
                            'legendary' => 'Legendarne',
                            default => ucfirst($eggRarity),
                        };

                        $progress = $incubator->getProgress();
                        $isReady = $incubator->isReady();
                        $timeRemaining = $isReady ? null : ($incubator->hatches_at ? $incubator->hatches_at->diffForHumans() : null);
                    @endphp

                    {{-- Egg incubating active layout --}}
                    <div class="text-center py-3 flex flex-col items-center">

                        {{-- Pedestal & Animated Egg --}}
                        <div class="relative w-28 h-28 flex items-center justify-center mb-4">
                            {{-- Ambient Magic Glow Ring --}}
                            <div class="absolute inset-0 rounded-full bg-gradient-to-tr {{ $rarityColorClass }} animate-pulse"></div>
                            <div class="absolute inset-2 rounded-full border border-amber-500/30 bg-stone-950/80 flex items-center justify-center shadow-inner">
                                <i class="fa-solid fa-egg text-5xl {{ $eggRarity === 'legendary' ? 'text-yellow-400 drop-shadow-[0_0_15px_rgba(234,179,8,0.8)]' : ($eggRarity === 'epic' ? 'text-purple-400 drop-shadow-[0_0_15px_rgba(168,85,247,0.8)]' : ($eggRarity === 'rare' ? 'text-cyan-400 drop-shadow-[0_0_15px_rgba(6,182,212,0.8)]' : ($eggRarity === 'uncommon' ? 'text-emerald-400 drop-shadow-[0_0_15px_rgba(16,185,129,0.8)]' : 'text-amber-200 drop-shadow-[0_0_10px_rgba(245,158,11,0.5)]'))) }} {{ $isReady ? 'animate-bounce' : 'animate-pulse' }}"></i>
                            </div>
                        </div>

                        <h4 class="text-lg font-bold text-amber-100 mb-1 tracking-wide" style="font-family: 'Cinzel', serif;">
                            Inkubacja w toku
                        </h4>

                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border text-xs font-bold mb-4 bg-stone-900/80 border-amber-500/30">
                            <span class="text-stone-400">Rzadkość:</span>
                            <span class="font-extrabold uppercase tracking-wider {{ $eggRarity === 'legendary' ? 'text-yellow-400' : ($eggRarity === 'epic' ? 'text-purple-400' : ($eggRarity === 'rare' ? 'text-cyan-400' : ($eggRarity === 'uncommon' ? 'text-emerald-400' : 'text-stone-300'))) }}">
                                {{ $rarityLabel }}
                            </span>
                        </div>

                        {{-- Progress Bar Container --}}
                        <div class="w-full bg-stone-950 p-3 rounded-xl border border-stone-800 mb-4 shadow-inner">
                            <div class="flex justify-between items-center text-xs mb-1.5 font-semibold">
                                <span class="text-stone-400">Postęp</span>
                                <span class="text-amber-300 font-bold">{{ number_format(min(100, $progress), 1) }}%</span>
                            </div>

                            <div class="w-full bg-stone-900 rounded-full h-3.5 border border-stone-700 overflow-hidden relative shadow-inner">
                                <div class="h-full rounded-full transition-all duration-1000 relative {{ $isReady ? 'bg-gradient-to-r from-emerald-600 via-green-500 to-emerald-400 shadow-[0_0_12px_rgba(16,185,129,0.6)]' : 'bg-gradient-to-r from-amber-700 via-amber-500 to-yellow-400 shadow-[0_0_12px_rgba(245,158,11,0.6)]' }}"
                                     style="width: {{ min(100, $progress) }}%">
                                    <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                                </div>
                            </div>

                            <div class="text-xs text-stone-400 mt-2 flex items-center justify-center gap-1.5 font-medium">
                                @if($isReady)
                                    <span class="text-emerald-400 font-bold flex items-center gap-1"><i class="fa-solid fa-circle-check"></i> Jajko gotowe do wyklucia!</span>
                                @else
                                    <span class="text-amber-300/90 flex items-center gap-1"><i class="fa-solid fa-hourglass-half text-amber-400 animate-spin"></i> {{ $timeRemaining }}</span>
                                @endif
                            </div>
                        </div>

                        @if($isReady)
                            <button wire:click="hatchEgg"
                                class="w-full bg-gradient-to-r from-emerald-600 via-green-600 to-emerald-700 hover:from-emerald-500 hover:to-green-500 text-white font-bold py-3 px-6 rounded-xl transition-all duration-200 transform hover:scale-[1.02] shadow-[0_0_20px_rgba(16,185,129,0.4)] border border-emerald-400/50 flex items-center justify-center gap-2 text-base tracking-wider"
                                style="font-family: 'Cinzel', serif;"
                                wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed">
                                <span wire:loading.remove wire:target="hatchEgg" class="flex items-center gap-2">
                                    <i class="fa-solid fa-wand-magic-sparkles text-amber-300"></i> Wykluj Chowańca!
                                </span>
                                <span wire:loading wire:target="hatchEgg" class="flex items-center gap-2">
                                    <i class="fa-solid fa-spinner animate-spin"></i> Wykluwanie...
                                </span>
                            </button>
                        @endif
                    </div>
                @else
                    {{-- Incubator is Empty --}}
                    <div class="text-center py-4">
                        <div class="w-20 h-20 mx-auto rounded-full border-2 border-dashed border-amber-500/30 bg-stone-950/60 flex items-center justify-center mb-3">
                            <i class="fa-solid fa-egg text-3xl text-amber-500/40"></i>
                        </div>
                        <h4 class="text-sm font-bold text-amber-200/90 mb-1" style="font-family: 'Cinzel', serif;">Komnata Inkubacji jest pusta</h4>
                        <p class="text-xs text-stone-400 mb-4">Wybierz jajko z plecaka, aby rozpocząć proces inkubacji.</p>

                        @if($eggs->count() > 0)
                            <div class="text-left border-t border-amber-500/20 pt-3">
                                <p class="text-xs font-bold text-amber-300/80 mb-2 uppercase tracking-wider">Jaja w plecaku:</p>
                                <div class="space-y-2 max-h-60 overflow-y-auto pr-1 custom-scrollbar">
                                    @foreach($eggs as $egg)
                                        @php
                                            $eggRarity = $egg->getEggRarity();
                                            $badgeColor = match($eggRarity) {
                                                'legendary' => 'text-yellow-400 border-yellow-500/40 bg-yellow-950/30',
                                                'epic' => 'text-purple-400 border-purple-500/40 bg-purple-950/30',
                                                'rare' => 'text-cyan-400 border-cyan-500/40 bg-cyan-950/30',
                                                'uncommon' => 'text-emerald-400 border-emerald-500/40 bg-emerald-950/30',
                                                default => 'text-stone-300 border-stone-700 bg-stone-900/60',
                                            };
                                            $rarityLabel = match($eggRarity) {
                                                'common' => 'Zwykłe',
                                                'uncommon' => 'Nietypowe',
                                                'rare' => 'Rzadkie',
                                                'epic' => 'Epickie',
                                                'legendary' => 'Legendarne',
                                                default => ucfirst($eggRarity),
                                            };
                                        @endphp
                                        <div class="flex items-center justify-between bg-stone-950/70 rounded-xl p-2.5 border border-stone-800 hover:border-amber-500/40 transition">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-9 h-9 rounded-lg bg-stone-900 border border-stone-700 flex items-center justify-center p-1 relative">
                                                    @if($egg->template->icon)
                                                        <img src="{{ route('assets.items', ['filename' => $egg->template->icon]) }}" class="w-full h-full object-contain drop-shadow" alt="{{ $egg->template->name }}">
                                                    @else
                                                        <i class="fa-solid fa-egg text-amber-400"></i>
                                                    @endif
                                                </div>
                                                <div class="text-left">
                                                    <p class="text-xs font-bold text-stone-200 leading-tight">{{ $egg->template->name }}</p>
                                                    <div class="flex items-center gap-1.5 mt-0.5">
                                                        <span class="text-[10px] font-extrabold uppercase px-1.5 py-0.2 rounded border {{ $badgeColor }}">
                                                            {{ $rarityLabel }}
                                                        </span>
                                                        @if($egg->stack_size > 1)
                                                            <span class="text-[10px] text-stone-400 font-semibold">x{{ $egg->stack_size }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <button wire:click="placeEgg('{{ $egg->id }}')"
                                                class="bg-gradient-to-r from-amber-700 to-amber-600 hover:from-amber-600 hover:to-amber-500 text-white text-xs font-bold py-1.5 px-3 rounded-lg border border-amber-500/50 transition-all shadow-md flex items-center gap-1"
                                                wire:loading.attr="disabled" wire:target="placeEgg('{{ $egg->id }}')">
                                                <span wire:loading.remove wire:target="placeEgg('{{ $egg->id }}')" class="flex items-center gap-1">
                                                    <i class="fa-solid fa-arrow-up-from-bracket text-[10px]"></i> Umieść
                                                </span>
                                                <span wire:loading wire:target="placeEgg('{{ $egg->id }}')">
                                                    <i class="fa-solid fa-spinner animate-spin"></i>
                                                </span>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="p-3 bg-stone-950/60 rounded-xl border border-stone-800 text-stone-500 text-xs mt-2">
                                <i class="fa-solid fa-circle-info mr-1 text-amber-500/60"></i> Brak jajek w plecaku. Zdobywaj je pokonując bossów i przemierzając lochy!
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Right: Pets List / Companion Roster (8 cols) --}}
        <div class="lg:col-span-7 xl:col-span-8 space-y-4">
            <div class="bg-gradient-to-b from-stone-900/95 via-slate-900/90 to-stone-950/95 border-2 border-amber-500/40 rounded-2xl p-5 shadow-[0_0_25px_rgba(0,0,0,0.8)] backdrop-blur-md relative">
                
                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-amber-500/20 pb-3 mb-4">
                    <h3 class="text-sm font-bold text-amber-200 uppercase tracking-wider flex items-center gap-2" style="font-family: 'Cinzel', serif;">
                        <i class="fa-solid fa-paw text-amber-400 text-lg drop-shadow-[0_0_8px_rgba(245,158,11,0.6)]"></i>
                        Menażeria Chowańców
                    </h3>
                    <span class="bg-stone-900 text-amber-300 border border-amber-500/30 text-xs font-bold px-2.5 py-0.5 rounded-full">
                        Posiadane: {{ $pets->count() }}
                    </span>
                </div>

                @if($pets->count() > 0)
                    <div class="space-y-3">
                        @foreach($pets as $pet)
                            @php
                                $rarityBorder = match($pet->rarity) {
                                    'legendary' => 'border-amber-400 shadow-[0_0_20px_rgba(245,158,11,0.3)] bg-gradient-to-r from-amber-950/40 via-stone-900/70 to-stone-950/90',
                                    'epic' => 'border-purple-500 shadow-[0_0_20px_rgba(168,85,247,0.25)] bg-gradient-to-r from-purple-950/40 via-stone-900/70 to-stone-950/90',
                                    'rare' => 'border-cyan-500 shadow-[0_0_20px_rgba(6,182,212,0.25)] bg-gradient-to-r from-cyan-950/40 via-stone-900/70 to-stone-950/90',
                                    'uncommon' => 'border-emerald-500 shadow-[0_0_20px_rgba(16,185,129,0.25)] bg-gradient-to-r from-emerald-950/40 via-stone-900/70 to-stone-950/90',
                                    default => 'border-stone-700 bg-stone-900/80',
                                };

                                $rarityBadge = match($pet->rarity) {
                                    'legendary' => 'text-yellow-400 border-yellow-500/50 bg-yellow-950/40',
                                    'epic' => 'text-purple-400 border-purple-500/50 bg-purple-950/40',
                                    'rare' => 'text-cyan-400 border-cyan-500/50 bg-cyan-950/40',
                                    'uncommon' => 'text-emerald-400 border-emerald-500/50 bg-emerald-950/40',
                                    default => 'text-stone-300 border-stone-700 bg-stone-900/60',
                                };

                                $rarityLabel = match($pet->rarity) {
                                    'common' => 'Zwykły',
                                    'uncommon' => 'Nietypowy',
                                    'rare' => 'Rzadki',
                                    'epic' => 'Epicki',
                                    'legendary' => 'Legendarny',
                                    default => ucfirst($pet->rarity),
                                };

                                $petIconClass = match($pet->rarity) {
                                    'legendary' => 'fa-dragon text-yellow-400 drop-shadow-[0_0_10px_rgba(234,179,8,0.8)]',
                                    'epic' => 'fa-wand-magic-sparkles text-purple-400 drop-shadow-[0_0_10px_rgba(168,85,247,0.8)]',
                                    'rare' => 'fa-cat text-cyan-400 drop-shadow-[0_0_10px_rgba(6,182,212,0.8)]',
                                    'uncommon' => 'fa-crow text-emerald-400 drop-shadow-[0_0_10px_rgba(16,185,129,0.8)]',
                                    default => 'fa-paw text-amber-300',
                                };
                            @endphp

                            <div class="relative rounded-2xl p-4 border-2 {{ $pet->is_equipped ? 'border-amber-400 bg-amber-950/30 shadow-[0_0_25px_rgba(245,158,11,0.35)]' : $rarityBorder }} transition-all duration-200">
                                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                    
                                    {{-- Left info --}}
                                    <div class="flex items-center space-x-4">
                                        {{-- Pet Icon Frame --}}
                                        <div class="relative w-14 h-14 rounded-xl border-2 {{ $pet->is_equipped ? 'border-amber-400 bg-amber-900/40 ring-4 ring-amber-400/30' : 'border-stone-700 bg-stone-950' }} flex items-center justify-center text-2xl shadow-inner">
                                            <i class="fa-solid {{ $petIconClass }}"></i>
                                            @if($pet->is_equipped)
                                                <span class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-emerald-500 rounded-full border-2 border-stone-900 shadow-[0_0_8px_rgba(16,185,129,0.8)]" title="W towarzystwie"></span>
                                            @endif
                                        </div>

                                        <div>
                                            <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                                                <h4 class="font-bold text-amber-100 text-base" style="font-family: 'Cinzel', serif;">{{ $pet->name }}</h4>
                                                @if($pet->is_equipped)
                                                    <span class="bg-gradient-to-r from-amber-600 to-yellow-600 text-stone-950 text-[10px] font-extrabold px-2 py-0.5 rounded-full border border-yellow-300 shadow-[0_0_10px_rgba(245,158,11,0.5)] tracking-wider">
                                                        <i class="fa-solid fa-shield-halved mr-0.5"></i> AKTYWNY
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="flex items-center space-x-2 mt-1 flex-wrap gap-y-1">
                                                <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded border {{ $rarityBadge }}">
                                                    {{ $rarityLabel }}
                                                </span>
                                                <span class="text-stone-600 text-xs">•</span>
                                                <span class="text-xs text-amber-300/80 font-bold">Poz. {{ $pet->level }}</span>
                                                <span class="text-stone-600 text-xs">•</span>
                                                <span class="text-xs text-indigo-300 font-bold flex items-center gap-1">
                                                    <i class="fa-solid fa-bolt text-yellow-400"></i> CP: +{{ $pet->getCombatPower() }}
                                                </span>
                                            </div>

                                            {{-- Pet Stats breakdown --}}
                                            @if(count($pet->stats ?? []) > 0)
                                                <div class="flex flex-wrap gap-1.5 mt-2">
                                                    @foreach($pet->stats ?? [] as $stat => $value)
                                                        @php
                                                            $statName = match($stat) {
                                                                'str' => 'Sił',
                                                                'agi' => 'Zrę',
                                                                'int' => 'Int',
                                                                'vit' => 'Wit',
                                                                default => strtoupper($stat),
                                                            };
                                                        @endphp
                                                        <span class="inline-flex items-center gap-1 text-[11px] bg-stone-950/80 border border-amber-500/20 px-2 py-0.5 rounded-md">
                                                            <span class="text-stone-400 font-semibold uppercase">{{ $statName }}:</span>
                                                            <span class="text-amber-300 font-extrabold">+{{ $value }}</span>
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Action Button --}}
                                    <div class="w-full sm:w-auto flex justify-end">
                                        <button wire:click="toggleEquipPet({{ $pet->id }})"
                                            class="w-full sm:w-auto {{ $pet->is_equipped
                                                ? 'bg-gradient-to-r from-red-800 to-red-700 hover:from-red-700 hover:to-red-600 text-red-100 border border-red-500/50 shadow-[0_0_15px_rgba(239,68,68,0.3)]'
                                                : 'bg-gradient-to-r from-emerald-700 via-green-600 to-emerald-700 hover:from-emerald-600 hover:to-green-500 text-white border border-emerald-400/50 shadow-[0_0_15px_rgba(16,185,129,0.3)]' }}
                                                font-bold py-2.5 px-5 rounded-xl transition-all duration-200 text-xs tracking-wider flex items-center justify-center gap-1.5"
                                            style="font-family: 'Cinzel', serif;"
                                            wire:loading.attr="disabled" wire:target="toggleEquipPet({{ $pet->id }})">
                                            <span wire:loading.remove wire:target="toggleEquipPet({{ $pet->id }})" class="flex items-center gap-1.5">
                                                @if($pet->is_equipped)
                                                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Odwołaj
                                                @else
                                                    <i class="fa-solid fa-hand-holding-hand text-amber-300"></i> Przywołaj
                                                @endif
                                            </span>
                                            <span wire:loading wire:target="toggleEquipPet({{ $pet->id }})" class="flex items-center gap-1.5">
                                                <i class="fa-solid fa-spinner animate-spin"></i> Przetwarzanie...
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- Empty Roster State --}}
                    <div class="text-center py-10 px-4">
                        <div class="w-20 h-20 mx-auto rounded-full border-2 border-dashed border-amber-500/30 bg-stone-950/60 flex items-center justify-center mb-4">
                            <i class="fa-solid fa-paw text-3xl text-amber-500/30"></i>
                        </div>
                        <h4 class="text-base font-bold text-amber-200 mb-1" style="font-family: 'Cinzel', serif;">Brak aktywnych chowańców</h4>
                        <p class="text-xs text-stone-400 max-w-md mx-auto mb-4">Nie posiadasz jeszcze żadnego chowańca. Umieść jajko w inkubatorze i poczekaj na jego wyklucie, aby zyskać wiernego towarzysza walki!</p>
                    </div>
                @endif

            </div>
        </div>

    </div>
</div>
