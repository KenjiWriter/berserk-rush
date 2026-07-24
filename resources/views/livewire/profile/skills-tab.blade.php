<div class="space-y-4 font-sans select-none">
    <div class="flex items-center justify-between border-b border-amber-900/60 pb-3">
        <div>
            <h3 class="text-lg font-extrabold text-amber-400 uppercase tracking-widest flex items-center gap-2" style="font-family: 'Cinzel', serif;">
                <i class="fa-solid fa-khanda text-amber-500"></i>
                <span>Deck Umiejętności Bojowych</span>
            </h3>
            <p class="text-xs text-amber-200/60 font-sans mt-0.5">
                Wyposaż maksymalnie 3 aktywne umiejętności, które zastąpią podstawowe ataki w walce (PvE oraz PvP).
            </p>
        </div>
    </div>

    @if($skills->isEmpty())
        <div class="bg-stone-950/80 border-2 border-amber-900/60 rounded-2xl p-8 text-center shadow-xl">
            <div class="mb-3">
                <i class="fa-solid fa-hat-wizard text-emerald-600/50 text-5xl"></i>
            </div>
            <h4 class="text-base font-extrabold text-amber-300 mb-1" style="font-family: 'Cinzel', serif;">Brak Odblokowanych Umiejętności</h4>
            <p class="text-xs text-amber-200/60 max-w-md mx-auto">
                Nie nauczysz się potężnych czarów bez mistrza. Odwiedź <strong class="text-amber-300">Czarnoksiężnika w Mieście</strong>, aby nauczyć się nowych zdolności bojowych za Punkty Umiejętności.
            </p>
        </div>
    @else
        @php
            // Character base attack parameters for simulations
            $totalStr = $character->getTotalAttributes()['str'] ?? 1;
            $eqStats = $character->getEquipmentStats();
            
            $baseMin = 10 + ($totalStr * 2) + ($character->level * 1) + ($eqStats['attack_min'] ?? 0);
            $baseMax = 10 + ($totalStr * 2) + ($character->level * 1) + ($eqStats['attack_max'] ?? 0);
            $baseAvg = ($baseMin + $baseMax) / 2;
            $simMobHp = max(100, $character->level * 150);
        @endphp

        <div class="grid grid-cols-1 gap-4">
            @foreach($skills as $characterSkill)
                @php
                    $skill = $characterSkill->skill;
                    $isActive = $characterSkill->is_equipped;
                    $level = $characterSkill->level;
                    $currentValue = $skill->base_value + ($skill->scaling_value * ($level - 1));

                    // Weapon requirement mapping
                    $weaponName = 'Wszystkie Bronie';
                    $weaponIcon = 'fa-solid fa-shield-halved';
                    if ($skill->required_weapon_type === 'sword') { $weaponName = 'Miecz'; $weaponIcon = 'fa-solid fa-khanda'; }
                    elseif ($skill->required_weapon_type === 'axe') { $weaponName = 'Topór'; $weaponIcon = 'fa-solid fa-axe'; }
                    elseif ($skill->required_weapon_type === 'wand') { $weaponName = 'Różdżka'; $weaponIcon = 'fa-solid fa-wand-magic-sparkles'; }
                    elseif ($skill->required_weapon_type === 'bell') { $weaponName = 'Dzwon'; $weaponIcon = 'fa-solid fa-bell'; }
                    elseif ($skill->required_weapon_type === 'bow') { $weaponName = 'Łuk'; $weaponIcon = 'fa-solid fa-bow-arrow'; }

                    // Effect formatting
                    $effectTitle = 'Obrażenia';
                    $effectValueText = '';
                    $statInfluenceText = 'Siła (STR) [+2 Atak/PKT], Poziom i Broń.';

                    if (in_array($skill->effect_type, ['direct_dmg', 'direct'])) {
                        $effectTitle = 'Obrażenia Bezpośrednie';
                        $effectValueText = round($currentValue * 100) . '% Obrażeń Broni';
                        $statInfluenceText = 'Siła (STR) [+2 Atak/PKT], Poziom Postaci i Broń.';
                    } elseif (in_array($skill->effect_type, ['buff_phys_dmg', 'buff_damage'])) {
                        $effectTitle = 'Wzmocnienie Fizyczne';
                        $effectValueText = '+' . round($currentValue * 100) . '% Obrażeń';
                        $statInfluenceText = 'Siła (STR), Poziom Postaci i Broń.';
                    } elseif (in_array($skill->effect_type, ['fire', 'dot_fire'])) {
                        $effectTitle = 'Podpalenie';
                        $effectValueText = number_format($currentValue * 100, 1) . '% Max HP / Turę';
                        $statInfluenceText = 'Poziom Skilla i Max HP Przeciwnika.';
                    } elseif (in_array($skill->effect_type, ['poison', 'dot_poison'])) {
                        $effectTitle = 'Trucizna';
                        $effectValueText = number_format($currentValue * 100, 1) . '% Akt. HP / Turę';
                        $statInfluenceText = 'Poziom Skilla i Akt. HP Przeciwnika.';
                    } else {
                        $effectTitle = 'Moc';
                        $effectValueText = round($currentValue * 100) . '%';
                    }

                    // Damage Simulations based on current stat values
                    $simMinDamage = round($baseMin * $currentValue);
                    $simMaxDamage = round($baseMax * $currentValue);
                    $simAvgDamage = round($baseAvg * $currentValue);
                    $simCritDamage = round($baseAvg * $currentValue * 1.5);

                    $simBuffBonus = round($baseAvg * $currentValue);
                    $simBuffedTotalMin = round($baseMin * (1 + $currentValue));
                    $simBuffedTotalMax = round($baseMax * (1 + $currentValue));

                    $simDotPerTurn = round($simMobHp * $currentValue);
                    $simDotTotal = round($simMobHp * $currentValue * $skill->base_duration);
                @endphp

                <div class="bg-gradient-to-b from-stone-900 via-stone-950 to-black border-2 rounded-xl p-4 flex flex-col justify-between gap-3 transition-all duration-200 shadow-md relative overflow-hidden
                    {{ $isActive ? 'border-amber-500 shadow-[0_0_15px_rgba(245,158,11,0.25)]' : 'border-stone-800 hover:border-stone-700' }}">
                    
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div class="flex items-start space-x-3.5 flex-1 min-w-0">
                            {{-- Icon Frame --}}
                            <div class="w-12 h-12 rounded-xl border-2 border-amber-500/80 bg-stone-950 flex items-center justify-center text-2xl shrink-0 overflow-hidden shadow-inner relative">
                                @if($skill->icon)
                                    <img src="{{ route('assets.skills.icons', ['filename' => $skill->icon]) }}" class="w-full h-full object-contain p-0.5" alt="{{ $skill->name }}">
                                @elseif($skill->effect_type === 'poison' || $skill->effect_type === 'dot_poison')
                                    <i class="fa-solid fa-skull-crossbones text-emerald-400"></i>
                                @elseif($skill->effect_type === 'fire' || $skill->effect_type === 'dot_fire')
                                    <i class="fa-solid fa-fire-flame-curved text-amber-400"></i>
                                @elseif(in_array($skill->effect_type, ['buff_phys_dmg', 'buff_damage']))
                                    <i class="fa-solid fa-hand-fist text-yellow-400"></i>
                                @else
                                    <i class="fa-solid fa-khanda text-amber-400"></i>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <h4 class="font-extrabold text-amber-100 text-sm tracking-wide" style="font-family: 'Cinzel', serif;">
                                        {{ $skill->name }}
                                    </h4>
                                    <span class="bg-emerald-950 text-emerald-300 border border-emerald-600/80 px-2 py-0.5 rounded text-[10px] font-extrabold uppercase">
                                        Poziom {{ $level }}
                                    </span>
                                    <span class="bg-stone-900 border border-amber-900/60 text-amber-300 px-2 py-0.5 rounded text-[10px] font-bold flex items-center gap-1">
                                        <i class="{{ $weaponIcon }}"></i> {{ $weaponName }}
                                    </span>
                                </div>

                                <p class="text-xs text-stone-400 mb-2 leading-relaxed">
                                    {{ $skill->description }}
                                </p>

                                <div class="flex flex-wrap items-center gap-2 text-[11px]">
                                    <span class="bg-stone-900 border border-emerald-900/60 text-emerald-300 px-2.5 py-1 rounded-md font-bold">
                                        <i class="fa-solid fa-bolt mr-1"></i>{{ $effectTitle }}: <strong class="text-yellow-300 font-extrabold">{{ $effectValueText }}</strong>
                                    </span>
                                    <span class="bg-stone-900 border border-sky-900/60 text-sky-300 px-2.5 py-1 rounded-md font-bold">
                                        <i class="fa-regular fa-clock mr-1"></i>CD: {{ $skill->base_cooldown }} Tur
                                    </span>
                                    @if($skill->base_duration > 1)
                                        <span class="bg-stone-900 border border-purple-900/60 text-purple-300 px-2.5 py-1 rounded-md font-bold">
                                            <i class="fa-solid fa-hourglass-half mr-1"></i>Czas: {{ $skill->base_duration }} Tur
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <button wire:click="equipSkill('{{ $characterSkill->id }}')" 
                                wire:loading.attr="disabled"
                                class="px-5 py-2.5 rounded-xl font-extrabold text-xs uppercase tracking-wider transition-all duration-200 shadow-md border cursor-pointer w-full sm:w-auto shrink-0 flex items-center justify-center gap-2
                                {{ $isActive ? 'bg-gradient-to-b from-amber-700 via-amber-800 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_10px_rgba(245,158,11,0.3)]' : 'bg-gradient-to-b from-stone-800 to-stone-950 text-stone-300 border-stone-700 hover:border-amber-500 hover:text-amber-200' }}">
                            @if($isActive)
                                <i class="fa-solid fa-circle-check text-yellow-300"></i>
                                <span>Wyposażono</span>
                            @else
                                <i class="fa-solid fa-plus text-stone-400"></i>
                                <span>Wyposaż</span>
                            @endif
                        </button>
                    </div>

                    {{-- Simulation & Stat Influence Box --}}
                    <div class="bg-gradient-to-b from-stone-950 via-stone-900 to-stone-950 border border-amber-900/40 rounded-xl p-3 font-sans shadow-inner">
                        <div class="flex items-center justify-between border-b border-amber-900/40 pb-1.5 mb-2">
                            <span class="text-[10px] font-black uppercase text-amber-400 tracking-wider flex items-center gap-1.5" style="font-family: 'Cinzel', serif;">
                                <i class="fa-solid fa-calculator text-amber-500"></i>
                                <span>Szacowany Wpływ w Walce</span>
                            </span>
                            <span class="text-[9px] text-stone-400 bg-stone-900 px-2 py-0.5 rounded border border-stone-800 font-mono">
                                Atak Bazowy: {{ $baseMin }}-{{ $baseMax }}
                            </span>
                        </div>

                        @if(in_array($skill->effect_type, ['direct_dmg', 'direct']))
                            <div class="grid grid-cols-3 gap-2 text-center my-1.5">
                                <div class="bg-stone-950 p-1.5 rounded-lg border border-amber-900/40">
                                    <span class="text-[9px] text-stone-400 uppercase tracking-widest block leading-none mb-1">Zakres DMG</span>
                                    <span class="text-xs font-extrabold text-amber-200 font-mono">{{ $simMinDamage }} - {{ $simMaxDamage }}</span>
                                </div>
                                <div class="bg-stone-950 p-1.5 rounded-lg border border-emerald-900/50">
                                    <span class="text-[9px] text-emerald-400 uppercase tracking-widest block leading-none mb-1">Średnio</span>
                                    <span class="text-xs font-black text-emerald-300 font-mono">{{ $simAvgDamage }} DMG</span>
                                </div>
                                <div class="bg-stone-950 p-1.5 rounded-lg border border-yellow-900/50">
                                    <span class="text-[9px] text-yellow-400 uppercase tracking-widest block leading-none mb-1">Krytyk</span>
                                    <span class="text-xs font-extrabold text-yellow-300 font-mono">{{ $simCritDamage }} DMG</span>
                                </div>
                            </div>
                        @elseif(in_array($skill->effect_type, ['buff_phys_dmg', 'buff_damage']))
                            <div class="grid grid-cols-2 gap-2 text-center my-1.5">
                                <div class="bg-stone-950 p-1.5 rounded-lg border border-emerald-900/50">
                                    <span class="text-[9px] text-emerald-400 uppercase tracking-widest block leading-none mb-1">Premia / Atak</span>
                                    <span class="text-xs font-extrabold text-emerald-300 font-mono">+{{ $simBuffBonus }} DMG</span>
                                </div>
                                <div class="bg-stone-950 p-1.5 rounded-lg border border-amber-900/50">
                                    <span class="text-[9px] text-amber-400 uppercase tracking-widest block leading-none mb-1">Atak w Buffie</span>
                                    <span class="text-xs font-extrabold text-yellow-300 font-mono">{{ $simBuffedTotalMin }}-{{ $simBuffedTotalMax }} DMG</span>
                                </div>
                            </div>
                        @elseif(in_array($skill->effect_type, ['fire', 'dot_fire']))
                            <div class="grid grid-cols-2 gap-2 text-center my-1.5">
                                <div class="bg-stone-950 p-1.5 rounded-lg border border-amber-900/50">
                                    <span class="text-[9px] text-amber-400 uppercase tracking-widest block leading-none mb-1">Na Turę (Mob {{ $simMobHp }} HP)</span>
                                    <span class="text-xs font-extrabold text-amber-200 font-mono">{{ $simDotPerTurn }} DMG</span>
                                </div>
                                <div class="bg-stone-950 p-1.5 rounded-lg border border-red-900/50">
                                    <span class="text-[9px] text-red-400 uppercase tracking-widest block leading-none mb-1">Łącznie ({{ $skill->base_duration }} Tury)</span>
                                    <span class="text-xs font-extrabold text-red-300 font-mono">{{ $simDotTotal }} DMG</span>
                                </div>
                            </div>
                        @elseif(in_array($skill->effect_type, ['poison', 'dot_poison']))
                            <div class="grid grid-cols-2 gap-2 text-center my-1.5">
                                <div class="bg-stone-950 p-1.5 rounded-lg border border-emerald-900/50">
                                    <span class="text-[9px] text-emerald-400 uppercase tracking-widest block leading-none mb-1">1. Tura (Mob {{ $simMobHp }} HP)</span>
                                    <span class="text-xs font-extrabold text-emerald-200 font-mono">{{ $simDotPerTurn }} DMG</span>
                                </div>
                                <div class="bg-stone-950 p-1.5 rounded-lg border border-green-900/50">
                                    <span class="text-[9px] text-green-400 uppercase tracking-widest block leading-none mb-1">Łącznie ({{ $skill->base_duration }} Tury)</span>
                                    <span class="text-xs font-extrabold text-green-300 font-mono">~{{ $simDotTotal }} DMG</span>
                                </div>
                            </div>
                        @endif

                        <div class="mt-1.5 text-[10px] text-stone-400 border-t border-stone-800/60 pt-1.5 flex items-center justify-between">
                            <span class="flex items-center gap-1 text-amber-300 font-bold">
                                <i class="fa-solid fa-circle-info text-amber-500"></i>
                                <span>Wpływ statystyk:</span>
                            </span>
                            <span class="text-stone-300 font-semibold">
                                {{ $statInfluenceText }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
