@props([
    'item',
    'equippedItem' => null,
    'dropSources' => null,
])

@php
    $template = $item->template ?? $item;
    $upgrade_level = $item->upgrade_level ?? 0;

    // Raw base_stats as defined on the template: for weapon/armor/accessory, each
    // numeric value may be a [min, max] range rather than a fixed scalar.
    $template_base_stats = is_array($template->base_stats ?? null) ? $template->base_stats : (json_decode($template->base_stats ?? '[]', true) ?? []);

    // Real ItemInstance -> show the concrete rolled value per stat (falls back to
    // the range midpoint if this instance never rolled it). Template-only preview
    // (dummy/recipe/shop card, no instance yet) -> show the raw range as-is.
    $isInstance = is_object($item) && method_exists($item, 'getResolvedBaseStats');
    $base_stats = $isInstance ? $item->getResolvedBaseStats() : $template_base_stats;

    $roll_stats = [];
    if (isset($item->roll_stats)) {
        $roll_stats = is_array($item->roll_stats) ? $item->roll_stats : (json_decode($item->roll_stats, true) ?? []);
    }
    $enchants = $roll_stats['enchants'] ?? [];
    
    // Calculate upgrade bonus for this item
    $upgrade_bonus = [];
    if (is_object($item) && method_exists($item, 'getUpgradeBonusStats')) {
        $upgrade_bonus = $item->getUpgradeBonusStats($upgrade_level);
    } elseif ($upgrade_level > 0) {
        foreach ($base_stats as $stat => $val) {
            if (is_numeric($val) && $val > 0) {
                $calc = (int) round($val * 0.10 * $upgrade_level);
                $upgrade_bonus[$stat] = max(1, $calc);
            }
        }
    }

    // For comparison:
    $equipped_base_stats = [];
    $equipped_enchants = [];
    $equipped_upgrade_bonus = [];
    
    if ($equippedItem) {
        $eq_template = $equippedItem->template ?? $equippedItem;
        $eq_template_base_stats = is_array($eq_template->base_stats ?? null) ? $eq_template->base_stats : (json_decode($eq_template->base_stats ?? '[]', true) ?? []);
        $eq_isInstance = is_object($equippedItem) && method_exists($equippedItem, 'getResolvedBaseStats');
        $equipped_base_stats = $eq_isInstance ? $equippedItem->getResolvedBaseStats() : $eq_template_base_stats;

        $eq_roll_stats = [];
        if (isset($equippedItem->roll_stats)) {
            $eq_roll_stats = is_array($equippedItem->roll_stats) ? $equippedItem->roll_stats : (json_decode($equippedItem->roll_stats, true) ?? []);
        }
        $equipped_enchants = $eq_roll_stats['enchants'] ?? [];

        $eq_upgrade_level = $equippedItem->upgrade_level ?? 0;
        if (is_object($equippedItem) && method_exists($equippedItem, 'getUpgradeBonusStats')) {
            $equipped_upgrade_bonus = $equippedItem->getUpgradeBonusStats($eq_upgrade_level);
        } elseif ($eq_upgrade_level > 0) {
            foreach ($equipped_base_stats as $stat => $val) {
                if (is_numeric($val) && $val > 0) {
                    $calc = (int) round($val * 0.10 * $eq_upgrade_level);
                    $equipped_upgrade_bonus[$stat] = max(1, $calc);
                }
            }
        }
    }

    $all_base_keys = array_unique(array_merge(array_keys($base_stats), array_keys($equipped_base_stats)));
    $all_enchant_keys = array_unique(array_merge(array_keys($enchants), array_keys($equipped_enchants)));
    
    $hasAnyStats = count($all_base_keys) > 0 || count($all_enchant_keys) > 0;
    
    // Determine weapon subtype label
    $subTypeLabel = null;
    if (($template->type ?? '') === 'weapon') {
        $subTypeKey = $template->sub_type ?? null;
        if (!$subTypeKey) {
            $name = mb_strtolower($template->name ?? '', 'UTF-8');
            if (str_contains($name, 'miecz') || str_contains($name, 'ostrze') || str_contains($name, 'pałasz') || str_contains($name, 'sword')) $subTypeKey = 'sword';
            elseif (str_contains($name, 'topór') || str_contains($name, 'rozłupywacz') || str_contains($name, 'maczuga') || str_contains($name, 'axe')) $subTypeKey = 'axe';
            elseif (str_contains($name, 'łuk') || str_contains($name, 'kusza') || str_contains($name, 'bow')) $subTypeKey = 'bow';
            elseif (str_contains($name, 'różdżka') || str_contains($name, 'kostur') || str_contains($name, 'laska') || str_contains($name, 'wand')) $subTypeKey = 'wand';
            elseif (str_contains($name, 'dzwon') || str_contains($name, 'gong') || str_contains($name, 'bell')) $subTypeKey = 'bell';
            elseif (str_contains($name, 'sztylet') || str_contains($name, 'sztylety') || str_contains($name, 'nóż') || str_contains($name, 'dagger')) $subTypeKey = 'dagger';
        }

        $subTypeNames = [
            'sword' => 'Miecz',
            'axe' => 'Topór',
            'bow' => 'Łuk',
            'wand' => 'Różdżka',
            'bell' => 'Dzwon',
            'dagger' => 'Sztylet',
        ];

        if ($subTypeKey && isset($subTypeNames[$subTypeKey])) {
            $subTypeLabel = $subTypeNames[$subTypeKey];
        }
    }

    // Check if slot matches to allow compare
    $canCompare = false;
    if ($equippedItem && ($equippedItem->id ?? null) !== ($item->id ?? null)) {
        if (($template->slot ?? null) === ($equippedItem->template->slot ?? null)) {
            $canCompare = true;
        }
    }

    $formatStatName = function(string $statKey): string {
        $map = [
            'attack_min' => 'Attack Min',
            'attack_max' => 'Attack Max',
            'magic_attack_min' => 'Magic Attack Min',
            'magic_attack_max' => 'Magic Attack Max',
            'attack_power' => 'Obrażenia Fizyczne',
            'magic_attack' => 'Obrażenia Magiczne',
            'str_bonus' => 'STR Bonus',
            'int_bonus' => 'INT Bonus',
            'vit_bonus' => 'VIT Bonus',
            'agi_bonus' => 'AGI Bonus',
            'crit_chance' => 'Crit Chance',
            'dodge_chance' => 'Dodge Chance',
            'hp_bonus' => 'HP Bonus',
            'defense' => 'Defense',
            'exp_bonus' => 'EXP Bonus',
            'gold_bonus' => 'Gold Bonus',
            'strong_vs_demons' => 'Silny vs Demony',
            'strong_vs_undead' => 'Silny vs Nieumarli',
            'strong_vs_animals' => 'Silny vs Zwierzęta',
            'strong_vs_orcs' => 'Silny vs Orki',
            'resist_demons' => 'Odporność na Demony',
            'resist_undead' => 'Odporność na Nieumarłe',
            'resist_animals' => 'Odporność na Zwierzęta',
            'resist_orcs' => 'Odporność na Orki',
            'poison_chance' => 'Szansa na Otrucie',
            'stun_chance' => 'Szansa na Ogłuszenie',
            'resist_poison' => 'Odporność na Otrucie',
            'resist_stun' => 'Odporność na Ogłuszenie',
            'strong_vs_hero' => 'Silny vs Bohaterów',
            'double_exp_chance' => 'Szansa na Podwójne EXP',
            'double_gold_chance' => 'Szansa na Podwójne Złoto',
            'double_drop_chance' => 'Szansa na Podwójny Łup',
        ];
        if (isset($map[$statKey])) {
            return $map[$statKey];
        }
        return ucwords(str_replace('_', ' ', $statKey));
    };

    $isPercentStat = function(string $statKey): bool {
        if (str_contains($statKey, 'chance') || str_contains($statKey, 'strong_vs') || str_contains($statKey, 'resist') || str_contains($statKey, 'percent') || str_contains($statKey, 'rate')) {
            return true;
        }
        if (in_array($statKey, ['exp_bonus', 'gold_bonus', 'crit_damage', 'life_steal', 'mana_steal', 'attack_power', 'magic_attack'])) {
            return true;
        }
        return false;
    };
