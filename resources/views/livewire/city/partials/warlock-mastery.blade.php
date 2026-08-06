@php
    $xpPct = $championXpTarget > 0 ? min(100, round(($character->xp / $championXpTarget) * 100, 1)) : 0;
    $xpFull = $character->xp >= $championXpTarget;
    $materialsFulfilled = collect($championMaterials)->every(fn ($row) => ($row['deposited'] ?? 0) >= ($row['required'] ?? 0));
    $runicFulfilled = ($ownedRunicShards ?? 0) >= ($reqRunicShards ?? 0);
    $atCap = $character->champion_level >= \App\Application\Mastery\ChampionService::LEVEL_CAP;
    $canLevelUp = !$atCap && $xpFull && $materialsFulfilled && $runicFulfilled;
    $runicPct = ($reqRunicShards ?? 0) > 0 ? min(100, round((($ownedRunicShards ?? 0) / $reqRunicShards) * 100)) : 100;
@endphp

<div class="w-full flex-1">
    {{-- Champion Level Header --}}
    <div class="text-center mb-6 bg-stone-950/80 border border-sky-900/60 p-4 sm:p-6 rounded-2xl shadow-xl max-w-3xl mx-auto backdrop-blur-sm relative overflow-hidden">
        <div class="absolute top-0 right-0 w-32 h-32 bg-sky-600/10 rounded-full blur-3xl pointer-events-none"></div>
        <h2 class="text-2xl sm:text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-sky-300 via-blue-400 to-sky-500 drop-shadow mb-2">
            ŚCIEŻKA MISTRZOSTWA
        </h2>
        <p class="text-xs sm:text-sm text-sky-200/80 font-sans leading-relaxed mb-3">
            "Osiągnąłeś szczyt śmiertelnych możliwości. Teraz udowodnij, że jesteś czymś więcej."
        </p>
        <div class="text-4xl sm:text-5xl font-black tracking-wide" style="font-family: 'Cinzel', serif;">
            <span class="text-amber-300">99</span><span class="text-sky-400">({{ $character->champion_level }})</span>
            @if($atCap)
                <span class="ml-2 align-middle text-xs px-2 py-1 rounded-lg bg-amber-600 text-stone-950 font-black uppercase tracking-wider">Ukończono</span>
            @endif
        </div>
    </div>

    <div class="max-w-3xl mx-auto flex flex-col gap-6">

        {{-- PK Badge + XP bar --}}
        <div class="bg-stone-950/90 border border-sky-900/60 rounded-2xl p-4 sm:p-5 shadow-inner font-sans">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2 text-sky-300 font-extrabold text-xs uppercase tracking-widest">
                    <i class="fa-solid fa-gauge-high"></i>
                    <span>Doświadczenie Championa</span>
                </div>
                <div class="bg-sky-950 border border-sky-600 px-3 py-1 rounded-lg flex items-center gap-2">
                    <i class="fa-solid fa-star text-sky-400 text-xs"></i>
                    <span class="text-sky-200 font-black text-sm">{{ $character->champion_points }} PK</span>
                </div>
            </div>

            @if($atCap)
                <div class="text-center text-amber-300 font-bold text-sm py-2">Osiągnięto maksymalny poziom Mistrzostwa.</div>
            @else
                <div class="w-full h-4 bg-stone-900 rounded-full border border-sky-900/60 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500 {{ $xpFull ? 'bg-gradient-to-r from-amber-500 to-yellow-400 animate-pulse' : 'bg-gradient-to-r from-sky-600 to-blue-400' }}" style="width: {{ $xpPct }}%"></div>
                </div>
                <div class="flex items-center justify-between mt-1.5 text-[11px] text-stone-400">
                    <span>{{ \App\Support\NumberHelper::formatShort($character->xp) }} / {{ \App\Support\NumberHelper::formatShort($championXpTarget) }} EXP</span>
                    <span class="font-bold {{ $xpFull ? 'text-amber-300' : 'text-sky-300' }}">{{ $xpPct }}%</span>
                </div>
            @endif
        </div>

        {{-- Materials tribute --}}
        @if(!$atCap)
            <div class="bg-stone-950/90 border border-sky-900/60 rounded-2xl p-4 sm:p-5 shadow-inner font-sans">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2 text-sky-300 font-extrabold text-xs uppercase tracking-widest">
                        <i class="fa-solid fa-boxes-stacked"></i>
                        <span>Wymagane Ulepszacze & Odłamki</span>
                    </div>
                </div>

                {{-- Runic Shard Requirement Card --}}
                <div class="bg-purple-950/40 border border-purple-600/60 rounded-xl p-3 flex items-center gap-3 mb-3 shadow-[0_0_15px_rgba(168,85,247,0.15)]">
                    <div class="w-12 h-12 rounded-lg border-2 {{ $runicFulfilled ? 'border-emerald-500 bg-emerald-950/50' : 'border-purple-500 bg-purple-950/70' }} flex items-center justify-center shrink-0 overflow-hidden shadow-[0_0_10px_rgba(168,85,247,0.3)]">
                        <img src="{{ route('assets.items', ['filename' => 'runiczny-odlamek.png']) }}" class="w-full h-full object-contain p-1" alt="Runiczny Odłamek">
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-bold text-purple-200 flex items-center gap-1.5">
                                <span>Runiczny Odłamek</span>
                                <span class="text-[9px] bg-purple-900/80 border border-purple-500/50 text-purple-300 px-1.5 py-0.2 rounded font-mono">Przetapianie u Kowala</span>
                            </span>
                            <span class="text-[10px] text-purple-400 shrink-0 font-semibold uppercase tracking-wider">Wymóg Czempiona</span>
                        </div>
                        <div class="w-full h-2 bg-stone-950 rounded-full border border-purple-900/60 overflow-hidden mt-1.5">
                            <div class="h-full rounded-full transition-all duration-500 {{ $runicFulfilled ? 'bg-emerald-500' : 'bg-gradient-to-r from-purple-600 to-indigo-500' }}" style="width: {{ $runicPct }}%"></div>
                        </div>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-[10px] font-mono text-purple-300 font-bold">{{ number_format($ownedRunicShards ?? 0) }} / {{ number_format($reqRunicShards ?? 0) }} szt.</span>
                            @if($runicFulfilled)
                                <span class="text-[10px] text-emerald-400 font-bold flex items-center gap-1">
                                    <i class="fa-solid fa-circle-check text-xs"></i> Odłamki gotowe
                                </span>
                            @else
                                <span class="text-[10px] text-purple-300/80 italic">Automatycznie pobierane przy awansie</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @forelse($championMaterials as $row)
                        @php
                            $remaining = max(0, $row['required'] - $row['deposited']);
                            $donateQty = min($row['owned'], $remaining);
                            $rowPct = $row['required'] > 0 ? min(100, round(($row['deposited'] / $row['required']) * 100)) : 100;
                            $rowDone = $remaining <= 0;
                        @endphp
                        <div class="bg-stone-900/80 border {{ $rowDone ? 'border-emerald-700/70' : 'border-stone-800' }} rounded-xl p-3 flex items-center gap-3">
                            <div class="w-12 h-12 rounded-lg border-2 {{ $rowDone ? 'border-emerald-500 bg-emerald-950/50' : 'border-stone-700 bg-stone-950' }} flex items-center justify-center shrink-0 overflow-hidden">
                                @if($row['icon'])
                                    <img src="{{ route('assets.items', ['filename' => $row['icon']]) }}" class="w-full h-full object-contain p-1" alt="{{ $row['name'] }}">
                                @else
                                    <i class="fa-solid fa-cube text-stone-500"></i>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-bold text-stone-200 truncate">{{ $row['name'] }}</span>
                                    <span class="text-[10px] text-stone-500 shrink-0">Mapa T{{ $row['tier'] }}</span>
                                </div>
                                <div class="w-full h-2 bg-stone-950 rounded-full border border-stone-800 overflow-hidden mt-1.5">
                                    <div class="h-full rounded-full {{ $rowDone ? 'bg-emerald-500' : 'bg-sky-500' }}" style="width: {{ $rowPct }}%"></div>
                                </div>
                                <div class="flex items-center justify-between mt-1">
                                    <span class="text-[10px] font-mono text-stone-400">{{ number_format($row['deposited']) }} / {{ number_format($row['required']) }}</span>
                                    @if($rowDone)
                                        <i class="fa-solid fa-circle-check text-emerald-400 text-xs"></i>
                                    @else
                                        <button wire:click="donateMaterial('{{ $row['template_id'] }}', {{ $donateQty }})"
                                                wire:loading.attr="disabled"
                                                class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase tracking-wide border cursor-pointer
                                                {{ $donateQty > 0 ? 'bg-sky-800 border-sky-500 text-sky-100 hover:bg-sky-700' : 'bg-stone-900 border-stone-800 text-stone-600 cursor-not-allowed' }}"
                                                @if($donateQty <= 0) disabled @endif>
                                            Wpłać ({{ $row['owned'] }})
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center text-stone-500 text-xs py-4">Brak wylosowanych wymagań.</div>
                    @endforelse
                </div>
            </div>

            {{-- Level up button --}}
            <button wire:click="levelUpChampion" wire:loading.attr="disabled"
                    class="w-full py-4 rounded-2xl font-extrabold text-sm uppercase tracking-widest border-2 transition-all duration-200 shadow-lg flex items-center justify-center gap-3 cursor-pointer
                    {{ $canLevelUp ? 'bg-gradient-to-b from-amber-600 via-yellow-600 to-amber-800 hover:from-amber-500 hover:to-yellow-700 text-stone-950 border-yellow-300 shadow-[0_0_25px_rgba(251,191,36,0.5)] animate-pulse' : 'bg-stone-900 text-stone-500 border-stone-800 cursor-not-allowed opacity-60' }}"
                    @if(!$canLevelUp) disabled @endif>
                <i class="fa-solid fa-angles-up"></i>
                <span>Awansuj Championa do 99({{ $character->champion_level + 1 }})</span>
            </button>
        @endif

        {{-- Champion skill tree --}}
        <div class="bg-stone-950/90 border border-sky-900/60 rounded-2xl p-4 sm:p-5 shadow-inner font-sans">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2 text-sky-300 font-extrabold text-xs uppercase tracking-widest">
                    <i class="fa-solid fa-sitemap"></i>
                    <span>Drzewko Rozwoju Championa</span>
                </div>
                <span class="text-[10px] text-stone-500">Max 10 PKT / umiejętność, max {{ \App\Application\Mastery\ChampionService::LEVEL_CAP }} PKT łącznie</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($championSkillsList as $skill)
                    @php
                        $invested = $myChampionSkills[$skill->id]->points ?? 0;
                        $isMaxed = $invested >= $skill->max_points;
                        $canInvest = !$isMaxed && $character->champion_points > 0;
                    @endphp
                    <div class="bg-stone-900/80 border {{ $isMaxed ? 'border-amber-600/70' : 'border-stone-800' }} rounded-xl p-3 flex items-center gap-3">
                        <div class="w-11 h-11 rounded-lg border-2 {{ $isMaxed ? 'border-amber-500 bg-amber-950/50' : 'border-sky-700 bg-sky-950/50' }} flex items-center justify-center shrink-0 text-lg {{ $isMaxed ? 'text-amber-300' : 'text-sky-300' }}">
                            <i class="{{ $skill->icon ?? 'fa-solid fa-star' }}"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-bold text-stone-100">{{ $skill->name }}</span>
                                <span class="text-[10px] font-mono {{ $isMaxed ? 'text-amber-300' : 'text-sky-300' }}">{{ $invested }}/{{ $skill->max_points }}</span>
                            </div>
                            <p class="text-[10px] text-stone-400 leading-snug mt-0.5">{{ $skill->description }}</p>
                        </div>
                        <button wire:click="investChampionPoint('{{ $skill->id }}')" wire:loading.attr="disabled"
                                class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 border font-black text-sm cursor-pointer
                                {{ $canInvest ? 'bg-sky-800 border-sky-500 text-sky-100 hover:bg-sky-700' : 'bg-stone-950 border-stone-800 text-stone-700 cursor-not-allowed' }}"
                                @if(!$canInvest) disabled @endif>
                            +
                        </button>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Reset --}}
        <div class="bg-stone-950/70 border border-stone-800 rounded-2xl p-4 flex flex-col sm:flex-row items-center justify-between gap-3 font-sans">
            <p class="text-[11px] text-stone-400 leading-relaxed">
                Reset drzewka Mistrzostwa zwraca wszystkie wydane Punkty Mistrzostwa do ponownego rozdania. Dostępny raz na miesiąc.
            </p>
            <button wire:click="resetChampionSkills" wire:loading.attr="disabled"
                    onclick="return confirm('Zresetować drzewko Mistrzostwa za {{ number_format($championResetGoldCost) }} złota?')"
                    class="px-4 py-2.5 rounded-xl font-extrabold text-xs uppercase tracking-widest border-2 shrink-0 flex items-center gap-2 cursor-pointer
                    {{ $championResetAvailable ? 'bg-gradient-to-b from-red-800 via-red-900 to-stone-950 hover:from-red-700 hover:to-red-900 text-red-100 border-red-600' : 'bg-stone-900 text-stone-500 border-stone-800 cursor-not-allowed opacity-60' }}"
                    @if(!$championResetAvailable) disabled @endif>
                <i class="fa-solid fa-rotate-left"></i>
                <span>{{ $championResetAvailable ? 'Resetuj (' . number_format($championResetGoldCost) . ' Gold)' : 'Dostępne za ' . $championResetDaysLeft . ' dni' }}</span>
            </button>
        </div>
    </div>
</div>
