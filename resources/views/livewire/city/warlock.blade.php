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
            
            <div class="flex flex-wrap items-center gap-3">
                {{-- Skill Points Badge --}}
                <div class="bg-gradient-to-b from-stone-950 via-stone-900 to-black border-2 border-emerald-600/80 px-3.5 py-2 rounded-xl shadow-[inset_0_2px_4px_rgba(0,0,0,0.8),0_0_15px_rgba(16,185,129,0.2)] flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-950 border border-emerald-500 flex items-center justify-center text-emerald-400 text-sm shrink-0">
                        <i class="fa-solid fa-sparkles"></i>
                    </div>
                    <div>
                        <span class="text-[9px] text-emerald-400 font-extrabold uppercase tracking-widest block leading-none">PKT SKILLI</span>
                        <span class="text-lg font-black text-emerald-300 drop-shadow">{{ $character->skill_points }}</span>
                    </div>
                </div>

                {{-- Skill Books Badge --}}
                <div class="bg-gradient-to-b from-stone-950 via-stone-900 to-black border-2 border-sky-600/80 px-3.5 py-2 rounded-xl shadow-[inset_0_2px_4px_rgba(0,0,0,0.8),0_0_15px_rgba(56,189,248,0.2)] flex items-center gap-2.5" title="Księgi Umiejętności w ekwipunku">
                    <div class="w-8 h-8 rounded-lg bg-sky-950 border border-sky-500 flex items-center justify-center text-sky-400 text-sm shrink-0">
                        <i class="fa-solid fa-book-bookmark"></i>
                    </div>
                    <div>
                        <span class="text-[9px] text-sky-400 font-extrabold uppercase tracking-widest block leading-none">KSIĘGI (M)</span>
                        <span class="text-lg font-black text-sky-300 drop-shadow">{{ $skillBooksCount }}</span>
                    </div>
                </div>

                {{-- Soul Stones Badge --}}
                <div class="bg-gradient-to-b from-stone-950 via-stone-900 to-black border-2 border-amber-600/80 px-3.5 py-2 rounded-xl shadow-[inset_0_2px_4px_rgba(0,0,0,0.8),0_0_15px_rgba(245,158,11,0.2)] flex items-center gap-2.5" title="Kamienie Duchowe w ekwipunku">
                    <div class="w-8 h-8 rounded-lg bg-amber-950 border border-amber-500 flex items-center justify-center text-amber-400 text-sm shrink-0">
                        <i class="fa-solid fa-gem"></i>
                    </div>
                    <div>
                        <span class="text-[9px] text-amber-400 font-extrabold uppercase tracking-widest block leading-none">KAMIENIE (G)</span>
                        <span class="text-lg font-black text-amber-300 drop-shadow">{{ $soulStonesCount }}</span>
                    </div>
                </div>

                {{-- Exorcism Scrolls Badge / Toggle --}}
                <button type="button" wire:click="toggleExorcismScroll"
                    class="bg-gradient-to-b from-stone-950 via-stone-900 to-black border-2 px-3.5 py-2 rounded-xl shadow-[inset_0_2px_4px_rgba(0,0,0,0.8)] flex items-center gap-2.5 cursor-pointer transition-all {{ $useExorcismScroll ? 'border-fuchsia-400 shadow-[0_0_15px_rgba(232,121,249,0.6)]' : 'border-fuchsia-700/80 shadow-[0_0_15px_rgba(232,121,249,0.2)]' }}"
                    title="Zwój Egzorcyzmu: gwarantuje 100% szans powodzenia następnej próby ulepszenia skilla na etapie Mistrza (M1-M10). Kliknij aby zaznaczyć/odznaczyć użycie.">
                    <div class="w-8 h-8 rounded-lg bg-fuchsia-950 border border-fuchsia-500 flex items-center justify-center text-fuchsia-400 text-sm shrink-0">
                        <i class="fa-solid fa-scroll"></i>
                    </div>
                    <div class="text-left">
                        <span class="text-[9px] text-fuchsia-400 font-extrabold uppercase tracking-widest block leading-none">ZWOJE EGZORCYZMU</span>
                        <span class="text-lg font-black text-fuchsia-300 drop-shadow">{{ $exorcismScrollsCount }}</span>
                    </div>
                    <i class="fa-solid {{ $useExorcismScroll ? 'fa-toggle-on text-fuchsia-300' : 'fa-toggle-off text-stone-600' }} text-lg ml-1"></i>
                </button>

                {{-- Info Button --}}
                <button wire:click="toggleInfoModal"
                    class="px-4 py-2.5 min-h-[44px] rounded-xl bg-black/80 border-2 border-sky-600/50 text-sky-300 hover:text-sky-100 hover:border-sky-400 font-extrabold text-xs uppercase tracking-widest shadow-inner transition-all duration-200 flex items-center gap-2 cursor-pointer"
                    title="Opis mechanik umiejętności i mistrzostwa">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>Opis mechanik</span>
                </button>

                {{-- Back Button --}}
                <button wire:click="backToHub" @click="$dispatch('location-leave', { text: 'Podróż do Miasta...', icon: 'fa-solid fa-archway', url: '{{ route('city.hub', $character->id) }}' }); $dispatch('play-audio', { type: 'tab' })"
                    class="px-4 py-2.5 rounded-xl bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-amber-200 font-extrabold text-xs uppercase tracking-widest border-2 border-slate-700 hover:border-emerald-500 hover:text-emerald-100 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),0_4px_10px_rgba(0,0,0,0.8)] transition-all duration-200 flex items-center gap-2 group cursor-pointer">
                    <i class="fa-solid fa-archway text-amber-400 group-hover:scale-110 transition-transform"></i>
                    <span>Powrót do Miasta</span>
                </button>
            </div>
        </div>

        {{-- Tabs: Umiejętności / Mistrzostwo --}}
        <div class="flex items-center justify-center gap-2 mb-6">
            <button wire:click="setTab('skills')"
                class="px-5 py-2.5 rounded-xl font-extrabold text-xs sm:text-sm uppercase tracking-widest border-2 transition-all duration-200 flex items-center gap-2 cursor-pointer
                {{ $activeTab === 'skills' ? 'bg-emerald-800 border-emerald-400 text-white shadow-[0_0_15px_rgba(16,185,129,0.4)]' : 'bg-stone-900 border-stone-700 text-stone-400 hover:border-emerald-600 hover:text-emerald-200' }}">
                <i class="fa-solid fa-book-skull"></i>
                <span>Umiejętności</span>
            </button>
            @if($championUnlocked)
                <button wire:click="setTab('mastery')"
                    class="px-5 py-2.5 rounded-xl font-extrabold text-xs sm:text-sm uppercase tracking-widest border-2 transition-all duration-200 flex items-center gap-2 cursor-pointer
                    {{ $activeTab === 'mastery' ? 'bg-sky-800 border-sky-400 text-white shadow-[0_0_15px_rgba(56,189,248,0.4)]' : 'bg-stone-900 border-stone-700 text-stone-400 hover:border-sky-600 hover:text-sky-200' }}">
                    <i class="fa-solid fa-crown"></i>
                    <span>Mistrzostwo</span>
                </button>
            @else
                <span class="px-5 py-2.5 rounded-xl font-extrabold text-xs sm:text-sm uppercase tracking-widest border-2 border-stone-800 bg-stone-950 text-stone-600 flex items-center gap-2 cursor-not-allowed" title="Odblokowuje się po osiągnięciu 99 poziomu">
                    <i class="fa-solid fa-lock"></i>
                    <span>Mistrzostwo (Poz. 99)</span>
                </span>
            @endif
        </div>

        @if($activeTab === 'mastery')
            @include('livewire.city.partials.warlock-mastery')
        @else

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

            {{-- Filters: Weapon Type & Skill Type --}}
            @php
                $weaponFilterOptions = [
                    'all' => ['label' => 'Wszystkie', 'icon' => 'fa-solid fa-shield-halved'],
                    'sword' => ['label' => 'Miecz', 'icon' => 'fa-solid fa-khanda'],
                    'axe' => ['label' => 'Topór', 'icon' => 'fa-solid fa-axe'],
                    'bow' => ['label' => 'Łuk', 'icon' => 'fa-solid fa-bow-arrow'],
                    'wand' => ['label' => 'Różdżka', 'icon' => 'fa-solid fa-wand-magic-sparkles'],
                    'bell' => ['label' => 'Dzwon', 'icon' => 'fa-solid fa-bell'],
                    'dagger' => ['label' => 'Sztylet', 'icon' => 'fa-solid fa-scissors'],
                ];
                $typeFilterOptions = [
                    'all' => ['label' => 'Wszystkie', 'icon' => 'fa-solid fa-layer-group'],
                    'active' => ['label' => 'Aktywne', 'icon' => 'fa-solid fa-bolt'],
                    'passive' => ['label' => 'Pasywne', 'icon' => 'fa-solid fa-shield'],
                ];
                $categoryFilterOptions = [
                    'all' => ['label' => 'Wszystkie', 'icon' => 'fa-solid fa-icons'],
                    'poison' => ['label' => 'Trucizna', 'icon' => 'fa-solid fa-skull-crossbones'],
                    'fire' => ['label' => 'Podpalenie', 'icon' => 'fa-solid fa-fire-flame-curved'],
                    'aoe' => ['label' => 'AOE', 'icon' => 'fa-solid fa-burst'],
                    'heal' => ['label' => 'Leczenie', 'icon' => 'fa-solid fa-heart'],
                    'defense' => ['label' => 'Obrona', 'icon' => 'fa-solid fa-shield-halved'],
                    'dmg' => ['label' => 'DMG', 'icon' => 'fa-solid fa-hand-fist'],
                ];
            @endphp
            <div class="mb-6 flex flex-col sm:flex-row items-center justify-center gap-4 sm:gap-8 bg-stone-950/70 border border-emerald-900/50 rounded-2xl p-4 shadow-inner">
                <div class="flex flex-col items-center gap-2">
                    <span class="text-[10px] font-black uppercase tracking-widest text-emerald-400/80">Typ Broni</span>
                    <div class="flex flex-wrap justify-center gap-1.5">
                        @foreach($weaponFilterOptions as $value => $opt)
                            <button wire:click="filterByWeapon('{{ $value }}')"
                                class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold border transition-all duration-150 flex items-center gap-1.5 cursor-pointer
                                {{ $weaponFilter === $value
                                    ? 'bg-emerald-800 border-emerald-400 text-white shadow-[0_0_10px_rgba(16,185,129,0.4)]'
                                    : 'bg-stone-900 border-stone-700 text-stone-400 hover:border-emerald-600 hover:text-emerald-200' }}">
                                <i class="{{ $opt['icon'] }}"></i>
                                <span>{{ $opt['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="hidden sm:block w-px self-stretch bg-emerald-900/50"></div>

                <div class="flex flex-col items-center gap-2">
                    <span class="text-[10px] font-black uppercase tracking-widest text-emerald-400/80">Typ Umiejętności</span>
                    <div class="flex flex-wrap justify-center gap-1.5">
                        @foreach($typeFilterOptions as $value => $opt)
                            <button wire:click="filterByType('{{ $value }}')"
                                class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold border transition-all duration-150 flex items-center gap-1.5 cursor-pointer
                                {{ $typeFilter === $value
                                    ? 'bg-sky-800 border-sky-400 text-white shadow-[0_0_10px_rgba(56,189,248,0.4)]'
                                    : 'bg-stone-900 border-stone-700 text-stone-400 hover:border-sky-600 hover:text-sky-200' }}">
                                <i class="{{ $opt['icon'] }}"></i>
                                <span>{{ $opt['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="hidden sm:block w-px self-stretch bg-emerald-900/50"></div>

                <div class="flex flex-col items-center gap-2">
                    <span class="text-[10px] font-black uppercase tracking-widest text-emerald-400/80">Kategoria</span>
                    <div class="flex flex-wrap justify-center gap-1.5">
                        @foreach($categoryFilterOptions as $value => $opt)
                            <button wire:click="filterByCategory('{{ $value }}')"
                                class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold border transition-all duration-150 flex items-center gap-1.5 cursor-pointer
                                {{ $categoryFilter === $value
                                    ? 'bg-amber-800 border-amber-400 text-white shadow-[0_0_10px_rgba(217,119,6,0.4)]'
                                    : 'bg-stone-900 border-stone-700 text-stone-400 hover:border-amber-600 hover:text-amber-200' }}">
                                <i class="{{ $opt['icon'] }}"></i>
                                <span>{{ $opt['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            @php
                $simMobHp = max(100, $character->level * 150);
            @endphp

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @forelse($allSkills as $skill)
                    @php
                        $mySkill = $mySkills[$skill->id] ?? null;
                        $isUnlocked = $mySkill !== null;
                        $level = $isUnlocked ? $mySkill->level : 1;
                        $tier = $isUnlocked ? $mySkill->getTier() : 'normal';
                        $displayLevel = $isUnlocked ? $mySkill->getDisplayLevel() : 'Lv. 1';
                        $effectiveLevel = $isUnlocked ? $mySkill->getEffectiveLevel() : 1;
                        $cooldown = $isUnlocked ? $mySkill->getCooldown() : $skill->base_cooldown;
                        
                        $isMaxLevel = $isUnlocked && $mySkill->level >= 27;
                        $canUnlock = !$isUnlocked && $character->level >= $skill->required_level && $character->skill_points >= $skill->unlock_cost;

                        $reqWeapon = $skill->required_weapon_type ?? 'all';
                        $reqBookSubType = \App\Application\Skills\UpgradeSkill::getRequiredBookSubType($reqWeapon);
                        $reqBookName = \App\Application\Skills\UpgradeSkill::getRequiredBookName($reqWeapon);
                        $ownedSpecificBooks = $ownedSkillBooksBySubType[$reqBookSubType] ?? 0;

                        // Calculation of upgrade eligibility based on tier stage:
                        $canUpgrade = false;
                        $costText = '';

                        if ($isUnlocked && !$isMaxLevel) {
                            if ($level < 6) {
                                $canUpgrade = $character->skill_points >= 1;
                                $costText = '1 PKT SKILLA (85% szans)';
                            } elseif ($level >= 6 && $level < 16) {
                                $bookCost = $level - 5;
                                $willUseExorcism = $useExorcismScroll && $exorcismScrollsCount >= 1;
                                $canUpgrade = $ownedSpecificBooks >= $bookCost && $character->gold >= 500 && (!$useExorcismScroll || $exorcismScrollsCount >= 1);
                                $costText = "{$bookCost}x {$reqBookName} + 500 Gold (" . ($willUseExorcism ? '100% szans - Zwój Egzorcyzmu' : '50% szans') . ")";
                            } elseif ($level >= 16 && $level < 27) {
                                $stoneCost = (int) round(1 + ($level - 16) * 4 / 9);
                                $stoneGoldCost = $level === 26 ? 10000 : 2500;
                                $canUpgrade = $soulStonesCount >= $stoneCost && $character->gold >= $stoneGoldCost;
                                $costText = "{$stoneCost}x Kamień Duchowy + " . ($level === 26 ? '10k' : '2.5k') . ' Gold (20% szans)';
                            }
                        }

                        // Calculation of current values & scaling
                        $currentValue = $isUnlocked ? $mySkill->getEffectiveValue() : $skill->base_value;
                        $nextValue = $isUnlocked ? ($skill->base_value + ($skill->scaling_value * $effectiveLevel)) : ($skill->base_value + $skill->scaling_value);

                        // Base damage range and stat influence computed specifically for this skill
                        $baseDamageRange = $skill->getBaseDamageRange($character);
                        $baseMin = $baseDamageRange['min'];
                        $baseMax = $baseDamageRange['max'];
                        $baseAvg = $baseDamageRange['avg'];
                        $statInfluenceText = $skill->getStatInfluenceText($character);

                        // Format Stat Power & Labels
                        $effectTitle = 'Siła Efektu';
                        $effectValueText = '';
                        $effectNextText = '';
                        $scalingText = '';

                        if (in_array($skill->effect_type, ['direct_dmg', 'direct'])) {
                            $effectTitle = 'Obrażenia Natychmiastowe';
                            $effectValueText = round($currentValue * 100) . '% Obrażeń Broni';
                            $effectNextText = round($nextValue * 100) . '% Obrażeń Broni (+' . round($skill->scaling_value * 100) . '%)';
                            $scalingText = round($skill->scaling_value * 100) . '% Obrażeń Broni / Poziom';
                        } elseif (in_array($skill->effect_type, ['buff_phys_dmg', 'buff_damage'])) {
                            $effectTitle = 'Wzmocnienie Obrażeń';
                            $effectValueText = '+' . round($currentValue * 100) . '% Obrażeń';
                            $effectNextText = '+' . round($nextValue * 100) . '% Obrażeń (+' . round($skill->scaling_value * 100) . '%)';
                            $scalingText = '+' . round($skill->scaling_value * 100) . '% Ataku / Poziom';
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
                        } elseif ($skill->effect_type === 'heal') {
                            $effectTitle = 'Leczenie';
                            $effectValueText = '+' . round($currentValue * 100) . '% Max HP';
                            $effectNextText = '+' . round($nextValue * 100) . '% Max HP (+' . round($skill->scaling_value * 100) . '%)';
                            $scalingText = '+' . round($skill->scaling_value * 100) . '% Max HP / Poziom';
                            $statInfluenceText = 'Poziom Skilla oraz Maksymalne HP Postaci.';
                        } elseif (in_array($skill->effect_type, ['freeze', 'stun'])) {
                            $effectTitle = $skill->effect_type === 'freeze' ? 'Zamrożenie (Obrażenia + CC)' : 'Ogłuszenie (Obrażenia + CC)';
                            $effectValueText = round($currentValue * 100) . '% Obrażeń Broni, ' . $skill->base_duration . ' Tur Unieruchomienia';
                            $effectNextText = round($nextValue * 100) . '% Obrażeń Broni (+' . round($skill->scaling_value * 100) . '%)';
                            $scalingText = round($skill->scaling_value * 100) . '% Obrażeń Broni / Poziom';
                        } elseif ($skill->effect_type === 'aoe_dmg') {
                            $effectTitle = 'Obrażenia Obszarowe (AOE)';
                            $effectValueText = round($currentValue * 100) . '% Obrażeń Broni (wszyscy wrogowie)';
                            $effectNextText = round($nextValue * 100) . '% Obrażeń Broni (+' . round($skill->scaling_value * 100) . '%)';
                            $scalingText = round($skill->scaling_value * 100) . '% Obrażeń Broni / Poziom';
                        } elseif ($skill->effect_type === 'buff_defense') {
                            $effectTitle = 'Redukcja Obrażeń Przychodzących';
                            $effectValueText = '-' . round($currentValue * 100) . '% Obrażeń';
                            $effectNextText = '-' . round($nextValue * 100) . '% Obrażeń (+' . round($skill->scaling_value * 100) . '%)';
                            $scalingText = '+' . round($skill->scaling_value * 100) . '% Redukcji / Poziom';
                            $statInfluenceText = 'Wyłącznie Poziom Skilla (cap 75% redukcji).';
                        } elseif ($skill->effect_type === 'passive_aura_dmg') {
                            $effectTitle = '[Pasywna] Aura Obrażeń Fizycznych';
                            $effectValueText = '+' . round($currentValue * 100) . '% Obrażeń Fizycznych';
                            $effectNextText = '+' . round($nextValue * 100) . '% Obrażeń Fizycznych (+' . round($skill->scaling_value * 100) . '%)';
                            $scalingText = '+' . round($skill->scaling_value * 100) . '% Ataku / Poziom';
                            $statInfluenceText = 'Aktywna stale, gdy wymagana broń jest założona.';
                        } elseif ($skill->effect_type === 'passive_extra_attack') {
                            $effectTitle = '[Pasywna] Szansa na Dodatkowy Atak';
                            $effectValueText = round(min(0.75, $currentValue) * 100) . '% Szansy';
                            $effectNextText = round(min(0.75, $nextValue) * 100) . '% Szansy (+' . round($skill->scaling_value * 100) . '%)';
                            $scalingText = '+' . round($skill->scaling_value * 100) . '% Szansy / Poziom (cap 75%)';
                            $statInfluenceText = 'Aktywna stale, gdy wymagana broń jest założona.';
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
                        elseif ($skill->required_weapon_type === 'dagger') { $weaponName = 'Sztylet'; $weaponIcon = 'fa-solid fa-scissors'; }

                        // Damage Simulations based on skill base damage calculation
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
                        {{ $isUnlocked ? ($tier === 'perfect' ? 'border-amber-400 shadow-[0_0_25px_rgba(251,191,36,0.35)]' : ($tier === 'grand_master' ? 'border-orange-500/90 shadow-[0_0_20px_rgba(249,115,22,0.25)]' : ($tier === 'master' ? 'border-sky-500/80 shadow-[0_0_15px_rgba(56,189,248,0.2)]' : 'border-emerald-600/80 hover:border-emerald-400 shadow-[0_0_15px_rgba(16,185,129,0.15)]'))) : 'border-stone-800/80 opacity-90 hover:border-stone-700' }}">
                        
                        {{-- Top Unlock Badge --}}
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                            <div class="flex items-center gap-2">
                                <span class="bg-stone-950/80 px-2.5 py-1 rounded-md text-[11px] font-bold text-amber-300 border border-amber-900/60 flex items-center gap-1.5">
                                    <i class="{{ $weaponIcon }}"></i>
                                    <span>{{ $weaponName }}</span>
                                </span>
                                <span class="bg-stone-950/80 px-2.5 py-1 rounded-md text-[11px] font-bold text-sky-300 border border-sky-900/60 flex items-center gap-1.5 font-sans" title="Czas odnowienia (CD zredukowany o awans rang)">
                                    <i class="fa-regular fa-clock"></i>
                                    <span>CD: {{ $cooldown }} Tur</span>
                                    @if($isUnlocked && $tier !== 'normal')
                                        <span class="text-[9px] text-amber-400 font-extrabold">(-{{ $tier === 'perfect' ? 3 : ($tier === 'grand_master' ? 2 : 1) }})</span>
                                    @endif
                                </span>
                                @if($skill->getManaCost($isUnlocked ? $mySkill->getEffectiveLevel() : 1) > 0)
                                    <span class="bg-stone-950/80 px-2.5 py-1 rounded-md text-[11px] font-bold text-cyan-300 border border-cyan-900/60 flex items-center gap-1.5 font-sans" title="Koszt many">
                                        <i class="fa-solid fa-bolt"></i>
                                        <span>Mana: {{ $skill->getManaCost($isUnlocked ? $mySkill->getEffectiveLevel() : 1) }} MP</span>
                                    </span>
                                @endif
                                @if($skill->base_duration > 1)
                                    <span class="bg-stone-950/80 px-2.5 py-1 rounded-md text-[11px] font-bold text-purple-300 border border-purple-900/60 flex items-center gap-1.5 font-sans">
                                        <i class="fa-solid fa-hourglass-half"></i>
                                        <span>Czas: {{ $skill->base_duration }} Tur</span>
                                    </span>
                                @endif
                            </div>

                            <div>
                                @if($isUnlocked)
                                    @if($tier === 'perfect')
                                        <span class="px-3 py-1 bg-gradient-to-r from-amber-600 via-yellow-500 to-amber-600 text-stone-950 border border-yellow-300 rounded-lg text-xs font-black uppercase tracking-wider shadow-[0_0_15px_rgba(251,191,36,0.6)] inline-flex items-center gap-1.5 animate-pulse">
                                            <i class="fa-solid fa-crown text-stone-950"></i>
                                            <span>PERFECT MASTER (P)</span>
                                        </span>
                                    @elseif($tier === 'grand_master')
                                        <span class="px-3 py-1 bg-gradient-to-r from-orange-950 via-amber-950 to-stone-950 text-amber-300 border border-amber-500 rounded-lg text-xs font-black uppercase tracking-wider shadow-[0_0_12px_rgba(249,115,22,0.4)] inline-flex items-center gap-1.5">
                                            <i class="fa-solid fa-fire text-amber-400"></i>
                                            <span>{{ $displayLevel }} (Grand Master)</span>
                                        </span>
                                    @elseif($tier === 'master')
                                        <span class="px-3 py-1 bg-gradient-to-r from-sky-950 via-indigo-950 to-stone-950 text-sky-300 border border-sky-500 rounded-lg text-xs font-black uppercase tracking-wider shadow-[0_0_10px_rgba(56,189,248,0.3)] inline-flex items-center gap-1.5">
                                            <i class="fa-solid fa-star text-sky-400"></i>
                                            <span>{{ $displayLevel }} (Master)</span>
                                        </span>
                                    @else
                                        <span class="px-3 py-1 bg-emerald-950 text-emerald-300 border border-emerald-600/80 rounded-lg text-xs font-black uppercase tracking-wider shadow-[0_0_10px_rgba(16,185,129,0.3)] inline-flex items-center gap-1">
                                            <i class="fa-solid fa-circle-check"></i>
                                            <span>{{ $displayLevel }} / 6</span>
                                        </span>
                                    @endif
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
                            <div class="w-16 h-16 rounded-xl border-2 {{ $isUnlocked ? ($tier === 'perfect' ? 'border-yellow-400 bg-amber-950/70 shadow-[0_0_15px_rgba(251,191,36,0.4)]' : ($tier === 'grand_master' ? 'border-amber-500 bg-amber-950/50' : ($tier === 'master' ? 'border-sky-500 bg-sky-950/50' : 'border-emerald-500 bg-emerald-950/60'))) : 'border-stone-700 bg-stone-950' }} flex items-center justify-center text-3xl shrink-0 overflow-hidden shadow-[inset_0_2px_4px_rgba(0,0,0,0.8)] relative">
                                @if($skill->icon)
                                    <img src="{{ route('assets.skills.icons', ['filename' => $skill->icon]) }}" class="w-full h-full object-contain p-1" alt="{{ $skill->name }}">
                                @elseif($skill->effect_type === 'poison' || $skill->effect_type === 'dot_poison')
                                    <i class="fa-solid fa-skull-crossbones text-emerald-400"></i>
                                @elseif($skill->effect_type === 'fire' || $skill->effect_type === 'dot_fire')
                                    <i class="fa-solid fa-fire-flame-curved text-amber-400"></i>
                                @elseif(in_array($skill->effect_type, ['buff_phys_dmg', 'buff_damage']))
                                    <i class="fa-solid fa-hand-fist text-yellow-400"></i>
                                @elseif($skill->effect_type === 'heal')
                                    <i class="fa-solid fa-heart text-rose-400"></i>
                                @elseif(in_array($skill->effect_type, ['freeze', 'stun']))
                                    <i class="fa-solid fa-snowflake text-sky-300"></i>
                                @elseif($skill->effect_type === 'aoe_dmg' || $skill->is_aoe)
                                    <i class="fa-solid fa-burst text-orange-400"></i>
                                @elseif($skill->effect_type === 'buff_defense')
                                    <i class="fa-solid fa-shield-halved text-blue-300"></i>
                                @elseif($skill->effect_type === 'passive_aura_dmg')
                                    <i class="fa-solid fa-fire text-orange-300"></i>
                                @elseif($skill->effect_type === 'passive_extra_attack')
                                    <i class="fa-solid fa-bolt-lightning text-yellow-300"></i>
                                @else
                                    <i class="fa-solid fa-khanda text-emerald-400"></i>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <h3 class="text-lg font-extrabold {{ $isUnlocked ? ($tier === 'perfect' ? 'text-amber-300' : 'text-emerald-200') : 'text-stone-300' }} truncate leading-snug">
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

                            @if($isUnlocked && !$isMaxLevel)
                                <div class="text-[11px] text-stone-400 flex items-center justify-between border-t border-stone-800/80 pt-2 mt-2">
                                    <span>Następny Poziom:</span>
                                    <span class="text-emerald-300 font-bold">{{ $effectNextText }}</span>
                                </div>
                            @elseif($isMaxLevel)
                                <div class="text-[11px] text-stone-400 flex items-center justify-between border-t border-stone-800/80 pt-2 mt-2">
                                    <span>Status Mocy:</span>
                                    <span class="text-amber-400 font-bold">Maksymalny Poziom Perfect Master (P) (+65% Mocy)</span>
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
                                    Atak Bazowy: {{ \App\Support\NumberHelper::formatShort($baseMin) }}-{{ \App\Support\NumberHelper::formatShort($baseMax) }}
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
                                    <i class="fa-solid fa-coins text-amber-400"></i>
                                    <span>Wymagania: <strong class="{{ $canUpgrade ? 'text-amber-300' : 'text-red-400' }}">{{ $isMaxLevel ? '-' : $costText }}</strong></span>
                                </div>

                                @if($isMaxLevel)
                                    <button disabled class="px-5 py-2 rounded-xl font-extrabold text-xs uppercase tracking-wider bg-gradient-to-r from-amber-600 to-yellow-500 text-stone-950 border border-yellow-300 cursor-not-allowed shadow-[0_0_15px_rgba(251,191,36,0.5)] flex items-center gap-2">
                                        <i class="fa-solid fa-crown text-stone-950"></i>
                                        <span>PERFECT (P)</span>
                                    </button>
                                @else
                                    <button wire:click="upgradeSkill('{{ $mySkill->id }}')" 
                                            wire:loading.attr="disabled"
                                            class="px-5 py-2 rounded-xl font-extrabold text-xs uppercase tracking-wider transition-all duration-200 shadow-md border cursor-pointer flex items-center gap-2
                                            {{ $canUpgrade ? ($level >= 26 ? 'bg-gradient-to-b from-amber-600 via-yellow-600 to-amber-900 hover:from-amber-500 hover:to-yellow-800 text-amber-100 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5)] animate-pulse' : ($level >= 16 ? 'bg-gradient-to-b from-amber-700 via-amber-800 to-stone-950 hover:from-amber-600 hover:to-amber-900 text-amber-100 border-amber-500 shadow-[0_0_12px_rgba(245,158,11,0.4)]' : ($level >= 6 ? 'bg-gradient-to-b from-sky-700 via-sky-800 to-indigo-950 hover:from-sky-600 hover:to-sky-900 text-sky-100 border-sky-500 shadow-[0_0_12px_rgba(56,189,248,0.4)]' : 'bg-gradient-to-b from-emerald-700 via-emerald-800 to-emerald-950 hover:from-emerald-600 hover:to-emerald-900 text-emerald-100 border-emerald-500 shadow-[0_0_12px_rgba(16,185,129,0.4)]'))) : 'bg-stone-900 text-stone-500 border-stone-800 cursor-not-allowed opacity-60' }}"
                                            @if(!$canUpgrade) disabled @endif>
                                        <i class="fa-solid fa-circle-arrow-up"></i>
                                        <span>{{ $level === 26 ? 'Awansuj na PERFECT (P)' : ($level >= 16 ? 'Ulepsz (Kamień Duchowy)' : ($level >= 6 ? 'Ulepsz (' . $reqBookName . ')' . (($willUseExorcism ?? false) ? ' + Zwój Egzorcyzmu' : '') : 'Ulepsz do ' . ($mySkill->level + 1))) }}</span>
                                    </button>
                                @endif
                            @endif
                        </div>

                    </div>
                @empty
                    <div class="col-span-full text-center py-12 bg-black/40 rounded-2xl border border-emerald-900/40 backdrop-blur-sm">
                        <i class="fa-solid fa-book-skull text-4xl text-emerald-400/40 mb-3 block"></i>
                        <p class="text-emerald-300/70 font-medium font-sans">Brak umiejętności pasujących do wybranych filtrów.</p>
                    </div>
                @endforelse
            </div>
        </div>
        @endif
    </div>

    {{-- Opis Mechanik --}}
    @if($showInfoModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/80 backdrop-blur-md p-4 animate-fade-in">
            <div x-data="{ infoTab: 'skills' }" class="bg-gradient-to-b from-stone-900 via-slate-900 to-stone-950 border-2 border-sky-500/50 rounded-2xl max-w-4xl w-full p-6 shadow-[0_0_50px_rgba(0,0,0,0.9)] relative max-h-[90vh] flex flex-col">
                <button wire:click="toggleInfoModal" class="absolute top-4 right-4 text-stone-400 hover:text-white text-xl">
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <h3 class="text-lg font-bold text-sky-200 mb-4 flex items-center gap-2" style="font-family: 'Cinzel', serif;">
                    <i class="fa-solid fa-circle-info"></i> Opis Mechanik Umiejętności i Mistrzostwa
                </h3>

                @php
                    $infoTabBtn = 'px-3 py-1.5 rounded-lg border text-[11px] sm:text-xs font-bold uppercase tracking-wide transition-colors whitespace-nowrap';
                    $infoTabActive = 'bg-sky-600/20 text-sky-200 border-sky-500/60';
                    $infoTabInactive = 'text-stone-400 border-transparent hover:text-stone-200 hover:bg-stone-800/60';
                @endphp

                {{-- Zakładki --}}
                <div class="flex flex-wrap gap-2 mb-4 border-b border-stone-800 pb-3">
                    <button @click="infoTab = 'skills'" :class="infoTab === 'skills' ? '{{ $infoTabActive }}' : '{{ $infoTabInactive }}'" class="{{ $infoTabBtn }}">
                        <i class="fa-solid fa-book-skull mr-1"></i> Rozwój Umiejętności
                    </button>
                    <button @click="infoTab = 'combat'" :class="infoTab === 'combat' ? '{{ $infoTabActive }}' : '{{ $infoTabInactive }}'" class="{{ $infoTabBtn }}">
                        <i class="fa-solid fa-hourglass-half mr-1"></i> Cooldown i Mana
                    </button>
                    <button @click="infoTab = 'rules'" :class="infoTab === 'rules' ? '{{ $infoTabActive }}' : '{{ $infoTabInactive }}'" class="{{ $infoTabBtn }}">
                        <i class="fa-solid fa-swords mr-1"></i> Zasady Walki
                    </button>
                    <button @click="infoTab = 'mastery'" :class="infoTab === 'mastery' ? '{{ $infoTabActive }}' : '{{ $infoTabInactive }}'" class="{{ $infoTabBtn }}">
                        <i class="fa-solid fa-crown mr-1"></i> Mistrzostwo Championa
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto pr-2 text-xs text-stone-300">

                    {{-- ROZWÓJ UMIEJĘTNOŚCI --}}
                    <div x-show="infoTab === 'skills'" class="space-y-6">
                        <div>
                            <h4 class="text-amber-300 font-bold mb-2 uppercase tracking-wider">3 Etapy Rozwoju</h4>
                            <p class="mb-2">Każda umiejętność rozwija się w 3 etapach, aż do poziomu Perfect (P) - maksimum. Każda próba ulepszenia ma szansę porażki - surowce (PKT/Księgi/Kamienie + złoto) zużywają się NIEZALEŻNIE od wyniku, tak jak przy ulepszaniu przedmiotów u Kowala.</p>
                            <div class="overflow-x-auto">
                                <table class="w-full text-[11px] border-collapse">
                                    <thead>
                                        <tr class="border-b border-stone-700 text-stone-400">
                                            <th class="text-left py-1 pr-2">Etap</th>
                                            <th class="py-1 px-1">Koszt / poziom</th>
                                            <th class="py-1 px-1">Szansa sukcesu</th>
                                            <th class="py-1 px-1">Bonus mocy</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="border-b border-stone-800">
                                            <td class="py-1 pr-2 font-bold text-emerald-300">Podstawowy (Lv. 1-6)</td>
                                            <td class="py-1 px-1 text-center">1 Punkt Umiejętności</td>
                                            <td class="py-1 px-1 text-center"><strong class="text-emerald-400">85%</strong></td>
                                            <td class="py-1 px-1 text-center">×1.00</td>
                                        </tr>
                                        <tr class="border-b border-stone-800">
                                            <td class="py-1 pr-2 font-bold text-sky-300">Mistrz (M1-M10)</td>
                                            <td class="py-1 px-1 text-center">1..10 Ksiąg Umiejętności + 500 złota</td>
                                            <td class="py-1 px-1 text-center"><strong class="text-amber-400">50%</strong></td>
                                            <td class="py-1 px-1 text-center">×1.15</td>
                                        </tr>
                                        <tr class="border-b border-stone-800">
                                            <td class="py-1 pr-2 font-bold text-amber-300">Arcymistrz (G1-G10)</td>
                                            <td class="py-1 px-1 text-center">1..5 Kamieni Duchowych + 2 500 złota</td>
                                            <td class="py-1 px-1 text-center"><strong class="text-red-400">20%</strong></td>
                                            <td class="py-1 px-1 text-center">×1.35</td>
                                        </tr>
                                        <tr class="border-b border-stone-800">
                                            <td class="py-1 pr-2 font-bold text-yellow-300">Perfect (P) - maks.</td>
                                            <td class="py-1 px-1 text-center">5 Kamieni Duchowych + 10 000 złota</td>
                                            <td class="py-1 px-1 text-center"><strong class="text-red-400">20%</strong></td>
                                            <td class="py-1 px-1 text-center">×1.65</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p class="mt-2 text-stone-400">Koszt Ksiąg/Kamieni rośnie stopniowo z każdym kolejnym poziomem w danym etapie (np. M1 wymaga 1 Księgi, M2 - 2 Ksiąg, ..., M10 - 10 Ksiąg). Wymagana Księga zależy od typu broni umiejętności (np. "Księga Walki Mieczem" dla skilli miecza).</p>
                        </div>

                        <div>
                            <h4 class="text-amber-300 font-bold mb-2 uppercase tracking-wider">Skąd wziąć surowce</h4>
                            <ul class="space-y-1">
                                <li><strong class="text-sky-300">Księgi Umiejętności:</strong> wypadają ze wszystkich bossów map (T1+) oraz wszystkich lochów (D1+).</li>
                                <li><strong class="text-amber-300">Kamienie Duchowe:</strong> wypadają z bossów map od Tieru 5 wzwyż oraz z lochów od Dungeon 3 wzwyż.</li>
                            </ul>
                        </div>

                        <div>
                            <h4 class="text-amber-300 font-bold mb-2 uppercase tracking-wider">Reset Umiejętności</h4>
                            <p>Zainwestowane Punkty Umiejętności można odzyskać przedmiotem "Zwój Resetu Umiejętności" (pojedynczy skill) lub "Zwój Pełnego Resetu" (wszystkie skille naraz).</p>
                        </div>
                    </div>

                    {{-- COOLDOWN I MANA --}}
                    <div x-show="infoTab === 'combat'" class="space-y-6">
                        <div>
                            <h4 class="text-purple-300 font-bold mb-2 uppercase tracking-wider">Czas Odnowienia (Cooldown)</h4>
                            <p class="mb-2">Umiejętności dzielą się na 3 kategorie szybkości wg bazowego cooldownu (na poziomie Podstawowym) - każda skaluje się inaczej z mistrzostwem:</p>
                            <div class="overflow-x-auto">
                                <table class="w-full text-[11px] border-collapse">
                                    <thead>
                                        <tr class="border-b border-stone-700 text-stone-400">
                                            <th class="text-left py-1 pr-2">Kategoria</th>
                                            <th class="py-1 px-1">Podstawowy</th>
                                            <th class="py-1 px-1">Mistrz</th>
                                            <th class="py-1 px-1">Arcymistrz / Perfect</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="border-b border-stone-800">
                                            <td class="py-1 pr-2 font-bold text-amber-200">Szybkie (CD 1-2)</td>
                                            <td class="py-1 px-1 text-center">bez zmian</td>
                                            <td class="py-1 px-1 text-center">+1 (max 3)</td>
                                            <td class="py-1 px-1 text-center">+2 (max 4)</td>
                                        </tr>
                                        <tr class="border-b border-stone-800">
                                            <td class="py-1 pr-2 font-bold text-amber-200">Średnie (CD 3-5)</td>
                                            <td class="py-1 px-1 text-center">bez zmian</td>
                                            <td class="py-1 px-1 text-center">-1 (min 3)</td>
                                            <td class="py-1 px-1 text-center">stałe 3</td>
                                        </tr>
                                        <tr class="border-b border-stone-800">
                                            <td class="py-1 pr-2 font-bold text-amber-200">Długie (CD 6+)</td>
                                            <td class="py-1 px-1 text-center">bez zmian</td>
                                            <td class="py-1 px-1 text-center">-2 (min 6)</td>
                                            <td class="py-1 px-1 text-center">stałe 5</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p class="mt-2 text-stone-400">Szybkie skille ROSNĄ czasem odnowienia z mistrzostwem (rekompensata za rosnącą moc), średnie i długie - maleją, aż do wspólnego okna 3-5 tur na Arcymistrzu/Perfect.</p>
                        </div>

                        <div>
                            <h4 class="text-purple-300 font-bold mb-2 uppercase tracking-wider">Koszt Many</h4>
                            <p class="font-mono text-[11px] text-amber-200 bg-black/40 rounded-lg px-3 py-2">koszt many = koszt_bazowy + (poziom - 1) × przyrost_na_poziom</p>
                            <p class="mt-2 text-stone-400">Skille pasywne pobierają manę co turę zamiast przy aktywacji - jeśli zabraknie many, efekt pasywny w danej turze zostaje pominięty.</p>
                        </div>
                    </div>

                    {{-- ZASADY WALKI --}}
                    <div x-show="infoTab === 'rules'" class="space-y-6">
                        <div>
                            <h4 class="text-rose-300 font-bold mb-2 uppercase tracking-wider">Deck Umiejętności</h4>
                            <p>Postać może mieć maksymalnie <strong class="text-amber-200">3 aktywne umiejętności</strong> wyposażone jednocześnie. Wyposażone skille zastępują atak podstawowy, gdy są gotowe (cooldown = 0) i starcza many.</p>
                        </div>

                        <div>
                            <h4 class="text-rose-300 font-bold mb-2 uppercase tracking-wider">Wymagana Broń</h4>
                            <p>Każda umiejętność wymaga konkretnego typu broni w ręce głównej (miecz/topór/łuk/różdżka/dzwon/sztylet) lub pasuje do każdej ("all"). Bez wymaganej broni skill jest nieaktywny i nie zostanie użyty w walce.</p>
                        </div>

                        <div>
                            <h4 class="text-rose-300 font-bold mb-2 uppercase tracking-wider">Pułap Obrażeń</h4>
                            <p>Umiejętności zadające bezpośrednie obrażenia (atak/AoE/zamrożenie/ogłuszenie) mają mnożnik obrażeń ograniczony do <strong class="text-amber-200">×4.0</strong> niezależnie od poziomu - zabezpieczenie przed jednouderzeniowymi zabójstwami przy pełnej maestrii. Leczenie i wzmocnienia procentowe nie podlegają temu limitowi.</p>
                        </div>
                    </div>

                    {{-- MISTRZOSTWO CHAMPIONA --}}
                    <div x-show="infoTab === 'mastery'" class="space-y-6">
                        <div>
                            <h4 class="text-yellow-300 font-bold mb-2 uppercase tracking-wider">Odblokowanie</h4>
                            <p>Zakładka "Mistrzostwo" pojawia się automatycznie po osiągnięciu <strong class="text-amber-200">99 poziomu</strong> postaci (poziom wyświetlany dalej jako <strong class="text-amber-200">99(X)</strong>, gdzie X to poziom Mistrzostwa, maks. {{ \App\Application\Mastery\ChampionService::LEVEL_CAP }}).</p>
                        </div>

                        <div>
                            <h4 class="text-yellow-300 font-bold mb-2 uppercase tracking-wider">Warunki Awansu</h4>
                            <p class="mb-2">Awans na kolejny poziom Mistrzostwa wymaga JEDNOCZEŚNIE:</p>
                            <ul class="space-y-1">
                                <li>Pełnego paska EXP (ten sam pasek co zwykłe levelowanie, tylko z dużo wyższym progiem).</li>
                                <li>Dostarczenia Czarnoksiężnikowi losowo wymaganego zestawu materiałów (sumujących się do 1000 sztuk, z map różnych tierów).</li>
                            </ul>
                            <p class="mt-2 text-stone-400">Po awansie pasek EXP resetuje się do zera i losowany jest nowy zestaw materiałów do kolejnego poziomu.</p>
                        </div>

                        <div>
                            <h4 class="text-yellow-300 font-bold mb-2 uppercase tracking-wider">Drzewko 10 Umiejętności Pasywnych</h4>
                            <p class="mb-2">Każdy poziom Mistrzostwa daje 1 Punkt Mistrzostwa (PKT) do zainwestowania w dowolną z 10 pasywnych umiejętności (max {{ \App\Application\Mastery\ChampionService::LEVEL_CAP }} PKT łącznie na {{ \App\Infrastructure\Persistence\ChampionSkill::count() }} umiejętności - trzeba wybrać, które maksować):</p>
                            <ul class="space-y-1">
                                @foreach($championSkillsCatalog as $cs)
                                    <li><strong class="text-amber-200">{{ $cs->name }}:</strong> {{ $cs->description }}</li>
                                @endforeach
                            </ul>
                        </div>

                        <div>
                            <h4 class="text-yellow-300 font-bold mb-2 uppercase tracking-wider">Reset Drzewka</h4>
                            <p>Koszt <strong class="text-amber-200">{{ number_format(\App\Application\Mastery\ChampionService::RESET_GOLD_COST) }} złota</strong>, dostępny raz na miesiąc. Zwraca wszystkie zainwestowane PKT do ponownego rozdania.</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    @endif
</div>