@endphp

<div class="p-4 relative bg-gray-900 border-2 border-slate-600 rounded-lg shadow-2xl pointer-events-auto max-w-[calc(100vw-24px)]" x-data="{ compare: {{ $canCompare ? 'true' : 'false' }} }" @click.stop>
    <!-- Górny pasek -->
    <div class="flex justify-between items-center mb-2">
        <div>
            <h4 class="font-bold text-lg text-amber-400">
                {{ $template->name }} 
                @if($upgrade_level > 0)<span class="text-amber-500 text-sm ml-1">+{{ $upgrade_level }}</span>@endif
            </h4>
            <p class="text-xs text-gray-400">Slot: {{ ucfirst($template->slot ?? 'Brak') }} | Typ: {{ ucfirst($template->type ?? 'Nieznany') }}@if($subTypeLabel) ({{ $subTypeLabel }})@endif | Poz: {{ $template->level_requirement ?? 1 }}</p>
            @if(isset($roll_stats['mint']))
                <p class="text-red-400 font-bold text-xs uppercase animate-pulse border-b border-red-500/50 pb-1 w-max">
                    <i class="fa-solid fa-fire text-red-500 mr-1"></i> Nakład: {{ $roll_stats['mint'] }} / {{ $roll_stats['max_mint'] }}
                </p>
            @endif
            @if(($template->type ?? null) === 'material' && $dropSources !== null)
                <div class="mt-1.5 pt-1.5 border-t border-slate-700/70">
                    <p class="text-[10px] uppercase tracking-wider text-gray-400 mb-1">Wypada z</p>
                    @if(count($dropSources) > 0)
                        <div class="flex flex-wrap gap-1">
                            @foreach($dropSources as $drop)
                                <span class="bg-gray-800 border border-gray-600 text-gray-300 text-[10px] px-1.5 py-0.5 rounded">
                                    {{ $drop['monster'] }}@if($drop['map'])<span class="text-amber-400/80"> · {{ $drop['map'] }}</span>@endif
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-[10px] text-gray-500 italic">Brak w znanych tabelach łupów.</p>
                    @endif
                </div>
            @endif
        </div>
        
        @if(is_object($item) && method_exists($item, 'getCombatPower'))
            <span class="text-indigo-300 font-bold ml-2 flex items-center gap-1"><i class="fa-solid fa-bolt text-indigo-400"></i> {{ $item->getCombatPower() }}</span>
        @endif
    </div>
    
    @if($canCompare)
        <div class="mb-3 flex items-center justify-between bg-slate-800/80 px-2.5 py-1.5 rounded border border-slate-600/50">
            <span class="text-xs text-amber-300 font-bold flex items-center gap-1.5">
                <i class="fa-solid fa-scale-balanced text-amber-400"></i> Porównanie z założonym
            </span>
            <button @click="compare = !compare; $dispatch('tooltip-updated')" class="text-[11px] text-gray-400 hover:text-amber-200 underline font-semibold transition">
                <span x-show="compare">Ukryj</span>
                <span x-show="!compare">Pokaż</span>
            </button>
        </div>
    @endif

    <div class="flex flex-col sm:flex-row gap-3 sm:gap-4" :class="compare ? 'w-full sm:min-w-[340px] md:min-w-[440px]' : 'w-full sm:min-w-[200px]'">
        <!-- Ten przedmiot -->
        <div class="flex-1">
            @if($hasAnyStats)
                <div class="text-sm text-gray-200 space-y-1">
                    @foreach($all_base_keys as $stat)
                        @php
                            $val = $base_stats[$stat] ?? 0;
                            $isRange = is_array($val);
                            $suffix = $isPercentStat($stat) ? '%' : '';
                        @endphp
                        @if($isRange)
                            {{-- Template-only preview (no instance rolled yet): show the raw min-max range. --}}
                            <div class="flex justify-between items-center">
                                <span class="capitalize text-gray-200">{{ $formatStatName($stat) }}</span>
                                <span class="font-bold text-gray-200">{{ $val[0] }}-{{ $val[1] }}{{ $suffix }}</span>
                            </div>
                        @else
                            @php
                                $up_val = $upgrade_bonus[$stat] ?? 0;
                                $total_val = $val + $up_val;
                                $isMaxed = $isInstance && method_exists($item, 'isStatMaxed') && $item->isStatMaxed($stat);

                                $eq_val_raw = $canCompare ? ($equipped_base_stats[$stat] ?? 0) : 0;
                                $eq_val = is_array($eq_val_raw) ? 0 : $eq_val_raw;
                                $eq_up_val = $canCompare ? ($equipped_upgrade_bonus[$stat] ?? 0) : 0;
                                $eq_total_val = $eq_val + $eq_up_val;

                                $diff = $total_val - $eq_total_val;
                            @endphp
                            <div class="flex justify-between items-center" x-show="compare || {{ ($val > 0 || $up_val > 0) ? 'true' : 'false' }}">
                                <span class="capitalize text-gray-200">{{ $formatStatName($stat) }}</span>
                                <div class="flex items-center gap-1">
                                    <span class="font-bold {{ $isMaxed ? 'text-yellow-400 font-extrabold' : ($val > 0 ? 'text-gray-200' : 'text-gray-500') }}">+{{ $val }}{{ $suffix }}</span>
                                    @if($up_val > 0)
                                        <span class="text-amber-400 font-semibold text-xs ml-0.5">(+{{ $up_val }}{{ $suffix }})</span>
                                    @endif
                                    <span x-show="compare" class="text-xs font-bold w-12 text-right ml-1 {{ $diff > 0 ? 'text-green-400 font-extrabold' : ($diff < 0 ? 'text-red-400 font-extrabold' : 'text-gray-500') }}">
                                        @if($diff > 0)(+{{ $diff }}{{ $suffix }})@elseif($diff < 0)({{ $diff }}{{ $suffix }})@else(- )@endif
                                    </span>
                                </div>
                            </div>
                        @endif
                    @endforeach
                    @foreach($all_enchant_keys as $stat)
                        @php
                            $val = $enchants[$stat] ?? 0;
                            $eq_val = $canCompare ? ($equipped_enchants[$stat] ?? 0) : 0;
                            $diff = $val - $eq_val;
                            $suffix = $isPercentStat($stat) ? '%' : '';
                        @endphp
                        <div class="flex justify-between items-center text-purple-400" x-show="compare || {{ $val }} > 0">
                            <span class="capitalize flex items-center gap-1"><i class="fa-solid fa-star text-purple-400 text-xs"></i> {{ $formatStatName($stat) }}</span>
                            <div class="flex items-center gap-1">
                                <span class="font-bold {{ $val > 0 ? 'text-purple-300' : 'text-gray-600' }}">+{{ $val }}{{ $suffix }}</span>
                                <span x-show="compare" class="text-xs font-bold w-12 text-right ml-1 {{ $diff > 0 ? 'text-green-400 font-extrabold' : ($diff < 0 ? 'text-red-400 font-extrabold' : 'text-gray-500') }}">
                                    @if($diff > 0)(+{{ $diff }}{{ $suffix }})@elseif($diff < 0)({{ $diff }}{{ $suffix }})@else(- )@endif
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Założony przedmiot (Porównanie) -->
        <div x-show="compare" x-transition.opacity style="display: none;" class="flex-1 border-t sm:border-t-0 sm:border-l border-slate-600 pt-3 sm:pt-0 sm:pl-4">
            @if($canCompare)
                <h5 class="text-[10px] uppercase tracking-wider text-gray-400 mb-1">Obecnie założone ({{ $equippedItem->template->slot }}):</h5>
                <p class="font-bold text-sm text-yellow-400 mb-2">
                    {{ $equippedItem->template->name }} 
                    @if(($equippedItem->upgrade_level ?? 0) > 0)<span class="text-amber-500">+{{ $equippedItem->upgrade_level }}</span>@endif
                </p>
                <div class="text-sm text-gray-300 space-y-1">
                    @foreach($all_base_keys as $stat)
                        @php
                            $eq_val_raw = $equipped_base_stats[$stat] ?? 0;
                            $eq_val = is_array($eq_val_raw) ? 0 : $eq_val_raw;
                            $eq_up_val = $equipped_upgrade_bonus[$stat] ?? 0;
                            $eq_isMaxed = $eq_isInstance && method_exists($equippedItem, 'isStatMaxed') && $equippedItem->isStatMaxed($stat);
                            $suffix = $isPercentStat($stat) ? '%' : '';
                        @endphp
                        @if($eq_val > 0 || $eq_up_val > 0)
                            <div class="flex justify-between">
                                <span class="capitalize text-gray-400">{{ $formatStatName($stat) }}</span>
                                <div class="flex items-center gap-1">
                                    <span class="font-bold {{ $eq_isMaxed ? 'text-yellow-400 font-extrabold' : 'text-gray-200' }}">+{{ $eq_val }}{{ $suffix }}</span>
                                    @if($eq_up_val > 0)
                                        <span class="text-amber-400 font-semibold text-xs ml-0.5">(+{{ $eq_up_val }}{{ $suffix }})</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                    @foreach($all_enchant_keys as $stat)
                        @php $suffix = $isPercentStat($stat) ? '%' : ''; @endphp
                        @if(($equipped_enchants[$stat] ?? 0) > 0)
                            <div class="flex justify-between text-purple-400/80">
                                <span class="capitalize flex items-center gap-1"><i class="fa-solid fa-star text-purple-400 text-xs"></i> {{ $formatStatName($stat) }}</span>
                                <span class="font-bold">+{{ $equipped_enchants[$stat] }}{{ $suffix }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
                @if(is_object($equippedItem) && method_exists($equippedItem, 'getCombatPower'))
                    <div class="mt-2 pt-2 border-t border-slate-700 text-xs text-indigo-300 flex items-center gap-1">
                        <i class="fa-solid fa-bolt text-indigo-400"></i> CP: <span class="font-bold">{{ $equippedItem->getCombatPower() }}</span>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- Przyciski i akcje wstrzykiwane z zewnątrz -->
    @if(isset($actions))
        <div class="mt-4 pt-3 border-t border-slate-700">
            {{ $actions }}
        </div>
    @endif
</div>
