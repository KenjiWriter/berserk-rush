<div>
    {{-- Global Notifications --}}
    @if($errorMessage)
        <div class="mb-4 p-4 bg-red-950/90 border-2 border-red-500/60 rounded-2xl text-red-200 text-sm shadow-[0_0_20px_rgba(239,68,68,0.3)] flex items-center justify-between animate-fade-in">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-triangle-exclamation text-red-400 text-xl"></i>
                <span>{{ $errorMessage }}</span>
            </div>
            <button wire:click="$set('errorMessage', null)" class="text-red-400 hover:text-red-200"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
    @endif

    @if($successMessage)
        <div class="mb-4 p-4 bg-emerald-950/90 border-2 border-emerald-500/60 rounded-2xl text-emerald-200 text-sm shadow-[0_0_20px_rgba(16,185,129,0.3)] flex items-center justify-between animate-fade-in">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-circle-check text-emerald-400 text-xl"></i>
                <span>{{ $successMessage }}</span>
            </div>
            <button wire:click="$set('successMessage', null)" class="text-emerald-400 hover:text-emerald-200"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
    @endif

    {{-- Main Container --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mt-2">

        {{-- Left Column: Inkubator & Alchemiczny Syntezator Dusz (4 cols) --}}
        <div class="lg:col-span-5 xl:col-span-4 space-y-6">

            {{-- 1. Inkubator Chamber --}}
            <div class="bg-gradient-to-b from-stone-900/95 via-slate-900/90 to-stone-950/95 border-2 border-amber-500/40 rounded-2xl p-5 shadow-[0_0_25px_rgba(0,0,0,0.8)] backdrop-blur-md relative overflow-hidden">
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-amber-500/10 via-transparent to-transparent pointer-events-none"></div>

                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-amber-500/20 pb-3 mb-4">
                    <h3 class="text-sm font-bold text-amber-200 uppercase tracking-wider flex items-center gap-2" style="font-family: 'Cinzel', serif;">
                        <i class="fa-solid fa-egg text-amber-400 text-lg drop-shadow-[0_0_8px_rgba(245,158,11,0.6)]"></i>
                        Komnata Inkubacji
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
                        <div class="relative w-24 h-24 flex items-center justify-center mb-3">
                            <div class="absolute inset-0 rounded-full bg-gradient-to-tr {{ $rarityColorClass }} animate-pulse"></div>
                            <div class="absolute inset-1.5 rounded-full border border-amber-500/30 bg-stone-950/80 flex items-center justify-center shadow-inner">
                                <i class="fa-solid fa-egg text-4xl {{ $eggRarity === 'legendary' ? 'text-yellow-400 drop-shadow-[0_0_15px_rgba(234,179,8,0.8)]' : ($eggRarity === 'epic' ? 'text-purple-400 drop-shadow-[0_0_15px_rgba(168,85,247,0.8)]' : ($eggRarity === 'rare' ? 'text-cyan-400 drop-shadow-[0_0_15px_rgba(6,182,212,0.8)]' : ($eggRarity === 'uncommon' ? 'text-emerald-400 drop-shadow-[0_0_15px_rgba(16,185,129,0.8)]' : 'text-amber-200 drop-shadow-[0_0_10px_rgba(245,158,11,0.5)]'))) }} {{ $isReady ? 'animate-bounce' : 'animate-pulse' }}"></i>
                            </div>
                        </div>

                        <h4 class="text-base font-bold text-amber-100 mb-1 tracking-wide" style="font-family: 'Cinzel', serif;">
                            Inkubacja w toku
                        </h4>

                        <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full border text-[11px] font-bold mb-3 bg-stone-900/80 border-amber-500/30">
                            <span class="text-stone-400">Rzadkość:</span>
                            <span class="font-extrabold uppercase tracking-wider {{ $eggRarity === 'legendary' ? 'text-yellow-400' : ($eggRarity === 'epic' ? 'text-purple-400' : ($eggRarity === 'rare' ? 'text-cyan-400' : ($eggRarity === 'uncommon' ? 'text-emerald-400' : 'text-stone-300'))) }}">
                                {{ $rarityLabel }}
                            </span>
                        </div>

                        {{-- Progress Bar Container --}}
                        <div class="w-full bg-stone-950 p-2.5 rounded-xl border border-stone-800 mb-3 shadow-inner">
                            <div class="flex justify-between items-center text-xs mb-1 font-semibold">
                                <span class="text-stone-400">Postęp</span>
                                <span class="text-amber-300 font-bold">{{ number_format(min(100, $progress), 1) }}%</span>
                            </div>

                            <div class="w-full bg-stone-900 rounded-full h-3 border border-stone-700 overflow-hidden relative shadow-inner">
                                <div class="h-full rounded-full transition-all duration-1000 relative {{ $isReady ? 'bg-gradient-to-r from-emerald-600 via-green-500 to-emerald-400 shadow-[0_0_12px_rgba(16,185,129,0.6)]' : 'bg-gradient-to-r from-amber-700 via-amber-500 to-yellow-400 shadow-[0_0_12px_rgba(245,158,11,0.6)]' }}"
                                     style="width: {{ min(100, $progress) }}%">
                                    <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                                </div>
                            </div>

                            <div class="text-[11px] text-stone-400 mt-1.5 flex items-center justify-center gap-1.5 font-medium">
                                @if($isReady)
                                    <span class="text-emerald-400 font-bold flex items-center gap-1"><i class="fa-solid fa-circle-check"></i> Jajko gotowe do wyklucia!</span>
                                @else
                                    <span class="text-amber-300/90 flex items-center gap-1"><i class="fa-solid fa-hourglass-half text-amber-400 animate-spin"></i> {{ $timeRemaining }}</span>
                                @endif
                            </div>
                        </div>

                        @if($isReady)
                            <button wire:click="hatchEgg"
                                class="w-full bg-gradient-to-r from-emerald-600 via-green-600 to-emerald-700 hover:from-emerald-500 hover:to-green-500 text-white font-bold py-2.5 px-4 rounded-xl transition-all duration-200 transform hover:scale-[1.02] shadow-[0_0_20px_rgba(16,185,129,0.4)] border border-emerald-400/50 flex items-center justify-center gap-2 text-sm tracking-wider"
                                style="font-family: 'Cinzel', serif;"
                                wire:loading.attr="disabled">
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
                    {{-- Incubator Empty --}}
                    <div class="text-center py-6 px-2 flex flex-col items-center justify-center">
                        <div class="w-16 h-16 mx-auto rounded-full border-2 border-dashed border-amber-500/30 bg-stone-950/60 flex items-center justify-center mb-3 shadow-inner">
                            <i class="fa-solid fa-egg text-2xl text-amber-500/40"></i>
                        </div>
                        <h4 class="text-xs font-bold text-amber-200 mb-1" style="font-family: 'Cinzel', serif;">Brak jaja w inkubatorze</h4>
                        <p class="text-[11px] text-stone-400 mb-3 max-w-xs leading-relaxed">
                            Umieść jajo chowańca pozyskane w lochach lub ze skrzyń, aby rozpocząć proces inkubacji.
                        </p>
                    </div>
                @endif
            </div>

            {{-- 2. Alchemiczny Syntezator Dusz ("Sokowirówka Dusz") --}}
            <div class="bg-gradient-to-b from-purple-950/80 via-slate-900/90 to-stone-950/95 border-2 border-purple-500/40 rounded-2xl p-5 shadow-[0_0_30px_rgba(168,85,247,0.2)] backdrop-blur-md relative overflow-hidden">
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-purple-500/10 via-transparent to-transparent pointer-events-none"></div>

                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-purple-500/20 pb-3 mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-purple-200 uppercase tracking-wider flex items-center gap-2" style="font-family: 'Cinzel', serif;">
                            <i class="fa-solid fa-flask-vial text-purple-400 text-lg drop-shadow-[0_0_10px_rgba(168,85,247,0.8)] animate-pulse"></i>
                            Syntezator Dusz
                        </h3>
                        <span class="text-[10px] text-purple-400/80 font-semibold italic">Potocznie: Sokowirówka Chowańców</span>
                    </div>
                    <span class="bg-purple-950 text-purple-300 border border-purple-500/40 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full shadow-[0_0_10px_rgba(168,85,247,0.3)]">
                        75% Szansy
                    </span>
                </div>

                {{-- Synthesizer Chamber Slots --}}
                <div class="space-y-4">
                    <p class="text-xs text-stone-300 leading-relaxed text-center">
                        Umieść <span class="text-amber-300 font-bold">3 chowańce tej samej rzadkości</span> w komorze syntezy, aby dokonać rytuału połączenia dusz.
                    </p>

                    {{-- 3 Slots Grid --}}
                    <div class="grid grid-cols-3 gap-2 py-2">
                        @for($i = 0; $i < 3; $i++)
                            @php
                                $selectedPetId = $selectedSynthesizerPetIds[$i] ?? null;
                                $selectedPet = $selectedPetId ? $pets->firstWhere('id', $selectedPetId) : null;
                            @endphp

                            <div class="relative flex flex-col items-center">
                                @if($selectedPet)
                                    <div wire:click="toggleSynthesizerPet({{ $selectedPet->id }})"
                                         class="w-full h-24 rounded-xl border-2 border-purple-400 bg-gradient-to-b from-purple-900/40 to-stone-950 flex flex-col items-center justify-center p-1.5 cursor-pointer transform hover:scale-105 transition-all shadow-[0_0_15px_rgba(168,85,247,0.4)] group relative">
                                        <button class="absolute -top-1 -right-1 w-5 h-5 bg-red-600 text-white rounded-full text-[10px] flex items-center justify-center shadow hover:bg-red-500">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                        <i class="fa-solid fa-dragon text-2xl text-purple-300 mb-1 drop-shadow-[0_0_8px_rgba(168,85,247,0.8)]"></i>
                                        <span class="text-[10px] font-bold text-amber-200 truncate w-full text-center">{{ $selectedPet->name }}</span>
                                        <span class="text-[9px] uppercase font-extrabold text-purple-300">{{ $selectedPet->rarity }}</span>
                                    </div>
                                @else
                                    <div class="w-full h-24 rounded-xl border-2 border-dashed border-purple-500/30 bg-stone-950/60 flex flex-col items-center justify-center p-2 text-stone-500 hover:border-purple-400/60 transition-colors">
                                        <i class="fa-solid fa-plus text-xl mb-1 text-purple-500/40"></i>
                                        <span class="text-[10px] font-semibold text-stone-400">Slot {{ $i + 1 }}</span>
                                    </div>
                                @endif
                            </div>
                        @endfor
                    </div>

                    {{-- Synthesizer Action Button --}}
                    @if(count($selectedSynthesizerPetIds) === 3)
                        @php
                            $firstPet = $pets->firstWhere('id', $selectedSynthesizerPetIds[0]);
                            $targetRarity = match($firstPet->rarity ?? '') {
                                'common' => 'Nietypowy (Uncommon)',
                                'uncommon' => 'Rzadki (Rare)',
                                'rare' => 'Epicki (Epic)',
                                'epic' => 'Legendarny (Legendary)',
                                default => 'Wyższa Klasa',
                            };
                        @endphp

                        <div class="bg-stone-950/80 p-3 rounded-xl border border-purple-500/30 text-center space-y-2">
                            <div class="text-[11px] text-stone-300">
                                Cel syntezy: <span class="text-amber-300 font-extrabold">{{ $targetRarity }}</span>
                            </div>

                            <button wire:click="synthesizePets"
                                class="w-full bg-gradient-to-r from-purple-700 via-indigo-600 to-purple-800 hover:from-purple-600 hover:to-indigo-500 text-white font-bold py-2.5 px-4 rounded-xl transition-all duration-200 transform hover:scale-[1.02] shadow-[0_0_20px_rgba(168,85,247,0.5)] border border-purple-400/50 flex items-center justify-center gap-2 text-xs uppercase tracking-wider"
                                style="font-family: 'Cinzel', serif;"
                                wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="synthesizePets" class="flex items-center gap-2">
                                    <i class="fa-solid fa-wand-magic-sparkles text-amber-300"></i> Uruchom Syntezator (Sokowirówkę)!
                                </span>
                                <span wire:loading wire:target="synthesizePets" class="flex items-center gap-2">
                                    <i class="fa-solid fa-spinner animate-spin"></i> Transmutowanie...
                                </span>
                            </button>

                            <button wire:click="clearSynthesizer" class="text-[11px] text-stone-400 hover:text-stone-200 underline">
                                Wyczyść komorę
                            </button>
                        </div>
                    @else
                        <div class="p-3 bg-stone-950/60 rounded-xl border border-stone-800 text-[11px] text-stone-400 text-center">
                            Wybierz <span class="text-purple-300 font-bold">3 niezałożone chowańce</span> z listy po prawej stronie, aby je załadować.
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- Right Column: Companion Roster / Menażeria Chowańców (8 cols) --}}
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
                    <div class="space-y-4">
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

                                $isSynthesizerSelected = in_array($pet->id, $selectedSynthesizerPetIds);
                                $expPercent = $pet->getExpProgressPercent();
                                $reqExp = $pet->getRequiredExp();
                                $totalStats = $pet->getTotalStats();
                            @endphp

                            <div class="relative rounded-2xl p-4 border-2 {{ $pet->is_equipped ? 'border-amber-400 bg-amber-950/30 shadow-[0_0_25px_rgba(245,158,11,0.35)]' : ($isSynthesizerSelected ? 'border-purple-400 bg-purple-950/30 shadow-[0_0_25px_rgba(168,85,247,0.4)]' : $rarityBorder) }} transition-all duration-200">
                                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">

                                    {{-- Left side: Icon & Details --}}
                                    <div class="flex items-start space-x-4 flex-1">
                                        {{-- Pet Icon --}}
                                        <div class="relative w-16 h-16 rounded-xl border-2 {{ $pet->is_equipped ? 'border-amber-400 bg-amber-900/40 ring-4 ring-amber-400/30' : 'border-stone-700 bg-stone-950' }} flex items-center justify-center text-3xl shadow-inner shrink-0 mt-0.5">
                                            <i class="fa-solid {{ $petIconClass }}"></i>
                                            @if($pet->is_equipped)
                                                <span class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-emerald-500 rounded-full border-2 border-stone-900 shadow-[0_0_8px_rgba(16,185,129,0.8)]" title="Aktywny Towarzysz"></span>
                                            @endif
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                                                <h4 class="font-bold text-amber-100 text-base" style="font-family: 'Cinzel', serif;">{{ $pet->name }}</h4>
                                                @if($pet->is_equipped)
                                                    <span class="bg-gradient-to-r from-amber-600 to-yellow-600 text-stone-950 text-[10px] font-extrabold px-2 py-0.5 rounded-full border border-yellow-300 shadow-[0_0_10px_rgba(245,158,11,0.5)] tracking-wider">
                                                        <i class="fa-solid fa-shield-halved mr-0.5"></i> AKTYWNY
                                                    </span>
                                                @endif
                                                @if($isSynthesizerSelected)
                                                    <span class="bg-purple-900 text-purple-200 border border-purple-400 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                                        <i class="fa-solid fa-flask-vial mr-0.5"></i> SYNTEZA
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="flex items-center space-x-2 mt-1 flex-wrap gap-y-1 text-xs">
                                                <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded border {{ $rarityBadge }}">
                                                    {{ $rarityLabel }}
                                                </span>
                                                <span class="text-stone-600">•</span>
                                                <span class="text-amber-300 font-bold">Poziom {{ $pet->level }}</span>
                                                <span class="text-stone-600">•</span>
                                                <span class="text-indigo-300 font-bold flex items-center gap-1">
                                                    <i class="fa-solid fa-bolt text-yellow-400"></i> CP: +{{ $pet->getCombatPower() }}
                                                </span>
                                            </div>

                                            {{-- EXP Bar --}}
                                            <div class="mt-2.5 max-w-md">
                                                <div class="flex justify-between items-center text-[10px] mb-1 font-semibold">
                                                    <span class="text-stone-400">Doświadczenie (EXP)</span>
                                                    <span class="text-amber-300 font-bold">{{ $pet->exp }} / {{ $reqExp }} ({{ $expPercent }}%)</span>
                                                </div>
                                                <div class="w-full bg-stone-950 rounded-full h-2 border border-stone-800 overflow-hidden relative">
                                                    <div class="h-full rounded-full bg-gradient-to-r from-blue-600 via-indigo-500 to-cyan-400 shadow-[0_0_8px_rgba(59,130,246,0.6)] transition-all duration-500"
                                                         style="width: {{ $expPercent }}%"></div>
                                                </div>
                                            </div>

                                            {{-- Stats breakdown --}}
                                            @if(count($totalStats) > 0)
                                                <div class="flex flex-wrap gap-1.5 mt-2.5">
                                                    @foreach($totalStats as $stat => $value)
                                                        @php
                                                            $statName = match($stat) {
                                                                'str' => 'Siła',
                                                                'agi' => 'Zręczność',
                                                                'int' => 'Inteligencja',
                                                                'vit' => 'Witalność',
                                                                default => strtoupper($stat),
                                                            };
                                                        @endphp
                                                        <span class="inline-flex items-center gap-1 text-[11px] bg-stone-950/80 border border-amber-500/20 px-2 py-0.5 rounded-md">
                                                            <span class="text-stone-400 font-semibold uppercase text-[10px]">{{ $statName }}:</span>
                                                            <span class="text-amber-300 font-extrabold">+{{ $value }}</span>
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Right side: Action Buttons --}}
                                    <div class="w-full sm:w-auto flex flex-row sm:flex-col gap-2 shrink-0 justify-end">
                                        {{-- Feed Button --}}
                                        <button wire:click="openFeedModal({{ $pet->id }})"
                                            class="flex-1 sm:flex-none bg-gradient-to-r from-amber-700 via-yellow-600 to-amber-700 hover:from-amber-600 hover:to-yellow-500 text-stone-950 font-bold py-2 px-3 rounded-xl transition-all duration-200 text-xs tracking-wider flex items-center justify-center gap-1.5 shadow-[0_0_12px_rgba(245,158,11,0.3)] border border-yellow-300/50"
                                            style="font-family: 'Cinzel', serif;">
                                            <i class="fa-solid fa-utensils"></i> Nakarm
                                        </button>

                                        {{-- Equip/Unequip Button --}}
                                        <button wire:click="toggleEquipPet({{ $pet->id }})"
                                            class="flex-1 sm:flex-none {{ $pet->is_equipped
                                                ? 'bg-gradient-to-r from-red-800 to-red-700 hover:from-red-700 hover:to-red-600 text-red-100 border border-red-500/50'
                                                : 'bg-gradient-to-r from-emerald-700 via-green-600 to-emerald-700 hover:from-emerald-600 hover:to-green-500 text-white border border-emerald-400/50' }}
                                                font-bold py-2 px-3 rounded-xl transition-all duration-200 text-xs tracking-wider flex items-center justify-center gap-1.5"
                                            style="font-family: 'Cinzel', serif;">
                                            @if($pet->is_equipped)
                                                <i class="fa-solid fa-arrow-right-from-bracket"></i> Odwołaj
                                            @else
                                                <i class="fa-solid fa-hand-holding-hand text-amber-300"></i> Przywołaj
                                            @endif
                                        </button>

                                        {{-- Synthesizer Select Toggle --}}
                                        @if(!$pet->is_equipped && $pet->rarity !== 'legendary')
                                            <button wire:click="toggleSynthesizerPet({{ $pet->id }})"
                                                class="flex-1 sm:flex-none {{ $isSynthesizerSelected
                                                    ? 'bg-purple-900 text-purple-200 border border-purple-400'
                                                    : 'bg-stone-800 hover:bg-purple-900/60 text-purple-300 border border-purple-500/30' }}
                                                    font-bold py-1.5 px-3 rounded-xl transition-all duration-200 text-[10px] tracking-wider flex items-center justify-center gap-1">
                                                <i class="fa-solid fa-flask-vial"></i> {{ $isSynthesizerSelected ? 'Wybierz do Syntezy (✓)' : 'Do Syntezy' }}
                                            </button>
                                        @endif
                                    </div>

                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- Empty State --}}
                    <div class="text-center py-12 px-4">
                        <div class="w-20 h-20 mx-auto rounded-full border-2 border-dashed border-amber-500/30 bg-stone-950/60 flex items-center justify-center mb-4">
                            <i class="fa-solid fa-paw text-3xl text-amber-500/30"></i>
                        </div>
                        <h4 class="text-base font-bold text-amber-200 mb-1" style="font-family: 'Cinzel', serif;">Brak aktywnych chowańców</h4>
                        <p class="text-xs text-stone-400 max-w-md mx-auto mb-4">Nie posiadasz jeszcze żadnego chowańca. Umieść jajko w inkubatorze i poczekaj na jego wyklucie!</p>
                    </div>
                @endif

            </div>
        </div>

    </div>

    {{-- FEEDING MODAL OVERLAY --}}
    @if($feedingPet)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/80 backdrop-blur-md p-4 animate-fade-in">
            <div class="bg-gradient-to-b from-stone-900 via-slate-900 to-stone-950 border-2 border-amber-500/50 rounded-2xl max-w-2xl w-full p-6 shadow-[0_0_50px_rgba(0,0,0,0.9)] relative max-h-[90vh] flex flex-col">
                
                {{-- Modal Close Button --}}
                <button wire:click="closeFeedModal" class="absolute top-4 right-4 text-stone-400 hover:text-white text-xl">
                    <i class="fa-solid fa-xmark"></i>
                </button>

                {{-- Modal Header --}}
                <div class="flex items-center space-x-4 pb-4 border-b border-amber-500/20 mb-4 shrink-0">
                    <div class="w-14 h-14 rounded-xl border-2 border-amber-400 bg-stone-950 flex items-center justify-center text-2xl">
                        <i class="fa-solid fa-dragon text-yellow-400"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-amber-200" style="font-family: 'Cinzel', serif;">
                            Karmienie: {{ $feedingPet->name }}
                        </h3>
                        <div class="flex items-center gap-2 text-xs text-stone-300 mt-0.5">
                            <span>Poziom <strong class="text-amber-300">{{ $feedingPet->level }}</strong></span>
                            <span>•</span>
                            <span>EXP: <strong class="text-cyan-300">{{ $feedingPet->exp }} / {{ $feedingPet->getRequiredExp() }}</strong></span>
                        </div>
                    </div>
                </div>

                {{-- Modal Body: Inventory Items --}}
                <div class="flex-1 overflow-y-auto pr-2 space-y-3">
                    <p class="text-xs text-stone-300">
                        Wybierz niepotrzebne przedmioty z plecaka, którymi chcesz nakarmić chowańca. Poziom przedmiotu oraz jego rzadkość zostaną przeliczone na punkty <strong class="text-amber-300">EXP</strong>.
                    </p>

                    @if($inventoryItems->count() > 0)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach($inventoryItems as $item)
                                @php
                                    $isSelected = in_array($item->id, $selectedFeedItemIds);
                                    $expValue = app(\App\Application\Pets\PetService::class)->calculateItemExp($item);
                                    $reqLvl = $item->template->level_requirement ?? 1;
                                @endphp

                                <div wire:click="toggleFeedItem('{{ $item->id }}')"
                                     class="p-3 rounded-xl border cursor-pointer transition-all flex items-center justify-between {{ $isSelected ? 'border-amber-400 bg-amber-950/40 shadow-[0_0_15px_rgba(245,158,11,0.2)]' : 'border-stone-800 bg-stone-950/70 hover:border-stone-600' }}">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-lg border border-stone-700 bg-stone-900 flex items-center justify-center text-lg text-amber-300 shrink-0">
                                            <i class="fa-solid fa-shield-halved"></i>
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-stone-200 truncate max-w-[150px]">{{ $item->template->name ?? 'Przedmiot' }}</div>
                                            <div class="text-[10px] text-stone-400">Poziom: {{ $reqLvl }} | Rzadkość: {{ ucfirst($item->rarity ?? 'common') }}</div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs font-extrabold text-emerald-400">+{{ $expValue }} EXP</span>
                                        <div class="mt-1">
                                            <input type="checkbox" @checked($isSelected) class="rounded bg-stone-900 border-amber-500/50 text-amber-500 focus:ring-amber-500">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-stone-500 text-xs">
                            Brak dostępnych przedmiotów w plecaku do zjedzenia.
                        </div>
                    @endif
                </div>

                {{-- Modal Footer --}}
                <div class="pt-4 border-t border-amber-500/20 mt-4 flex items-center justify-between shrink-0">
                    <button wire:click="selectAllFeedItems" class="text-xs text-amber-400 hover:text-amber-300 font-semibold">
                        {{ count($selectedFeedItemIds) === $inventoryItems->count() ? 'Odznacz wszystkie' : 'Zaznacz wszystkie' }}
                    </button>

                    <div class="flex items-center gap-3">
                        <button wire:click="closeFeedModal" class="px-4 py-2 bg-stone-800 hover:bg-stone-700 text-stone-300 text-xs font-bold rounded-xl">
                            Anuluj
                        </button>

                        <button wire:click="feedPet"
                            @disabled(empty($selectedFeedItemIds))
                            class="px-5 py-2 bg-gradient-to-r from-amber-600 via-yellow-500 to-amber-600 hover:from-amber-500 hover:to-yellow-400 disabled:opacity-50 text-stone-950 text-xs font-extrabold rounded-xl shadow-[0_0_15px_rgba(245,158,11,0.4)] tracking-wider"
                            style="font-family: 'Cinzel', serif;">
                            <span wire:loading.remove wire:target="feedPet">
                                <i class="fa-solid fa-utensils mr-1"></i> Nakarm ({{ count($selectedFeedItemIds) }})
                            </span>
                            <span wire:loading wire:target="feedPet">
                                <i class="fa-solid fa-spinner animate-spin"></i> Karmienie...
                            </span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    @endif
</div>
