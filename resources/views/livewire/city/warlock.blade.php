<div class="min-h-screen text-amber-100 relative overflow-hidden select-none"
     style="background: radial-gradient(circle at 50% 0%, #1c1917 0%, #0c0a09 60%, #050505 100%); font-family: 'Cinzel', serif;">
    
    {{-- Ambient Magical Glow Effects --}}
    <div class="absolute top-0 right-1/4 w-96 h-96 bg-emerald-600/15 rounded-full filter blur-3xl opacity-50 animate-pulse pointer-events-none"></div>
    <div class="absolute bottom-1/4 left-1/4 w-96 h-96 bg-teal-600/15 rounded-full filter blur-3xl opacity-50 animate-pulse pointer-events-none" style="animation-delay: 2s;"></div>

    <div class="relative container mx-auto px-4 py-6 sm:py-8 min-h-screen z-10 max-w-7xl flex flex-col">
        
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-8 border-b-2 border-amber-900/60 pb-6 bg-gradient-to-b from-stone-950/90 to-transparent p-4 rounded-2xl shadow-xl">
            <div class="flex items-center space-x-4">
                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-gradient-to-b from-emerald-800 via-stone-900 to-black border-2 border-emerald-500 flex items-center justify-center text-2xl sm:text-3xl text-emerald-400 shadow-[0_0_20px_rgba(16,185,129,0.4)] shrink-0">
                    <i class="fa-solid fa-hat-wizard"></i>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 via-teal-400 to-green-500 drop-shadow-md">SANKTUARIUM CZARNOKSIĘŻNIKA</h1>
                    <p class="text-xs sm:text-sm text-emerald-300/70 font-sans tracking-wide">Poznaj tajemnice sztuki wojennej, ulepszaj umiejętności bojowe i niszcz swoich wrogów</p>
                </div>
            </div>
            
            <div class="flex flex-wrap items-center gap-4">
                {{-- Skill Points Badge --}}
                <div class="bg-gradient-to-b from-stone-950 via-stone-900 to-black border-2 border-emerald-600/80 px-4 py-2 rounded-xl shadow-[inset_0_2px_4px_rgba(0,0,0,0.8),0_0_15px_rgba(16,185,129,0.2)] flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-emerald-950 border border-emerald-500 flex items-center justify-center text-emerald-400 text-base shrink-0">
                        <i class="fa-solid fa-sparkles"></i>
                    </div>
                    <div>
                        <span class="text-[9px] text-emerald-400 font-extrabold uppercase tracking-widest block leading-none">PUNKTY SKILLI</span>
                        <span class="text-xl sm:text-2xl font-black text-emerald-300 drop-shadow">{{ $character->skill_points }}</span>
                    </div>
                </div>

                {{-- Back Button --}}
                <button wire:click="backToHub" @click="$dispatch('location-leave', { text: 'Podróż do Miasta...', icon: 'fa-solid fa-archway' }); $dispatch('play-audio', { type: 'tab' })"
                    class="px-4 py-2.5 rounded-xl bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-amber-200 font-extrabold text-xs uppercase tracking-widest border-2 border-slate-700 hover:border-emerald-500 hover:text-emerald-100 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),0_4px_10px_rgba(0,0,0,0.8)] transition-all duration-200 flex items-center gap-2 group cursor-pointer">
                    <i class="fa-solid fa-archway text-amber-400 group-hover:scale-110 transition-transform"></i>
                    <span>Powrót do Miasta</span>
                </button>
            </div>
        </div>

        {{-- Main Spellbook Grid Section --}}
        <div class="w-full flex-1">
            <div class="text-center mb-8 bg-stone-950/80 border border-emerald-900/60 p-4 sm:p-6 rounded-2xl shadow-xl max-w-3xl mx-auto backdrop-blur-sm relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-600/10 rounded-full blur-3xl pointer-events-none"></div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 via-green-400 to-emerald-500 drop-shadow mb-2">
                    KSIĘGA ZAKLĘĆ I TECHNIK BOJOWYCH
                </h2>
                <p class="text-xs sm:text-sm text-emerald-200/80 font-sans leading-relaxed">
                    "Za odpowiednią cenę nauczę cię sekretnych technik niszczenia. Każdy zdobyty poziom przyznaje ci 1 Punkt Umiejętności do odblokowania i ulepszania twojej mocy."
                </p>
            </div>

            @php
                // Character base attack parameters for simulations
                $totalStr = $character->getTotalAttributes()['str'] ?? 1;
                $totalInt = $character->getTotalAttributes()['int'] ?? 1;
                $eqStats = $character->getEquipmentStats();
                
                $baseMin = 10 + ($totalStr * 2) + ($character->level * 1) + ($eqStats['attack_min'] ?? 0);
                $baseMax = 10 + ($totalStr * 2) + ($character->level * 1) + ($eqStats['attack_max'] ?? 0);
                $baseAvg = ($baseMin + $baseMax) / 2;
                $simMobHp = max(100, $character->level * 150);
            @endphp

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($allSkills as $skill)
                    @php
                        $mySkill = $mySkills[$skill->id] ?? null;
                        $isUnlocked = $mySkill !== null;
                        $level = $isUnlocked ? $mySkill->level : 1;
                        
                        $canUnlock = !$isUnlocked && $character->level >= $skill->required_level && $character->skill_points >= $skill->unlock_cost;
                        $canUpgrade = $isUnlocked && $character->skill_points >= 1;

                        // Calculation of current values & scaling
                        $currentValue = $skill->base_value + ($skill->scaling_value * ($level - 1));
                        $nextValue = $skill->base_value + ($skill->scaling_value * $level);

                        // Format Stat Power & Labels
                        $effectTitle = 'Siła Efektu';
                        $effectValueText = '';
                        $effectNextText = '';
                        $scalingText = '';
                        $statInfluenceText = 'Siła (STR) [+2 Atak/PKT], Poziom Postaci oraz Atak z Ekwipunku.';

                        if (in_array($skill->effect_type, ['direct_dmg', 'direct'])) {
                            $effectTitle = 'Obrażenia Natychmiastowe';
                            $effectValueText = round($currentValue * 100) . '% Obrażeń Broni';
                            $effectNextText = round($nextValue * 100) . '% Obrażeń Broni (+' . round($skill->scaling_value * 100) . '%)';
                            $scalingText = round($skill->scaling_value * 100) . '% Obrażeń Broni / Poziom';
                            $statInfluenceText = 'Siła (STR) [+2 Atak/PKT], Poziom Postaci i Broń.';
                        } elseif (in_array($skill->effect_type, ['buff_phys_dmg', 'buff_damage'])) {
                            $effectTitle = 'Wzmocnienie Obrażeń Fizycznych';
                            $effectValueText = '+' . round($currentValue * 100) . '% Fizycznych Obrażeń';
                            $effectNextText = '+' . round($nextValue * 100) . '% Fizycznych Obrażeń (+' . round($skill->scaling_value * 100) . '%)';
                            $scalingText = '+' . round($skill->scaling_value * 100) . '% Ataku / Poziom';
                            $statInfluenceText = 'Siła (STR), Poziom Postaci i Broń.';
                        } elseif (in_array($skill->effect_type, ['fire', 'dot_fire'])) {
                            $effectTitle = 'Podpalenie (Obrażenia Ogniowe)';
                            $effectValueText = number_format($currentValue * 100, 1) . '% Max HP / Turę';
                            $effectNextText = number_format($nextValue * 100, 1) . '% Max HP / Turę (+' . number_format($skill->scaling_value * 100, 1) . '%)';
                            $scalingText = '+' . number_format($skill->scaling_value * 100, 1) . '% Max HP / Poziom';
                            $statInfluenceText = 'Poziom Skilla oraz Maksymalne HP Przeciwnika.';
                        } elseif (in_array($skill->effect_type, ['poison', 'dot_poison'])) {
                            $effectTitle = 'Trucizna (Obrażenia w Czasie)';
                            $effectValueText = number_format($currentValue * 100, 1) . '% Aktualnego HP / Turę';
                            $effectNextText = number_format($nextValue * 100, 1) . '% Aktualnego HP / Turę (+' . number_format($skill->scaling_value * 100, 1) . '%)';
                            $scalingText = '+' . number_format($skill->scaling_value * 100, 1) . '% Akt. HP / Poziom';
                            $statInfluenceText = 'Poziom Skilla oraz Aktualne HP Przeciwnika.';
                        } else {
                            $effectTitle = 'Siła Umiejętności';
                            $effectValueText = round($currentValue * 100) . '%';
                            $effectNextText = round($nextValue * 100) . '% (+' . round($skill->scaling_value * 100) . '%)';
                            $scalingText = '+' . round($skill->scaling_value * 100) . '% / Poziom';
                        }

                        // Required weapon translation
                        $weaponName = 'Wszystkie Bronie';
                        $weaponIcon = 'fa-solid fa-shield-halved';
                        if ($skill->required_weapon_type === 'sword') { $weaponName = 'Miecz'; $weaponIcon = 'fa-solid fa-khanda'; }
                        elseif ($skill->required_weapon_type === 'axe') { $weaponName = 'Topór'; $weaponIcon = 'fa-solid fa-axe'; }
                        elseif ($skill->required_weapon_type === 'wand') { $weaponName = 'Różdżka'; $weaponIcon = 'fa-solid fa-wand-magic-sparkles'; }
                        elseif ($skill->required_weapon_type === 'bell') { $weaponName = 'Dzwon'; $weaponIcon = 'fa-solid fa-bell'; }
                        elseif ($skill->required_weapon_type === 'bow') { $weaponName = 'Łuk'; $weaponIcon = 'fa-solid fa-bow-arrow'; }

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

                    <div class="bg-gradient-to-b from-stone-900 via-stone-950 to-black border-2 rounded-2xl p-5 sm:p-6 shadow-[0_4px_20px_rgba(0,0,0,0.9)] transition-all duration-300 flex flex-col justify-between relative overflow-hidden group
                        {{ $isUnlocked ? 'border-emerald-600/80 hover:border-emerald-400 shadow-[0_0_15px_rgba(16,185,129,0.15)]' : 'border-stone-800/80 opacity-90 hover:border-stone-700' }}">
                        
                        {{-- Top Unlock Badge --}}
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                            <div class="flex items-center gap-2">
                                <span class="bg-stone-950/80 px-2.5 py-1 rounded-md text-[11px] font-bold text-amber-300 border border-amber-900/60 flex items-center gap-1.5">
                                    <i class="{{ $weaponIcon }}"></i>
                                    <span>{{ $weaponName }}</span>
                                </span>
                                <span class="bg-stone-950/80 px-2.5 py-1 rounded-md text-[11px] font-bold text-sky-300 border border-sky-900/60 flex items-center gap-1.5 font-sans">
                                    <i class="fa-regular fa-clock"></i>
                                    <span>CD: {{ $skill->base_cooldown }} Tur</span>
                                </span>
                                @if($skill->base_duration > 1)
                                    <span class="bg-stone-950/80 px-2.5 py-1 rounded-md text-[11px] font-bold text-purple-300 border border-purple-900/60 flex items-center gap-1.5 font-sans">
                                        <i class="fa-solid fa-hourglass-half"></i>
                                        <span>Czas: {{ $skill->base_duration }} Tur</span>
                                    </span>
                                @endif
                            </div>

                            <div>
                                @if($isUnlocked)
                                    <span class="px-3 py-1 bg-emerald-950 text-emerald-300 border border-emerald-600/80 rounded-lg text-xs font-black uppercase tracking-wider shadow-[0_0_10px_rgba(16,185,129,0.3)] inline-flex items-center gap-1">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <span>Poziom {{ $mySkill->level }}</span>
                                    </span>
                                @else
                                    <span class="px-3 py-1 bg-stone-950 text-stone-400 border border-stone-800 rounded-lg text-xs font-extrabold uppercase tracking-wider inline-flex items-center gap-1">
                                        <i class="fa-solid fa-lock text-stone-500"></i>
                                        <span>Zablokowano</span>
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Skill Header & Icon --}}
                        <div class="flex items-start space-x-4 mb-3">
                            <div class="w-16 h-16 rounded-xl border-2 {{ $isUnlocked ? 'border-emerald-500 bg-emerald-950/60' : 'border-stone-700 bg-stone-950' }} flex items-center justify-center text-3xl shrink-0 overflow-hidden shadow-[inset_0_2px_4px_rgba(0,0,0,0.8),0_0_12px_rgba(16,185,129,0.2)] relative">
                                @if($skill->icon)
                                    <img src="{{ route('assets.skills.icons', ['filename' => $skill->icon]) }}" class="w-full h-full object-contain p-1" alt="{{ $skill->name }}">
                                @elseif($skill->effect_type === 'poison' || $skill->effect_type === 'dot_poison')
                                    <i class="fa-solid fa-skull-crossbones text-emerald-400"></i>
                                @elseif($skill->effect_type === 'fire' || $skill->effect_type === 'dot_fire')
                                    <i class="fa-solid fa-fire-flame-curved text-amber-400"></i>
                                @elseif(in_array($skill->effect_type, ['buff_phys_dmg', 'buff_damage']))
                                    <i class="fa-solid fa-hand-fist text-yellow-400"></i>
                                @else
                                    <i class="fa-solid fa-khanda text-emerald-400"></i>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <h3 class="text-lg font-extrabold {{ $isUnlocked ? 'text-emerald-200' : 'text-stone-300' }} truncate leading-snug">
                                    {{ $skill->name }}
                                </h3>
                                <p class="text-xs text-stone-400 font-sans leading-relaxed mt-1">
                                    {{ $skill->description }}
                                </p>
                            </div>
                        </div>

                        {{-- Clear Skill Power Stats Box --}}
                        <div class="bg-stone-950/90 border border-emerald-900/60 rounded-xl p-3.5 my-3 font-sans shadow-inner">
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-emerald-400 font-extrabold uppercase tracking-wider text-[10px] flex items-center gap-1">
                                    <i class="fa-solid fa-bolt"></i>
                                    <span>{{ $effectTitle }}</span>
                                </span>
                                <span class="text-yellow-300 font-black text-sm drop-shadow">
                                    {{ $effectValueText }}
                                </span>
                            </div>

                            @if($isUnlocked)
                                <div class="text-[11px] text-stone-400 flex items-center justify-between border-t border-stone-800/80 pt-2 mt-2">
                                    <span>Kolejny Poziom (Lv. {{ $mySkill->level + 1 }}):</span>
                                    <span class="text-emerald-300 font-bold">{{ $effectNextText }}</span>
                                </div>
                            @else
                                <div class="text-[11px] text-stone-400 flex items-center justify-between border-t border-stone-800/80 pt-2 mt-2">
                                    <span>Przyrost na Poziom:</span>
                                    <span class="text-emerald-300 font-bold">{{ $scalingText }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- Damage Simulation Box --}}
                        <div class="bg-gradient-to-b from-stone-950 via-stone-900 to-stone-950 border border-emerald-900/60 rounded-xl p-3 my-2 font-sans shadow-inner">
                            <div class="flex items-center justify-between border-b border-emerald-900/40 pb-1.5 mb-2">
                                <span class="text-[10px] font-black uppercase text-amber-400 tracking-wider flex items-center gap-1.5" style="font-family: 'Cinzel', serif;">
                                    <i class="fa-solid fa-calculator text-amber-500"></i>
                                    <span>Szacowane Obrażenia Postaci</span>
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

                        {{-- Action Footer --}}
                        <div class="mt-auto pt-3 border-t border-amber-900/40 flex items-center justify-between gap-3">
                            @if(!$isUnlocked)
                                <div class="text-xs font-sans flex items-center gap-3">
                                    <span class="flex items-center gap-1 {{ $character->level >= $skill->required_level ? 'text-emerald-400 font-bold' : 'text-red-400 font-bold' }}">
                                        <i class="fa-solid fa-user-shield text-[10px]"></i>
                                        <span>Poz. {{ $skill->required_level }}</span>
                                    </span>
                                    <span class="flex items-center gap-1 {{ $character->skill_points >= $skill->unlock_cost ? 'text-emerald-400 font-bold' : 'text-red-400 font-bold' }}">
                                        <i class="fa-solid fa-sparkles text-[10px]"></i>
                                        <span>{{ $skill->unlock_cost }} PKT</span>
                                    </span>
                                </div>

                                <button wire:click="unlockSkill('{{ $skill->id }}')" 
                                        wire:loading.attr="disabled"
                                        class="px-5 py-2 rounded-xl font-extrabold text-xs uppercase tracking-wider transition-all duration-200 shadow-md border cursor-pointer flex items-center gap-2
                                        {{ $canUnlock ? 'bg-gradient-to-b from-emerald-700 via-emerald-800 to-emerald-950 hover:from-emerald-600 hover:to-emerald-900 text-emerald-100 border-emerald-500 shadow-[0_0_12px_rgba(16,185,129,0.4)]' : 'bg-stone-900 text-stone-500 border-stone-800 cursor-not-allowed opacity-60' }}"
                                        @if(!$canUnlock) disabled @endif>
                                    <i class="fa-solid fa-key"></i>
                                    <span>Odblokuj [{{ $skill->unlock_cost }} PKT]</span>
                                </button>
                            @else
                                <div class="text-xs font-sans text-stone-400 flex items-center gap-1">
                                    <i class="fa-solid fa-sparkles text-emerald-400"></i>
                                    <span>Koszt: <strong class="text-emerald-300">1 PKT</strong></span>
                                </div>

                                <button wire:click="upgradeSkill('{{ $mySkill->id }}')" 
                                        wire:loading.attr="disabled"
                                        class="px-5 py-2 rounded-xl font-extrabold text-xs uppercase tracking-wider transition-all duration-200 shadow-md border cursor-pointer flex items-center gap-2
                                        {{ $canUpgrade ? 'bg-gradient-to-b from-sky-700 via-sky-800 to-sky-950 hover:from-sky-600 hover:to-sky-900 text-sky-100 border-sky-500 shadow-[0_0_12px_rgba(56,189,248,0.4)]' : 'bg-stone-900 text-stone-500 border-stone-800 cursor-not-allowed opacity-60' }}"
                                        @if(!$canUpgrade) disabled @endif>
                                    <i class="fa-solid fa-circle-arrow-up"></i>
                                    <span>Ulepsz do Lv. {{ $mySkill->level + 1 }}</span>
                                </button>
                            @endif
                        </div>

                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
