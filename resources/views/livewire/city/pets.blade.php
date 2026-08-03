<div class="min-h-screen text-amber-100 relative overflow-hidden bg-black">
    {{-- Static Background --}}
    <div class="absolute inset-0 bg-cover bg-center opacity-40 mix-blend-luminosity" style="background-image: url('{{ asset('img/swordmaster.png') }}');"></div>
    <div class="absolute inset-0 bg-gradient-to-b from-slate-900/60 via-slate-900/80 to-slate-900/95"></div>

    @php
        $tierColors = [
            1 => ['border' => 'border-stone-600', 'text' => 'text-stone-300', 'badge' => 'text-stone-300 border-stone-700 bg-stone-900/60', 'glow' => 'shadow-[0_0_20px_rgba(120,113,108,0.25)]', 'from' => 'from-stone-800/60'],
            2 => ['border' => 'border-emerald-500', 'text' => 'text-emerald-400', 'badge' => 'text-emerald-400 border-emerald-500/50 bg-emerald-950/40', 'glow' => 'shadow-[0_0_20px_rgba(16,185,129,0.25)]', 'from' => 'from-emerald-950/40'],
            3 => ['border' => 'border-cyan-500', 'text' => 'text-cyan-400', 'badge' => 'text-cyan-400 border-cyan-500/50 bg-cyan-950/40', 'glow' => 'shadow-[0_0_20px_rgba(6,182,212,0.25)]', 'from' => 'from-cyan-950/40'],
            4 => ['border' => 'border-indigo-500', 'text' => 'text-indigo-400', 'badge' => 'text-indigo-400 border-indigo-500/50 bg-indigo-950/40', 'glow' => 'shadow-[0_0_20px_rgba(99,102,241,0.25)]', 'from' => 'from-indigo-950/40'],
            5 => ['border' => 'border-purple-500', 'text' => 'text-purple-400', 'badge' => 'text-purple-400 border-purple-500/50 bg-purple-950/40', 'glow' => 'shadow-[0_0_20px_rgba(168,85,247,0.25)]', 'from' => 'from-purple-950/40'],
            6 => ['border' => 'border-amber-400', 'text' => 'text-yellow-400', 'badge' => 'text-yellow-400 border-yellow-500/50 bg-yellow-950/40', 'glow' => 'shadow-[0_0_25px_rgba(245,158,11,0.35)]', 'from' => 'from-amber-950/40'],
        ];
        $tc = fn($tier) => $tierColors[$tier] ?? $tierColors[1];
    @endphp

    <div class="relative w-full px-4 sm:px-6 md:px-10 lg:px-12 py-8 min-h-screen flex flex-col">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 gap-4">
            <div class="bg-black/60 border border-amber-700/50 rounded-lg p-3 sm:p-4 shadow-2xl backdrop-blur-md">
                <h2 class="text-xl sm:text-2xl font-bold text-amber-500 medieval-font flex items-center gap-2 tracking-wider">
                    <i class="fa-solid fa-paw text-amber-500 text-xl sm:text-2xl mr-1"></i> Pety
                </h2>
            </div>

            <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-end">
                <button wire:click="toggleInfoModal"
                    class="min-h-[44px] w-11 h-11 flex items-center justify-center bg-black/80 border border-sky-600/50 rounded-lg text-sky-300 hover:text-sky-100 hover:border-sky-400 transition-colors shadow-inner"
                    title="Poradnik chowańców">
                    <i class="fa-solid fa-circle-info text-lg"></i>
                </button>
                <button wire:click="backToHub"
                    class="min-h-[44px] bg-gradient-to-b from-slate-700 to-slate-800 hover:from-slate-600 hover:to-slate-700 border border-slate-500 text-amber-200 font-bold py-2.5 px-6 rounded-lg transition-all duration-200 shadow-[0_4px_15px_rgba(0,0,0,0.5)] medieval-font flex items-center justify-center">
                    <i class="fa-solid fa-archway mr-2 text-amber-400"></i> Powrót
                </button>
            </div>
        </div>

        {{-- Global Notifications --}}
        @if(!empty($errorMessage))
            <div class="mb-4 p-4 bg-red-950/90 border-2 border-red-500/60 rounded-2xl text-red-200 text-sm shadow-[0_0_20px_rgba(239,68,68,0.3)] flex items-center justify-between animate-fade-in">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-triangle-exclamation text-red-400 text-xl"></i>
                    <span>{{ $errorMessage }}</span>
                </div>
                <button wire:click="$set('errorMessage', null)" class="text-red-400 hover:text-red-200"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
        @endif

        @if(!empty($successMessage))
            <div class="mb-4 p-4 bg-emerald-950/90 border-2 border-emerald-500/60 rounded-2xl text-emerald-200 text-sm shadow-[0_0_20px_rgba(16,185,129,0.3)] flex items-center justify-between animate-fade-in">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-circle-check text-emerald-400 text-xl"></i>
                    <span>{{ $successMessage }}</span>
                </div>
                <button wire:click="$set('successMessage', null)" class="text-emerald-400 hover:text-emerald-200"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
        @endif

        {{-- Main Container --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            {{-- Left Column: Inkubator & Fuzja --}}
            <div class="lg:col-span-5 xl:col-span-4 space-y-6">

                {{-- 1. Inkubator --}}
                <div class="bg-gradient-to-b from-stone-900/95 via-slate-900/90 to-stone-950/95 border-2 border-amber-500/40 rounded-2xl p-5 shadow-[0_0_25px_rgba(0,0,0,0.8)] backdrop-blur-md relative overflow-hidden">
                    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-amber-500/10 via-transparent to-transparent pointer-events-none"></div>

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
                            $eggTier = $incubator->getEffectiveTier();
                            $ec = $tc($eggTier);
                            $tierName = \App\Domain\Pets\PetTier::name($eggTier);

                            $progress = $incubator->getProgress();
                            $isReady = $incubator->isReady();
                            $startMs = $incubator->started_at ? $incubator->started_at->timestamp * 1000 : now()->timestamp * 1000;
                            $targetMs = $incubator->hatches_at ? $incubator->hatches_at->timestamp * 1000 : now()->timestamp * 1000;

                            $diff = $isReady ? null : ($incubator->hatches_at ? now()->diff($incubator->hatches_at) : null);
                            $initialTimeRemaining = $diff ? sprintf('%dh %dm %ds', $diff->h + ($diff->days * 24), $diff->i, $diff->s) : null;
                        @endphp

                        <div class="text-center py-3 flex flex-col items-center"
                             x-data="{
                                start: {{ $startMs }},
                                target: {{ $targetMs }},
                                now: Date.now(),
                                timer: null,
                                isReady: {{ $isReady ? 'true' : 'false' }},
                                init() {
                                    if (this.isReady) return;
                                    this.timer = setInterval(() => {
                                        this.now = Date.now();
                                        if (this.now >= this.target) {
                                            clearInterval(this.timer);
                                            this.isReady = true;
                                            $wire.$refresh();
                                        }
                                    }, 1000);
                                },
                                destroy() {
                                    if (this.timer) clearInterval(this.timer);
                                },
                                get diffSeconds() {
                                    return Math.max(0, Math.floor((this.target - this.now) / 1000));
                                },
                                get formattedTime() {
                                    if (this.diffSeconds <= 0 || this.isReady) return 'Gotowe do wyklucia!';
                                    let h = Math.floor(this.diffSeconds / 3600);
                                    let m = Math.floor((this.diffSeconds % 3600) / 60);
                                    let s = this.diffSeconds % 60;
                                    let parts = [];
                                    if (h > 0) parts.push(h + 'h');
                                    parts.push((m < 10 && h > 0 ? '0' : '') + m + 'm');
                                    parts.push((s < 10 ? '0' : '') + s + 's');
                                    return parts.join(' ');
                                },
                                get progressPercent() {
                                    if (this.isReady) return '100.0';
                                    let total = (this.target - this.start);
                                    if (total <= 0) return '100.0';
                                    let elapsed = (this.now - this.start);
                                    return Math.min(100, Math.max(0, (elapsed / total) * 100)).toFixed(1);
                                }
                             }">
                            <div class="relative w-24 h-24 flex items-center justify-center mb-3">
                                <div class="absolute inset-0 rounded-full bg-gradient-to-tr {{ $ec['from'] }} to-stone-900/10 border {{ $ec['border'] }} animate-pulse"></div>
                                <div class="absolute inset-1.5 rounded-full border border-amber-500/30 bg-stone-950/80 flex items-center justify-center shadow-inner">
                                    <i class="fa-solid fa-egg text-4xl {{ $ec['text'] }} {{ $isReady ? 'animate-bounce' : 'animate-pulse' }}"></i>
                                </div>
                            </div>

                            <h4 class="text-base font-bold text-amber-100 mb-1 tracking-wide" style="font-family: 'Cinzel', serif;">
                                Inkubacja w toku
                            </h4>

                            <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full border text-[11px] font-bold mb-3 bg-stone-900/80 border-amber-500/30">
                                <span class="text-stone-400">Tier jajka:</span>
                                <span class="font-extrabold uppercase tracking-wider {{ $ec['text'] }}">{{ $tierName }}</span>
                            </div>

                            <div class="w-full bg-stone-950 p-2.5 rounded-xl border border-stone-800 mb-3 shadow-inner">
                                <div class="flex justify-between items-center text-xs mb-1 font-semibold">
                                    <span class="text-stone-400">Postęp</span>
                                    <span class="text-amber-300 font-bold" x-text="progressPercent + '%'">{{ number_format(min(100, $progress), 1) }}%</span>
                                </div>

                                <div class="w-full bg-stone-900 rounded-full h-3 border border-stone-700 overflow-hidden relative shadow-inner">
                                    <div class="h-full rounded-full transition-all duration-300 relative {{ $isReady ? 'bg-gradient-to-r from-emerald-600 via-green-500 to-emerald-400 shadow-[0_0_12px_rgba(16,185,129,0.6)]' : 'bg-gradient-to-r from-amber-700 via-amber-500 to-yellow-400 shadow-[0_0_12px_rgba(245,158,11,0.6)]' }}"
                                         :style="'width: ' + progressPercent + '%'"
                                         style="width: {{ min(100, $progress) }}%">
                                        <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                                    </div>
                                </div>

                                <div class="text-[11px] text-stone-400 mt-1.5 flex items-center justify-center gap-1.5 font-medium">
                                    <template x-if="isReady">
                                        <span class="text-emerald-400 font-bold flex items-center gap-1"><i class="fa-solid fa-circle-check"></i> Jajko gotowe do wyklucia!</span>
                                    </template>
                                    <template x-if="!isReady">
                                        <span class="text-amber-300/90 flex items-center gap-1 font-mono font-bold">
                                            <i class="fa-solid fa-hourglass-half text-amber-400 animate-spin mr-1"></i>
                                            <span x-text="formattedTime">{{ $initialTimeRemaining }}</span>
                                        </span>
                                    </template>
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
                        <div class="text-center py-6 px-2 flex flex-col items-center justify-center">
                            <div class="w-16 h-16 mx-auto rounded-full border-2 border-dashed border-amber-500/30 bg-stone-950/60 flex items-center justify-center mb-3 shadow-inner">
                                <i class="fa-solid fa-egg text-2xl text-amber-500/40"></i>
                            </div>
                            <h4 class="text-xs font-bold text-amber-200 mb-1" style="font-family: 'Cinzel', serif;">Brak jaja w inkubatorze</h4>
                            <p class="text-[11px] text-stone-400 mb-3 max-w-xs leading-relaxed">
                                Umieść jajo chowańca pozyskane w lochach lub ze skrzyń, aby rozpocząć proces inkubacji.
                            </p>

                            @if($eggs->count() > 0)
                                <div class="w-full grid grid-cols-3 gap-2">
                                    @foreach($eggs as $egg)
                                        <button wire:click="placeEgg('{{ $egg->id }}')"
                                            class="p-2 rounded-lg border border-stone-700 bg-stone-950/80 hover:border-amber-500/60 text-[10px] font-semibold text-stone-300 flex flex-col items-center gap-1">
                                            <i class="fa-solid fa-egg text-amber-400"></i>
                                            <span class="truncate w-full text-center">{{ $egg->template->name ?? 'Jajko' }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- 2. Fuzja Chowańców --}}
                <div class="bg-gradient-to-b from-purple-950/80 via-slate-900/90 to-stone-950/95 border-2 border-purple-500/40 rounded-2xl p-5 shadow-[0_0_30px_rgba(168,85,247,0.2)] backdrop-blur-md relative overflow-hidden">
                    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-purple-500/10 via-transparent to-transparent pointer-events-none"></div>

                    <div class="flex items-center justify-between border-b border-purple-500/20 pb-3 mb-4">
                        <h3 class="text-sm font-bold text-purple-200 uppercase tracking-wider flex items-center gap-2" style="font-family: 'Cinzel', serif;">
                            <i class="fa-solid fa-flask-vial text-purple-400 text-lg drop-shadow-[0_0_10px_rgba(168,85,247,0.8)] animate-pulse"></i>
                            Fuzja Chowańców
                        </h3>
                    </div>

                    <div class="space-y-4">
                        <p class="text-xs text-stone-300 leading-relaxed text-center">
                            Umieść <span class="text-amber-300 font-bold">2 chowańce tego samego tieru</span> w komorze, aby spróbować połączyć je w jednego, potężniejszego.
                            Im bardziej dojrzałe pety (bliżej "Formy Dorosłej"), tym wyższa szansa sukcesu.
                        </p>

                        <div class="grid grid-cols-2 gap-3 py-2">
                            @for($i = 0; $i < 2; $i++)
                                @php
                                    $selectedPetId = $selectedFusionPetIds[$i] ?? null;
                                    $selectedPet = $selectedPetId ? $pets->firstWhere('id', $selectedPetId) : null;
                                @endphp

                                <div class="relative flex flex-col items-center">
                                    @if($selectedPet)
                                        <div wire:click="toggleFusionPet({{ $selectedPet->id }})"
                                             class="w-full h-24 rounded-xl border-2 border-purple-400 bg-gradient-to-b from-purple-900/40 to-stone-950 flex flex-col items-center justify-center p-1.5 cursor-pointer transform hover:scale-105 transition-all shadow-[0_0_15px_rgba(168,85,247,0.4)] group relative">
                                            <button class="absolute -top-1 -right-1 w-5 h-5 bg-red-600 text-white rounded-full text-[10px] flex items-center justify-center shadow hover:bg-red-500">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                            <img src="{{ route('assets.items', ['filename' => $selectedPet->icon]) }}" alt="{{ $selectedPet->name }}" class="w-10 h-10 object-contain mb-1 drop-shadow-[0_0_8px_rgba(168,85,247,0.8)]">
                                            <span class="text-[10px] font-bold text-amber-200 truncate w-full text-center">{{ $selectedPet->name }}</span>
                                            <span class="text-[9px] uppercase font-extrabold text-purple-300">{{ $selectedPet->tierName() }} · {{ $selectedPet->growthStageLabel() }}</span>
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

                        @if(count($selectedFusionPetIds) === 2)
                            @php
                                $fusionA = $pets->firstWhere('id', $selectedFusionPetIds[0]);
                                $fusionB = $pets->firstWhere('id', $selectedFusionPetIds[1]);
                                $fusionChance = ($fusionA && $fusionB) ? \App\Domain\Pets\PetFusionRules::successChance($fusionA->tier, $fusionA->growth_stage, $fusionB->growth_stage) : 0;
                                $fusionTargetTier = $fusionA ? \App\Domain\Pets\PetFusionRules::resultTier($fusionA->tier) : null;
                                $fusionCost = $fusionA ? (int) config("pets.fusion_cost_gold.{$fusionA->tier}", 0) : 0;
                                $canAffordFusion = $character->gold >= $fusionCost;
                            @endphp

                            <div class="bg-stone-950/80 p-3 rounded-xl border border-purple-500/30 text-center space-y-2">
                                <div class="text-[11px] text-stone-300">
                                    Cel fuzji: <span class="text-amber-300 font-extrabold">{{ $fusionTargetTier ? \App\Domain\Pets\PetTier::name($fusionTargetTier) : '—' }}</span>
                                </div>
                                <div class="text-[11px] text-stone-300">
                                    Szansa powodzenia: <span class="text-emerald-400 font-extrabold">{{ number_format($fusionChance, 2) }}%</span>
                                </div>
                                <div class="text-[11px] text-stone-300">
                                    Koszt próby: <span class="{{ $canAffordFusion ? 'text-yellow-300' : 'text-red-400' }} font-extrabold">{{ number_format($fusionCost) }} złota</span>
                                </div>
                                @if(!$canAffordFusion)
                                    <div class="text-[10px] text-red-400 font-semibold">Nie masz wystarczająco złota na tę próbę fuzji.</div>
                                @endif
                                <div class="text-[10px] text-stone-500 leading-relaxed">
                                    W razie porażki pety mogą przetrwać (bez utraty, z utratą ewolucji lub cofnięciem rozwoju) albo ulec rozproszeniu — wynik losowany, nie wybierany.
                                </div>

                                <button wire:click="fusePets"
                                    @disabled(!$canAffordFusion)
                                    class="w-full bg-gradient-to-r from-purple-700 via-indigo-600 to-purple-800 hover:from-purple-600 hover:to-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold py-2.5 px-4 rounded-xl transition-all duration-200 transform hover:scale-[1.02] shadow-[0_0_20px_rgba(168,85,247,0.5)] border border-purple-400/50 flex items-center justify-center gap-2 text-xs uppercase tracking-wider"
                                    style="font-family: 'Cinzel', serif;"
                                    wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="fusePets" class="flex items-center gap-2">
                                        <i class="fa-solid fa-wand-magic-sparkles text-amber-300"></i> Rozpocznij Fuzję!
                                    </span>
                                    <span wire:loading wire:target="fusePets" class="flex items-center gap-2">
                                        <i class="fa-solid fa-spinner animate-spin"></i> Łączenie...
                                    </span>
                                </button>

                                <button wire:click="clearFusion" class="text-[11px] text-stone-400 hover:text-stone-200 underline">
                                    Wyczyść komorę
                                </button>
                            </div>
                        @else
                            <div class="p-3 bg-stone-950/60 rounded-xl border border-stone-800 text-[11px] text-stone-400 text-center">
                                Wybierz <span class="text-purple-300 font-bold">2 niezałożone chowańce</span> tego samego tieru z listy po prawej stronie.
                            </div>
                        @endif
                    </div>
                </div>

            </div>

            {{-- Right Column: Roster --}}
            <div class="lg:col-span-7 xl:col-span-8 space-y-4">
                <div class="bg-gradient-to-b from-stone-900/95 via-slate-900/90 to-stone-950/95 border-2 border-amber-500/40 rounded-2xl p-5 shadow-[0_0_25px_rgba(0,0,0,0.8)] backdrop-blur-md relative">

                    <div class="flex items-center justify-between border-b border-amber-500/20 pb-3 mb-4">
                        <h3 class="text-sm font-bold text-amber-200 uppercase tracking-wider flex items-center gap-2" style="font-family: 'Cinzel', serif;">
                            <i class="fa-solid fa-paw text-amber-400 text-lg drop-shadow-[0_0_8px_rgba(245,158,11,0.6)]"></i>
                            Menażeria Chowańców
                        </h3>
                        <span class="bg-stone-900 text-amber-300 border border-amber-500/30 text-xs font-bold px-2.5 py-0.5 rounded-full">
                            Posiadane: {{ $pets->count() }} / {{ $character->getPetStableCapacity() }}
                        </span>
                    </div>

                    @if($pets->count() > 0)
                        <div class="space-y-4">
                            @foreach($pets as $pet)
                                @php
                                    $c = $tc($pet->tier);
                                    $isFusionSelected = in_array($pet->id, $selectedFusionPetIds);
                                    $expPercent = $pet->getExpProgressPercent();
                                    $reqExp = $pet->getRequiredExp();
                                    $effectiveStats = $pet->getEffectiveStatsFor($character);
                                    $effectiveCp = $pet->getCombatPowerFor($character);
                                    $isDampened = $character->level < $pet->level;
                                    $archetypeBonus = $pet->getArchetypeBonusPercentFor($character);
                                @endphp

                                <div class="relative rounded-2xl p-4 border-2 {{ $pet->is_equipped ? 'border-amber-400 bg-amber-950/30 shadow-[0_0_25px_rgba(245,158,11,0.35)]' : ($isFusionSelected ? 'border-purple-400 bg-purple-950/30 shadow-[0_0_25px_rgba(168,85,247,0.4)]' : $c['border'] . ' bg-gradient-to-r ' . $c['from'] . ' via-stone-900/70 to-stone-950/90 ' . $c['glow']) }} transition-all duration-200">
                                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">

                                        <div class="flex items-start space-x-4 flex-1">
                                            <div class="relative w-16 h-16 rounded-xl border-2 {{ $pet->is_equipped ? 'border-amber-400 bg-amber-900/40 ring-4 ring-amber-400/30' : 'border-stone-700 bg-stone-950' }} flex items-center justify-center text-3xl shadow-inner shrink-0 mt-0.5">
                                                <img src="{{ route('assets.items', ['filename' => $pet->icon]) }}" alt="{{ $pet->name }}" class="w-11 h-11 object-contain">
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
                                                    @if($isFusionSelected)
                                                        <span class="bg-purple-900 text-purple-200 border border-purple-400 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                                            <i class="fa-solid fa-flask-vial mr-0.5"></i> FUZJA
                                                        </span>
                                                    @endif
                                                    @if($pet->fusion_count > 0)
                                                        <span class="bg-stone-900 text-sky-300 border border-sky-500/40 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                                            +{{ $pet->fusion_count }}
                                                        </span>
                                                    @endif
                                                    @if($pet->archetype)
                                                        @php
                                                            $archetypeColors = [
                                                                'attacker' => 'bg-red-950 text-red-300 border-red-500/40',
                                                                'defense' => 'bg-blue-950 text-blue-300 border-blue-500/40',
                                                                'support' => 'bg-emerald-950 text-emerald-300 border-emerald-500/40',
                                                            ];
                                                        @endphp
                                                        <span class="{{ $archetypeColors[$pet->archetype] ?? 'bg-stone-900 text-stone-300 border-stone-600' }} border text-[10px] font-bold px-2 py-0.5 rounded-full"
                                                              title="{{ \App\Domain\Pets\PetArchetype::passiveDescription($pet->archetype) }}">
                                                            {{ \App\Domain\Pets\PetArchetype::label($pet->archetype) }}
                                                            @if($archetypeBonus > 0)
                                                                (+{{ number_format($archetypeBonus, 1) }}%)
                                                            @endif
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="flex items-center space-x-2 mt-1 flex-wrap gap-y-1 text-xs">
                                                    <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded border {{ $c['badge'] }}">
                                                        {{ $pet->tierName() }}
                                                    </span>
                                                    <span class="text-stone-600">•</span>
                                                    <span class="text-stone-400 font-semibold">{{ $pet->growthStageLabel() }}</span>
                                                    <span class="text-stone-600">•</span>
                                                    <span class="text-amber-300 font-bold">Poziom {{ $pet->level }}</span>
                                                    <span class="text-stone-600">•</span>
                                                    <span class="text-indigo-300 font-bold flex items-center gap-1">
                                                        <i class="fa-solid fa-bolt text-yellow-400"></i> CP: +{{ $effectiveCp }}
                                                    </span>
                                                    @if($isDampened)
                                                        <span class="text-red-400 text-[10px] font-bold flex items-center gap-1" title="Twój poziom postaci jest niższy niż poziom peta - jego moc jest tłumiona.">
                                                            <i class="fa-solid fa-triangle-exclamation"></i> Moc tłumiona ({{ min(100, round(($character->level / max(1,$pet->level)) * 100)) }}%)
                                                        </span>
                                                    @endif
                                                </div>

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

                                                @if(count($effectiveStats) > 0)
                                                    <div class="flex flex-wrap gap-1.5 mt-2.5">
                                                        @foreach($effectiveStats as $stat => $value)
                                                            @php
                                                                $statName = match($stat) {
                                                                    'str' => 'Siła', 'agi' => 'Zręczność', 'int' => 'Inteligencja', 'vit' => 'Witalność',
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

                                                {{-- Ekwipunek Peta --}}
                                                <div class="flex items-center gap-2 mt-3">
                                                    @foreach(['collar' => ['icon' => 'fa-link', 'label' => 'Obroża'], 'charm' => ['icon' => 'fa-gem', 'label' => 'Charm']] as $slot => $meta)
                                                        @php $gearItem = $pet->{$slot . 'Item'}; @endphp
                                                        <div class="flex items-center gap-1.5">
                                                            @if($gearItem)
                                                                <button wire:click="unequipGear({{ $pet->id }}, '{{ $slot }}')"
                                                                    class="w-9 h-9 rounded-lg border-2 border-emerald-500/60 bg-emerald-950/40 flex items-center justify-center text-emerald-300 hover:border-red-500 hover:text-red-300 transition-colors"
                                                                    title="{{ $gearItem->template->name ?? $meta['label'] }} (kliknij, aby zdjąć)">
                                                                    <i class="fa-solid {{ $meta['icon'] }} text-sm"></i>
                                                                </button>
                                                            @else
                                                                <button wire:click="openGearPicker({{ $pet->id }}, '{{ $slot }}')"
                                                                    class="w-9 h-9 rounded-lg border-2 border-dashed border-stone-700 bg-stone-950/60 flex items-center justify-center text-stone-500 hover:border-amber-500/60 hover:text-amber-300 transition-colors"
                                                                    title="Załóż {{ $meta['label'] }}">
                                                                    <i class="fa-solid {{ $meta['icon'] }} text-sm"></i>
                                                                </button>
                                                            @endif
                                                            <span class="text-[9px] text-stone-500 uppercase font-semibold">{{ $meta['label'] }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>

                                        <div class="w-full sm:w-auto flex flex-row sm:flex-col gap-2 shrink-0 justify-end">
                                            <button wire:click="openFeedModal({{ $pet->id }})"
                                                class="flex-1 sm:flex-none bg-gradient-to-r from-amber-700 via-yellow-600 to-amber-700 hover:from-amber-600 hover:to-yellow-500 text-stone-950 font-bold py-2 px-3 rounded-xl transition-all duration-200 text-xs tracking-wider flex items-center justify-center gap-1.5 shadow-[0_0_12px_rgba(245,158,11,0.3)] border border-yellow-300/50"
                                                style="font-family: 'Cinzel', serif;">
                                                <i class="fa-solid fa-utensils"></i> Nakarm
                                            </button>

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

                                            @if(!$pet->is_equipped && \App\Domain\Pets\PetFusionRules::canFuse($pet->tier))
                                                <button wire:click="toggleFusionPet({{ $pet->id }})"
                                                    class="flex-1 sm:flex-none {{ $isFusionSelected
                                                        ? 'bg-purple-900 text-purple-200 border border-purple-400'
                                                        : 'bg-stone-800 hover:bg-purple-900/60 text-purple-300 border border-purple-500/30' }}
                                                        font-bold py-1.5 px-3 rounded-xl transition-all duration-200 text-[10px] tracking-wider flex items-center justify-center gap-1">
                                                    <i class="fa-solid fa-flask-vial"></i> {{ $isFusionSelected ? 'Wybrano do Fuzji (✓)' : 'Do Fuzji' }}
                                                </button>
                                            @endif

                                            @if(!$pet->is_equipped && !$pet->collar_item_instance_id && !$pet->charm_item_instance_id)
                                                <button wire:click="openSellModal({{ $pet->id }})"
                                                    class="flex-1 sm:flex-none bg-stone-800 hover:bg-yellow-900/60 text-yellow-300 border border-yellow-500/30 font-bold py-1.5 px-3 rounded-xl transition-all duration-200 text-[10px] tracking-wider flex items-center justify-center gap-1">
                                                    <i class="fa-solid fa-scale-balanced"></i> Sprzedaj
                                                </button>
                                            @endif
                                        </div>

                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
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
    </div>

    {{-- FEEDING MODAL --}}
    @if($feedingPet)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/80 backdrop-blur-md p-4 animate-fade-in">
            <div class="bg-gradient-to-b from-stone-900 via-slate-900 to-stone-950 border-2 border-amber-500/50 rounded-2xl max-w-2xl w-full p-6 shadow-[0_0_50px_rgba(0,0,0,0.9)] relative max-h-[90vh] flex flex-col">

                <button wire:click="closeFeedModal" class="absolute top-4 right-4 text-stone-400 hover:text-white text-xl">
                    <i class="fa-solid fa-xmark"></i>
                </button>

                @php $feedMin = \App\Domain\Pets\PetTier::feedLevelMin($feedingPet->tier); @endphp

                <div class="flex items-center space-x-4 pb-4 border-b border-amber-500/20 mb-4 shrink-0">
                    <div class="w-14 h-14 rounded-xl border-2 border-amber-400 bg-stone-950 flex items-center justify-center text-2xl">
                        <img src="{{ route('assets.items', ['filename' => $feedingPet->icon]) }}" alt="{{ $feedingPet->name }}" class="w-10 h-10 object-contain">
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-amber-200" style="font-family: 'Cinzel', serif;">
                            Karmienie: {{ $feedingPet->name }}
                        </h3>
                        <div class="flex items-center gap-2 text-xs text-stone-300 mt-0.5 flex-wrap">
                            <span>Poziom <strong class="text-amber-300">{{ $feedingPet->level }}</strong></span>
                            <span>•</span>
                            <span>EXP: <strong class="text-cyan-300">{{ $feedingPet->exp }} / {{ $feedingPet->getRequiredExp() }}</strong></span>
                            <span>•</span>
                            <span>Przyjmuje itemy od poz. <strong class="text-emerald-300">{{ $feedMin }}+</strong></span>
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto pr-2 space-y-3">
                    <p class="text-xs text-stone-300">
                        Wybierz niepotrzebne przedmioty z plecaka (w akceptowanym przedziale poziomowym), którymi chcesz nakarmić chowańca.
                    </p>

                    @if($inventoryItems->count() > 0)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach($inventoryItems as $item)
                                @php
                                    $isSelected = in_array($item->id, $selectedFeedItemIds);
                                    $expValue = app(\App\Application\Pets\PetFeedingService::class)->calculateItemExp($item);
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
                            Brak przedmiotów w plecaku mieszczących się w przedziale poziomowym tego chowańca.
                        </div>
                    @endif
                </div>

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

    {{-- GEAR PICKER MODAL --}}
    @if($gearPet && $gearSlot)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/80 backdrop-blur-md p-4 animate-fade-in">
            <div class="bg-gradient-to-b from-stone-900 via-slate-900 to-stone-950 border-2 border-amber-500/50 rounded-2xl max-w-lg w-full p-6 shadow-[0_0_50px_rgba(0,0,0,0.9)] relative max-h-[90vh] flex flex-col">
                <button wire:click="closeGearPicker" class="absolute top-4 right-4 text-stone-400 hover:text-white text-xl">
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <h3 class="text-lg font-bold text-amber-200 mb-4" style="font-family: 'Cinzel', serif;">
                    Wybierz {{ $gearSlot === 'collar' ? 'obrożę' : 'charm' }} dla {{ $gearPet->name }}
                </h3>

                <div class="flex-1 overflow-y-auto space-y-2">
                    @forelse($gearPickerItems as $item)
                        <button wire:click="equipGear('{{ $item->id }}')"
                            class="w-full p-3 rounded-xl border border-stone-800 bg-stone-950/70 hover:border-amber-500/60 flex items-center justify-between text-left transition-colors">
                            <div>
                                <div class="text-sm font-bold text-amber-100">{{ $item->template->name ?? 'Przedmiot' }}</div>
                                <div class="text-[11px] text-stone-400">{{ $item->template->description ?? '' }}</div>
                            </div>
                            <i class="fa-solid fa-hand-pointer text-amber-400"></i>
                        </button>
                    @empty
                        <div class="text-center py-8 text-stone-500 text-xs">
                            Nie posiadasz żadnego przedmiotu tego typu w plecaku. Kup obrożę/charm u Handlarza.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    {{-- INFO MODAL --}}
    @if($showInfoModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/80 backdrop-blur-md p-4 animate-fade-in">
            <div class="bg-gradient-to-b from-stone-900 via-slate-900 to-stone-950 border-2 border-sky-500/50 rounded-2xl max-w-3xl w-full p-6 shadow-[0_0_50px_rgba(0,0,0,0.9)] relative max-h-[90vh] flex flex-col">
                <button wire:click="toggleInfoModal" class="absolute top-4 right-4 text-stone-400 hover:text-white text-xl">
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <h3 class="text-lg font-bold text-sky-200 mb-4 flex items-center gap-2" style="font-family: 'Cinzel', serif;">
                    <i class="fa-solid fa-circle-info"></i> Poradnik Chowańców
                </h3>

                <div class="flex-1 overflow-y-auto pr-2 space-y-6 text-xs text-stone-300">
                    <div>
                        <h4 class="text-amber-300 font-bold mb-2 uppercase tracking-wider">Szansa na wyklucie</h4>
                        <p class="mb-2">Tier jajka wpływa na szansę wyklucia konkretnego tieru chowańca:</p>
                        <div class="overflow-x-auto">
                            <table class="w-full text-[11px] border-collapse">
                                <thead>
                                    <tr class="border-b border-stone-700 text-stone-400">
                                        <th class="text-left py-1 pr-2">Jajko \ Pet</th>
                                        @foreach($tiers as $t => $meta)
                                            <th class="py-1 px-1">{{ $meta['name'] }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(config('pets.hatch_matrix') as $eggTier => $dist)
                                        <tr class="border-b border-stone-800">
                                            <td class="py-1 pr-2 font-bold text-amber-200">{{ $tiers[$eggTier]['name'] }}</td>
                                            @foreach($tiers as $t => $meta)
                                                <td class="py-1 px-1 text-center">{{ isset($dist[$t]) ? $dist[$t].'%' : '—' }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-purple-300 font-bold mb-2 uppercase tracking-wider">Fuzja</h4>
                        <p>2 pety tego samego tieru → 1 pet o tier wyższy. Bazowa szansa sukcesu rośnie z dojrzałością obu petów (+{{ config('pets.fusion_growth_stage_bonus_percent') }}% za każdą "ewolucję" osiągniętą przez którykolwiek z nich, max +20% gdy oba są "Formą Dorosłą"). Każda próba kosztuje złoto zależne od tieru wejściowego.</p>
                        <ul class="mt-2 space-y-1">
                            @foreach(config('pets.fusion_base_chance') as $tier => $chance)
                                <li>{{ $tiers[$tier]['name'] }} + {{ $tiers[$tier]['name'] }} → {{ $tiers[$tier + 1]['name'] }}: bazowo <strong class="text-emerald-400">{{ $chance }}%</strong>, koszt <strong class="text-yellow-300">{{ number_format((int) config("pets.fusion_cost_gold.{$tier}", 0)) }} złota</strong></li>
                            @endforeach
                        </ul>
                        <p class="mt-3 mb-1">Wynik nieudanej fuzji jest zawsze losowany na podstawie prawdopodobieństwa — nigdy nie wybierasz go sam:</p>
                        <ul class="space-y-1">
                            @php
                                $failureLabels = [
                                    'lose_both' => 'Utrata dwóch petów',
                                    'lose_one' => 'Utrata jednego peta',
                                    'devolve_both' => 'Cofnięcie rozwoju dwóch petów',
                                    'devolve_one' => 'Cofnięcie rozwoju jednego peta',
                                    'no_loss' => 'Brak utraty petów',
                                ];
                            @endphp
                            @foreach(config('pets.fusion_failure_outcomes', []) as $outcome => $chance)
                                <li>{{ $failureLabels[$outcome] ?? $outcome }}: <strong class="text-red-400">{{ $chance }}%</strong></li>
                            @endforeach
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-rose-300 font-bold mb-2 uppercase tracking-wider">Pasywka Rodzaju</h4>
                        <p class="mb-2">Każdy pet powstały z fuzji (fusion_count &gt; 0) i ustawiony jako aktywny towarzysz daje pasywny bonus bojowy zależny od jego Rodzaju, tieru i licznika fuzji: <strong class="text-amber-300">bonus% = fusion_count × {{ config('pets.fusion_count_archetype_bonus_percent', 1) }}% × tier</strong> (tłumiony poziomem tak samo jak reszta staty peta).</p>
                        <ul class="space-y-1">
                            @foreach(\App\Domain\Pets\PetArchetype::all() as $archetype)
                                <li><strong class="text-amber-200">{{ \App\Domain\Pets\PetArchetype::label($archetype) }}</strong>: {{ \App\Domain\Pets\PetArchetype::passiveDescription($archetype) }}</li>
                            @endforeach
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-emerald-300 font-bold mb-2 uppercase tracking-wider">Karmienie</h4>
                        <p class="mb-2">Każdy tier chowańca przyjmuje przedmioty od określonego minimalnego poziomu wzwyż (mocniejszy przedmiot zawsze można skarmić):</p>
                        <ul class="space-y-1">
                            @foreach($tiers as $t => $meta)
                                <li>{{ $meta['name'] }}: od poz. <strong class="text-amber-300">{{ $meta['feed_level_min'] }}+</strong></li>
                            @endforeach
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-cyan-300 font-bold mb-2 uppercase tracking-wider">Moc peta względem poziomu postaci</h4>
                        <p>Jeśli pet ma wyższy poziom niż Twoja postać, jego wkład do statystyk jest tłumiony proporcjonalnie (poziom postaci / poziom peta). Pet zawsze można założyć, karmić i sprzedać niezależnie od poziomu.</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- FUSION RESULT MODAL --}}
    @if($fusionResultModal)
        @php
            $fr = $fusionResultModal;
            $frSuccess = $fr['success'] ?? false;
            $frPet = $fr['pet'] ?? null;
            $frOutcome = $fr['outcome'] ?? ($frSuccess ? 'success' : 'lose_both');
            $frMeta = match ($frOutcome) {
                'success' => [
                    'title' => 'Fuzja udana!', 'icon' => 'fa-check',
                    'border' => 'border-emerald-500', 'iconBorder' => 'border-emerald-400', 'iconBg' => 'bg-emerald-950/40',
                    'iconText' => 'text-emerald-400', 'titleText' => 'text-emerald-300', 'btn' => 'bg-emerald-700 hover:bg-emerald-600',
                ],
                'no_loss' => [
                    'title' => 'Fuzja nieudana', 'icon' => 'fa-shield-heart',
                    'border' => 'border-sky-500', 'iconBorder' => 'border-sky-400', 'iconBg' => 'bg-sky-950/40',
                    'iconText' => 'text-sky-400', 'titleText' => 'text-sky-300', 'btn' => 'bg-sky-700 hover:bg-sky-600',
                ],
                'devolve_one' => [
                    'title' => 'Fuzja nieudana', 'icon' => 'fa-arrow-down',
                    'border' => 'border-yellow-500', 'iconBorder' => 'border-yellow-400', 'iconBg' => 'bg-yellow-950/40',
                    'iconText' => 'text-yellow-400', 'titleText' => 'text-yellow-300', 'btn' => 'bg-yellow-700 hover:bg-yellow-600',
                ],
                'devolve_both' => [
                    'title' => 'Fuzja nieudana', 'icon' => 'fa-arrows-down-to-line',
                    'border' => 'border-orange-500', 'iconBorder' => 'border-orange-400', 'iconBg' => 'bg-orange-950/40',
                    'iconText' => 'text-orange-400', 'titleText' => 'text-orange-300', 'btn' => 'bg-orange-700 hover:bg-orange-600',
                ],
                'lose_one' => [
                    'title' => 'Fuzja nieudana', 'icon' => 'fa-skull',
                    'border' => 'border-red-500', 'iconBorder' => 'border-red-400', 'iconBg' => 'bg-red-950/40',
                    'iconText' => 'text-red-400', 'titleText' => 'text-red-300', 'btn' => 'bg-red-800 hover:bg-red-700',
                ],
                default => [
                    'title' => 'Fuzja nieudana', 'icon' => 'fa-skull-crossbones',
                    'border' => 'border-red-500', 'iconBorder' => 'border-red-400', 'iconBg' => 'bg-red-950/40',
                    'iconText' => 'text-red-400', 'titleText' => 'text-red-300', 'btn' => 'bg-red-800 hover:bg-red-700',
                ],
            };
        @endphp
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-stone-950/85 backdrop-blur-md p-4 animate-fade-in">
            <div class="bg-gradient-to-b from-stone-900 via-slate-900 to-stone-950 border-2 {{ $frMeta['border'] }} rounded-2xl max-w-md w-full p-6 shadow-[0_0_60px_rgba(0,0,0,0.9)] relative text-center">
                <button wire:click="closeFusionResultModal" class="absolute top-4 right-4 text-stone-400 hover:text-white text-xl">
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <div class="w-20 h-20 mx-auto rounded-full border-4 {{ $frMeta['iconBorder'] }} {{ $frMeta['iconBg'] }} flex items-center justify-center mb-4 shadow-lg">
                    <i class="fa-solid {{ $frMeta['icon'] }} {{ $frMeta['iconText'] }} text-4xl"></i>
                </div>

                <h3 class="text-2xl font-bold mb-2 {{ $frMeta['titleText'] }}" style="font-family: 'Cinzel', serif;">
                    {{ $frMeta['title'] }}
                </h3>

                <p class="text-sm text-stone-300 mb-4">{{ $fr['message'] ?? '' }}</p>

                <div class="flex items-center justify-center gap-4 text-xs text-stone-500 mb-4">
                    @if(isset($fr['chance']))
                        <span>Szansa powodzenia: <strong class="text-amber-300">{{ number_format($fr['chance'], 2) }}%</strong></span>
                    @endif
                    @if(isset($fr['cost']))
                        <span>Koszt: <strong class="text-yellow-300">{{ number_format($fr['cost']) }} złota</strong></span>
                    @endif
                </div>

                @if($frSuccess && $frPet)
                    <div class="flex items-center gap-3 bg-stone-950/80 border border-emerald-500/30 rounded-xl p-3 text-left">
                        <div class="w-12 h-12 rounded-lg border-2 border-emerald-400 bg-stone-900 flex items-center justify-center text-xl shrink-0">
                            <img src="{{ route('assets.items', ['filename' => $frPet->icon]) }}" alt="{{ $frPet->name }}" class="w-9 h-9 object-contain">
                        </div>
                        <div>
                            <div class="font-bold text-amber-100">{{ $frPet->name }}</div>
                            <div class="text-xs text-stone-400">
                                {{ $frPet->tierName() }} · Poziom {{ $frPet->level }}
                                @if($frPet->archetype)
                                    · {{ \App\Domain\Pets\PetArchetype::label($frPet->archetype) }}
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <button wire:click="closeFusionResultModal" class="mt-5 w-full {{ $frMeta['btn'] }} text-white font-bold py-2.5 rounded-xl transition-colors">
                    Zamknij
                </button>
            </div>
        </div>
    @endif

    {{-- SELL MODAL --}}
    @if($sellingPetId)
        @php $sellPet = \App\Infrastructure\Persistence\Pet::find($sellingPetId); @endphp
        <div class="fixed inset-0 bg-black/80 flex items-center justify-center z-[100] p-4">
            <div class="bg-gray-900 border-2 border-yellow-600 rounded-lg shadow-2xl p-6 max-w-md w-full relative">
                <button wire:click="closeSellModal" class="absolute top-4 right-4 text-gray-400 hover:text-white">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>

                <h3 class="text-2xl font-bold text-yellow-500 mb-4 medieval-font">Wystaw na Targowisko</h3>

                @if($sellPet)
                    <div class="flex items-center space-x-3 mb-6 bg-gray-800 p-3 rounded border border-gray-700">
                        <div class="text-3xl flex items-center justify-center w-10 h-10">
                            <img src="{{ route('assets.items', ['filename' => $sellPet->icon]) }}" alt="{{ $sellPet->name }}" class="w-full h-full object-contain">
                        </div>
                        <div>
                            <div class="font-bold text-amber-200">{{ $sellPet->name }}</div>
                            <div class="text-xs text-gray-400">{{ $sellPet->tierName() }} | Poziom {{ $sellPet->level }}</div>
                        </div>
                    </div>
                @endif

                <div class="space-y-4 mb-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-1">Cena
                            <span class="text-amber-500/80 font-normal text-xs ml-1">(np. 2k, 2kk, 1.5kk, 2m)</span>
                        </label>
                        <input type="text" wire:model.live.debounce.150ms="sellPrice"
                               class="w-full bg-gray-800 border border-gray-600 rounded p-2 text-white font-mono text-base focus:border-amber-500 focus:outline-none"
                               placeholder="np. 1000, 2.5k, 2kk, 10m">
                        @php $parsedSellPrice = \App\Support\PriceParser::parse($sellPrice ?? ''); @endphp
                        @if($parsedSellPrice > 0)
                            <div class="mt-1.5 text-xs text-amber-300 font-semibold flex items-center justify-between bg-amber-950/50 border border-amber-600/40 rounded px-3 py-1.5 shadow-sm">
                                <span class="flex items-center gap-1.5">
                                    <i class="fa-solid fa-calculator text-amber-400"></i>
                                    <span>Przeliczona cena:</span>
                                </span>
                                <span class="font-bold text-yellow-300 text-sm tracking-wide">
                                    {{ \App\Support\PriceParser::format($parsedSellPrice) }}
                                    @if(($sellCurrency ?? 'gold') === 'gems')
                                        <i class="fa-solid fa-gem text-cyan-400 ml-1"></i>
                                    @else
                                        <i class="fa-solid fa-coins text-yellow-400 ml-1"></i>
                                    @endif
                                </span>
                            </div>
                        @endif
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-1">Waluta</label>
                        <select wire:model="sellCurrency" class="w-full bg-gray-800 border border-gray-600 rounded p-2 text-white">
                            <option value="gold">Złoto</option>
                            <option value="gems">Klejnoty</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-1">Czas trwania i opłata</label>
                        <select wire:model="sellDuration" class="w-full bg-gray-800 border border-gray-600 rounded p-2 text-white">
                            <option value="24">24 godziny (Koszt: 100 złota)</option>
                            <option value="48">48 godzin (Koszt: 250 złota)</option>
                            <option value="72">72 godziny (Koszt: 500 złota)</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button wire:click="closeSellModal" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded font-bold transition">Anuluj</button>
                    <button wire:click="sellPet" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-500 text-white rounded font-bold transition">Wystaw Chowańca</button>
                </div>
            </div>
        </div>
    @endif
</div>
