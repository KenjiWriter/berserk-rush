<div class="min-h-screen bg-gradient-to-b from-slate-950 via-green-950 to-slate-950 text-amber-100 relative overflow-hidden font-sans">
    <!-- Magical Background Effects -->
    <div class="absolute top-0 right-1/4 w-96 h-96 bg-green-600/20 rounded-full mix-blend-screen filter blur-3xl opacity-40 animate-pulse"></div>
    <div class="absolute bottom-1/4 left-1/4 w-96 h-96 bg-emerald-600/20 rounded-full mix-blend-screen filter blur-3xl opacity-40 animate-pulse" style="animation-delay: 2s;"></div>
    <div class="absolute inset-0 bg-[url('/img/noise.png')] opacity-[0.03] mix-blend-overlay"></div>

    <div class="relative container mx-auto px-4 py-8 min-h-screen flex flex-col">
        
        {{-- Header Bar --}}
        <div class="flex flex-col md:flex-row items-center justify-between mb-10 gap-4">
            <div class="bg-green-900/40 border border-green-500/30 rounded-xl p-4 shadow-2xl backdrop-blur-md flex items-center gap-4">
                <div class="text-4xl filter drop-shadow-[0_0_8px_rgba(34,197,94,0.8)]">🦹‍♂️</div>
                <div>
                    <h2 class="text-2xl font-bold text-green-100 medieval-font tracking-wider uppercase">Sanktuarium Czarnoksiężnika</h2>
                    <p class="text-sm text-green-300 font-medium">{{ $character->name }}</p>
                </div>
            </div>
            
            <div class="flex gap-4 items-center">
                <div class="bg-slate-900/60 border border-slate-500/40 text-amber-200 font-bold py-2 px-6 rounded-xl shadow-[0_0_15px_rgba(245,158,11,0.2)] backdrop-blur-md flex flex-col items-center">
                    <span class="text-xs text-slate-400 uppercase tracking-widest">Punkty Skilli</span>
                    <span class="text-2xl tracking-wide font-mono text-green-400">{{ $character->skill_points }}</span>
                </div>

                <button wire:click="backToHub" class="group relative overflow-hidden bg-slate-800/80 hover:bg-slate-700 text-amber-100 font-bold py-3 px-6 rounded-xl transition-all duration-300 shadow-[0_0_15px_rgba(0,0,0,0.5)] border border-slate-600 backdrop-blur-md flex items-center gap-2 h-full">
                    <span class="text-xl group-hover:-translate-x-1 transition-transform">🏰</span>
                    <span class="medieval-font tracking-wide">Powrót</span>
                </button>
            </div>
        </div>

        {{-- Main Content Area --}}
        <div class="max-w-6xl mx-auto w-full flex-1">
            <div class="text-center mb-10">
                <h3 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-300 via-emerald-400 to-green-600 medieval-font drop-shadow-lg mb-4">
                    Księga Zaklęć i Umiejętności
                </h3>
                <p class="text-green-200/70 max-w-2xl mx-auto text-lg">
                    "Za odpowiednią cenę nauczę cię sekretnych technik walki. Punkty umiejętności zdobywasz z każdym nowym poziomem doświadczenia."
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($allSkills as $skill)
                    @php
                        $mySkill = $mySkills[$skill->id] ?? null;
                        $isUnlocked = $mySkill !== null;
                        $canUnlock = !$isUnlocked && $character->level >= $skill->required_level && $character->skill_points >= $skill->unlock_cost;
                        $canUpgrade = $isUnlocked && $character->skill_points >= 1; // 1 pkt za upgrade
                    @endphp

                    <div class="bg-slate-900/60 border {{ $isUnlocked ? 'border-green-500/50' : 'border-slate-700/50' }} rounded-2xl p-6 shadow-xl backdrop-blur-sm relative overflow-hidden flex flex-col transition-all duration-300 hover:shadow-[0_0_20px_rgba(34,197,94,0.15)] hover:border-green-400/50">
                        @if($isUnlocked)
                            <div class="absolute top-0 right-0 bg-green-600 text-white text-xs font-bold px-3 py-1 rounded-bl-lg shadow-md uppercase tracking-wider">
                                Odblokowano (Lv. {{ $mySkill->level }})
                            </div>
                        @endif

                        <div class="flex gap-4 mb-4">
                            <div class="w-16 h-16 rounded-xl border-2 {{ $isUnlocked ? 'border-green-400 bg-green-900/50' : 'border-slate-600 bg-slate-800' }} flex items-center justify-center text-3xl shadow-inner shrink-0">
                                @if($skill->effect_type === 'poison')
                                    ☠️
                                @elseif($skill->effect_type === 'fire')
                                    🔥
                                @elseif($skill->effect_type === 'buff_phys_dmg')
                                    💪
                                @else
                                    ⚔️
                                @endif
                            </div>
                            <div>
                                <h4 class="text-xl font-bold {{ $isUnlocked ? 'text-green-300' : 'text-slate-300' }} medieval-font mb-1">{{ $skill->name }}</h4>
                                <div class="flex gap-2 text-xs font-mono mb-2">
                                    <span class="bg-black/40 px-2 py-0.5 rounded text-amber-200 border border-amber-900/50">Wymaga: {{ strtoupper($skill->required_weapon_type ?: 'Brak') }}</span>
                                    <span class="bg-black/40 px-2 py-0.5 rounded text-blue-300 border border-blue-900/50">CD: {{ $skill->base_cooldown }} rund</span>
                                </div>
                                <p class="text-sm text-slate-400 leading-relaxed">{{ $skill->description }}</p>
                            </div>
                        </div>

                        <div class="mt-auto pt-4 border-t border-slate-700/50 flex items-center justify-between">
                            @if(!$isUnlocked)
                                <div class="text-sm">
                                    <div class="text-slate-400 mb-1">Wymagania:</div>
                                    <div class="flex items-center gap-3 font-mono">
                                        <span class="{{ $character->level >= $skill->required_level ? 'text-green-400' : 'text-red-400' }}">Poziom: {{ $skill->required_level }}</span>
                                        <span class="{{ $character->skill_points >= $skill->unlock_cost ? 'text-green-400' : 'text-red-400' }}">Punkty: {{ $skill->unlock_cost }}</span>
                                    </div>
                                </div>
                                <button 
                                    wire:click="unlockSkill('{{ $skill->id }}')" 
                                    class="px-6 py-2 rounded-lg font-bold tracking-wider transition-all {{ $canUnlock ? 'bg-green-600 hover:bg-green-500 text-white shadow-[0_0_10px_rgba(34,197,94,0.4)]' : 'bg-slate-800 text-slate-500 cursor-not-allowed' }}"
                                    @if(!$canUnlock) disabled @endif
                                >
                                    Odblokuj
                                </button>
                            @else
                                <div class="text-sm">
                                    <div class="text-green-400 mb-1 font-bold">Obecny efekt:</div>
                                    <div class="text-slate-300 font-mono">
                                        Siła: <span class="text-amber-400">{{ ($skill->base_value + ($skill->scaling_value * ($mySkill->level - 1))) * 100 }}%</span>
                                        @if($skill->base_duration > 0)
                                            | Czas: <span class="text-amber-400">{{ $skill->base_duration }} rundy</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-xs text-slate-400">Koszt: 1 pkt</span>
                                    <button 
                                        wire:click="upgradeSkill('{{ $mySkill->id }}')" 
                                        class="px-6 py-2 rounded-lg font-bold tracking-wider transition-all {{ $canUpgrade ? 'bg-blue-600 hover:bg-blue-500 text-white shadow-[0_0_10px_rgba(59,130,246,0.4)]' : 'bg-slate-800 text-slate-500 cursor-not-allowed' }}"
                                        @if(!$canUpgrade) disabled @endif
                                    >
                                        Rozwiń
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
