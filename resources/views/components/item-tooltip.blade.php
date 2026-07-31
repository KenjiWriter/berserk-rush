@props([
    'item',
    'equippedItem' => null,
    'dropSources' => null,
])

@php
    $template = $item->template ?? $item;
    $upgrade_level = $item->upgrade_level ?? 0;

    // Non-stat metadata sometimes stored alongside real stats in base_stats (e.g.
    // 'loot_table' on chest consumables, see LootChestSeeder) - not a [min, max]
    // range and not numeric, so it must never reach the +/- arithmetic below.
    $stripNonStatEntries = function (array $stats): array {
        return array_filter($stats, fn ($val) => is_array($val) || is_numeric($val));
    };

    // Raw base_stats as defined on the template: for weapon/armor/accessory, each
    // numeric value may be a [min, max] range rather than a fixed scalar.
    $template_base_stats = is_array($template->base_stats ?? null) ? $template->base_stats : (json_decode($template->base_stats ?? '[]', true) ?? []);
    $template_base_stats = $stripNonStatEntries($template_base_stats);

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
        $eq_template_base_stats = $stripNonStatEntries($eq_template_base_stats);
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

    $all_raw_keys = array_unique(array_merge(array_keys($base_stats), array_keys($equipped_base_stats)));
    $all_base_keys = [];

    $hasPhysAttack = in_array('attack_min', $all_raw_keys) || in_array('attack_max', $all_raw_keys);
    $hasMagicAttack = in_array('magic_attack_min', $all_raw_keys) || in_array('magic_attack_max', $all_raw_keys);
    $hasMagicBurst = in_array('magic_burst_min', $all_raw_keys) || in_array('magic_burst_max', $all_raw_keys);

    if ($hasPhysAttack) {
        $all_base_keys[] = 'attack_range';
    }
    if ($hasMagicAttack) {
        $all_base_keys[] = 'magic_attack_range';
    }
    if ($hasMagicBurst) {
        $all_base_keys[] = 'magic_burst_range';
    }

    foreach ($all_raw_keys as $k) {
        if (in_array($k, ['attack_min', 'attack_max', 'magic_attack_min', 'magic_attack_max', 'magic_burst_min', 'magic_burst_max'])) {
            continue;
        }
        $all_base_keys[] = $k;
    }

    // Przedmioty, które faktycznie mogą nosić zaklęcia Wiedźmy (patrz
    // EnchantmentStrategy::poolFor()) - tylko dla nich renderujemy 5 stałych
    // slotów zaczarowań po prawej stronie tooltipa.
    $isEnchantable = in_array($template->type ?? null, ['weapon', 'armor', 'accessory']);

    $hasAnyStats = count($all_base_keys) > 0 || count($enchants) > 0 || count($equipped_enchants) > 0 || $isEnchantable;
    
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

    // Etykiety/ikony slotu i typu przedmiotu - dla czytelnych "chipów" w nagłówku
    // tooltipa (zamiast surowego "Slot: main_hand | Typ: weapon").
    $slotMeta = [
        'head' => ['label' => 'Głowa', 'icon' => 'fa-helmet-safety'],
        'chest' => ['label' => 'Klatka', 'icon' => 'fa-shield-halved'],
        'main_hand' => ['label' => 'Główna Ręka', 'icon' => 'fa-khanda'],
        'neck' => ['label' => 'Szyja', 'icon' => 'fa-gem'],
        'ring' => ['label' => 'Pierścień', 'icon' => 'fa-ring'],
        'feet' => ['label' => 'Stopy', 'icon' => 'fa-shoe-prints'],
    ];
    $typeLabels = [
        'weapon' => 'Broń',
        'armor' => 'Zbroja',
        'accessory' => 'Biżuteria',
        'material' => 'Materiał',
        'consumable' => (($template->sub_type ?? '') === 'chest' ? 'Skrzynia' : 'Eliksir'),
        'egg' => 'Jajko',
    ];
    $slotKey = $template->slot ?? null;
    $slotLabel = $slotMeta[$slotKey]['label'] ?? ($slotKey ? ucfirst($slotKey) : null);
    $slotIcon = $slotMeta[$slotKey]['icon'] ?? 'fa-circle-dot';
    $typeLabel = $typeLabels[$template->type ?? ''] ?? ucfirst($template->type ?? 'Nieznany');

    // Check if slot matches to allow compare
    $canCompare = false;
    if ($equippedItem && ($equippedItem->id ?? null) !== ($item->id ?? null)) {
        if (($template->slot ?? null) === ($equippedItem->template->slot ?? null)) {
            $canCompare = true;
        }
    }

    $formatStatName = function(string $statKey): string {
        $map = [
            'attack_range' => 'Atak',
            'magic_attack_range' => 'Magiczny Atak',
            'magic_burst_range' => 'Rozbłysk Magii',
            'attack_min' => 'Atak Min',
            'attack_max' => 'Atak Max',
            'magic_attack_min' => 'Magiczny Atak Min',
            'magic_attack_max' => 'Magiczny Atak Max',
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

    // $isEnchant: 'hp_bonus'/'defense' są płaskimi punktami jako bazowa statystyka
    // przedmiotu (base_stats), ale procentowym (ryzykownym, patrz EnchantmentStrategy)
    // afiksem Wiedźmy (enchants) - te same klucze, różne znaczenie zależnie od źródła,
    // więc kontekst trzeba przekazać jawnie z miejsca wywołania.
    $isPercentStat = function(string $statKey, bool $isEnchant = false): bool {
        if (str_contains($statKey, 'chance') || str_contains($statKey, 'strong_vs') || str_contains($statKey, 'resist') || str_contains($statKey, 'percent') || str_contains($statKey, 'rate')) {
            return true;
        }
        if (in_array($statKey, ['exp_bonus', 'gold_bonus', 'crit_damage', 'life_steal', 'mana_steal', 'attack_power', 'magic_attack'])) {
            return true;
        }
        if ($isEnchant && in_array($statKey, ['hp_bonus', 'defense'])) {
            return true;
        }
        return false;
    };

    // Skraca duże liczby do formy "1k" / "1.5k" / "1M". Liczby < 1000 wracają bez zmian.
    $formatNumber = function ($value) {
        return \App\Support\NumberHelper::formatShort($value);
    };

    // Jakość (rzadkość) przedmiotu - tylko realne ItemInstance mają wylosowaną
    // rarity (podglądy szablonów w rzemiośle/sklepie jej nie mają).
    $rarity = $item->rarity ?? null;
    $rarityLabel = match ($rarity) {
        'common' => 'Zwykła',
        'uncommon' => 'Niezwykła',
        'rare' => 'Rzadka',
        'epic' => 'Epicka',
        'legendary' => 'Legendarna',
        default => $rarity ? ucfirst($rarity) : null,
    };
    $rarityClass = match ($rarity) {
        'uncommon' => 'text-emerald-400 border-emerald-600/60 bg-emerald-950/20',
        'rare' => 'text-sky-400 border-sky-500/60 bg-sky-950/20',
        'epic' => 'text-purple-400 border-purple-500/60 bg-purple-950/20',
        'legendary' => 'text-amber-400 border-amber-400/70 bg-amber-950/30 shadow-[0_0_8px_rgba(245,158,11,0.3)]',
        default => 'text-gray-300 border-slate-600/60 bg-slate-800/80',
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
            <div class="flex flex-wrap items-center gap-1 mt-1 mb-0.5">
                @if($slotLabel)
                    <span class="inline-flex items-center gap-1 bg-slate-800/80 border border-slate-600/60 rounded px-1.5 py-0.5 text-[10px] font-semibold text-gray-300">
                        <i class="fa-solid {{ $slotIcon }} text-amber-400/80"></i> {{ $slotLabel }}
                    </span>
                @endif
                <span class="inline-flex items-center gap-1 bg-slate-800/80 border border-slate-600/60 rounded px-1.5 py-0.5 text-[10px] font-semibold text-gray-300">
                    {{ $typeLabel }}@if($subTypeLabel) <span class="text-gray-500">·</span> {{ $subTypeLabel }}@endif
                </span>
                <span class="inline-flex items-center gap-1 bg-slate-800/80 border border-slate-600/60 rounded px-1.5 py-0.5 text-[10px] font-semibold text-amber-300/90">
                    <i class="fa-solid fa-arrow-up-right-dots"></i> Poz. {{ $template->level_requirement ?? 1 }}
                </span>
                @if($rarityLabel)
                    <span class="inline-flex items-center gap-1 border rounded px-1.5 py-0.5 text-[10px] font-semibold {{ $rarityClass }}">
                        <i class="fa-solid fa-gem"></i> {{ $rarityLabel }}
                    </span>
                @endif
            </div>
            @if(isset($roll_stats['mint']))
                <p class="text-red-400 font-bold text-xs uppercase animate-pulse border-b border-red-500/50 pb-1 w-max">
                    <i class="fa-solid fa-fire text-red-500 mr-1"></i> Nakład: {{ $roll_stats['mint'] }} / {{ $roll_stats['max_mint'] }}
                </p>
            @endif
            @if(($template->type ?? null) === 'material' && $dropSources !== null)
                <div class="mt-1.5 pt-1.5 border-t border-slate-700/70">
                    <p class="text-[10px] uppercase tracking-wider text-gray-400 mb-1">Wypada z</p>
                    @if(count($dropSources) > 0)
                        @php
                            $uniqueDropSources = collect($dropSources)->unique(fn($d) => (is_array($d) ? (($d['monster'] ?? '') . '_' . ($d['map'] ?? '')) : $d))->values();
                        @endphp
                        <div class="flex flex-wrap gap-1">
                            @foreach($uniqueDropSources as $drop)
                                <span class="bg-gray-800 border border-gray-600 text-gray-300 text-[10px] px-1.5 py-0.5 rounded">
                                    @if(is_array($drop))
                                        {{ $drop['monster'] }}@if(!empty($drop['map']))<span class="text-amber-400/80"> · {{ $drop['map'] }}</span>@endif
                                    @else
                                        {{ $drop }}
                                    @endif
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

    <div class="flex flex-col sm:flex-row gap-3 sm:gap-4" :class="compare ? 'w-full sm:min-w-[580px] md:min-w-[640px]' : 'w-full sm:min-w-[340px] md:min-w-[380px]'">
        <!-- Ten przedmiot -->
        <div class="flex-1">
            @if($hasAnyStats)
                <div class="flex gap-3">
                    <div class="flex-1 min-w-0 text-sm text-gray-200 space-y-1">
                        @foreach($all_base_keys as $stat)
                            @if(in_array($stat, ['attack_range', 'magic_attack_range', 'magic_burst_range']))
                                @php
                                    $rangeConfigs = [
                                        'attack_range' => ['min' => 'attack_min', 'max' => 'attack_max', 'label' => 'Atak'],
                                        'magic_attack_range' => ['min' => 'magic_attack_min', 'max' => 'magic_attack_max', 'label' => 'Magiczny Atak'],
                                        'magic_burst_range' => ['min' => 'magic_burst_min', 'max' => 'magic_burst_max', 'label' => 'Rozbłysk Magii'],
                                    ];
                                    $cfg = $rangeConfigs[$stat];
                                    $minK = $cfg['min'];
                                    $maxK = $cfg['max'];

                                    $val_min = $base_stats[$minK] ?? 0;
                                    $val_max = $base_stats[$maxK] ?? 0;

                                    $isRangeMin = is_array($val_min);
                                    $isRangeMax = is_array($val_max);

                                    $up_min = $upgrade_bonus[$minK] ?? 0;
                                    $up_max = $upgrade_bonus[$maxK] ?? 0;

                                    $isMaxed = $isInstance && method_exists($item, 'isStatMaxed') && ($item->isStatMaxed($minK) || $item->isStatMaxed($maxK));

                                    $eq_val_raw_min = $canCompare ? ($equipped_base_stats[$minK] ?? 0) : 0;
                                    $eq_val_raw_max = $canCompare ? ($equipped_base_stats[$maxK] ?? 0) : 0;
                                    $eq_val_min = is_array($eq_val_raw_min) ? 0 : $eq_val_raw_min;
                                    $eq_val_max = is_array($eq_val_raw_max) ? 0 : $eq_val_raw_max;
                                    $eq_up_min = $canCompare ? ($equipped_upgrade_bonus[$minK] ?? 0) : 0;
                                    $eq_up_max = $canCompare ? ($equipped_upgrade_bonus[$maxK] ?? 0) : 0;

                                    $tot_item_min = (is_array($val_min) ? 0 : $val_min) + $up_min;
                                    $tot_item_max = (is_array($val_max) ? 0 : $val_max) + $up_max;
                                    $tot_eq_min = $eq_val_min + $eq_up_min;
                                    $tot_eq_max = $eq_val_max + $eq_up_max;

                                    $diff_min = $tot_item_min - $tot_eq_min;
                                    $diff_max = $tot_item_max - $tot_eq_max;
                                @endphp

                                @if($isRangeMin || $isRangeMax)
                                    <div class="flex justify-between items-center gap-2">
                                        <span class="capitalize text-gray-200 shrink-0 whitespace-nowrap">{{ $cfg['label'] }}</span>
                                        <span class="font-bold text-gray-200 shrink-0 whitespace-nowrap">
                                            @if($isRangeMin && $isRangeMax)
                                                {{ $formatNumber($val_min[0]) }}-{{ $formatNumber($val_min[1]) }} ~ {{ $formatNumber($val_max[0]) }}-{{ $formatNumber($val_max[1]) }}
                                            @elseif($isRangeMin)
                                                {{ $formatNumber($val_min[0]) }}-{{ $formatNumber($val_min[1]) }}
                                            @else
                                                {{ $formatNumber($val_min) }}-{{ $formatNumber($val_max) }}
                                            @endif
                                        </span>
                                    </div>
                                @else
                                    <div class="space-y-0.5" x-show="compare || {{ ($val_min > 0 || $val_max > 0 || $up_min > 0 || $up_max > 0) ? 'true' : 'false' }}">
                                        <div class="flex justify-between items-center gap-2">
                                            <span class="capitalize text-gray-200 shrink-0 whitespace-nowrap">{{ $cfg['label'] }}</span>
                                            <div class="flex items-center gap-1 shrink-0 whitespace-nowrap">
                                                <span class="font-bold {{ $isMaxed ? 'text-yellow-400 font-extrabold' : (($val_min > 0 || $val_max > 0) ? 'text-gray-200' : 'text-gray-500') }}">
                                                    {{ $formatNumber($val_min) }}-{{ $formatNumber($val_max) }}
                                                </span>
                                                <span x-show="compare" class="text-xs font-bold w-16 text-right ml-1 {{ ($diff_min > 0 || $diff_max > 0) ? 'text-green-400 font-extrabold' : (($diff_min < 0 || $diff_max < 0) ? 'text-red-400 font-extrabold' : 'text-gray-500') }}">
                                                    @if($diff_min > 0 && $diff_max > 0)
                                                        (+{{ $formatNumber($diff_min) }}-+{{ $formatNumber($diff_max) }})
                                                    @elseif($diff_min > 0 || $diff_max > 0)
                                                        (+{{ $formatNumber($diff_min) }}/+{{ $formatNumber($diff_max) }})
                                                    @elseif($diff_min < 0 || $diff_max < 0)
                                                        ({{ $formatNumber($diff_min) }}-{{ $formatNumber($diff_max) }})
                                                    @else
                                                        (- )
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                        @if($up_min > 0 || $up_max > 0)
                                            <div class="flex justify-end text-amber-400 font-semibold text-xs leading-none">
                                                (+{{ $formatNumber($up_min) }}-+{{ $formatNumber($up_max) }})
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            @else
                                @php
                                    $val = $base_stats[$stat] ?? 0;
                                    $isRange = is_array($val);
                                    $suffix = $isPercentStat($stat) ? '%' : '';
                                @endphp
                                @if($isRange)
                                    {{-- Template-only preview (no instance rolled yet): show the raw min-max range. --}}
                                    <div class="flex justify-between items-center gap-2">
                                        <span class="capitalize text-gray-200 shrink-0 whitespace-nowrap">{{ $formatStatName($stat) }}</span>
                                        <span class="font-bold text-gray-200 shrink-0 whitespace-nowrap">{{ $formatNumber($val[0]) }}-{{ $formatNumber($val[1]) }}{{ $suffix }}</span>
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
                                    <div class="flex justify-between items-center gap-2" x-show="compare || {{ ($val > 0 || $up_val > 0) ? 'true' : 'false' }}">
                                        <span class="capitalize text-gray-200 shrink-0 whitespace-nowrap">{{ $formatStatName($stat) }}</span>
                                        <div class="flex items-center gap-1 shrink-0 whitespace-nowrap">
                                            <span class="font-bold {{ $isMaxed ? 'text-yellow-400 font-extrabold' : ($val > 0 ? 'text-gray-200' : 'text-gray-500') }}">+{{ $formatNumber($val) }}{{ $suffix }}</span>
                                            @if($up_val > 0)
                                                <span class="text-amber-400 font-semibold text-xs ml-0.5">(+{{ $formatNumber($up_val) }}{{ $suffix }})</span>
                                            @endif
                                            <span x-show="compare" class="text-xs font-bold w-12 text-right ml-1 {{ $diff > 0 ? 'text-green-400 font-extrabold' : ($diff < 0 ? 'text-red-400 font-extrabold' : 'text-gray-500') }}">
                                                @if($diff > 0)(+{{ $formatNumber($diff) }}{{ $suffix }})@elseif($diff < 0)({{ $formatNumber($diff) }}{{ $suffix }})@else(- )@endif
                                            </span>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        @endforeach
                    </div>

                    @if($isEnchantable)
                        <div class="w-px self-stretch bg-slate-600/60"></div>

                        <div class="w-[152px] shrink-0 space-y-1.5">
                            <p class="text-[10px] uppercase tracking-wider text-gray-400 font-bold mb-1 flex items-center gap-1">
                                <i class="fa-solid fa-wand-sparkles text-gray-400"></i> Zaczarowania
                            </p>
                            @php
                                $enchantKeys = array_keys($enchants);
                                $itemType = $template->type ?? '';
                            @endphp
                            @for ($i = 0; $i < 5; $i++)
                                @php
                                    $stat = $enchantKeys[$i] ?? null;
                                    $val = $stat ? ($enchants[$stat] ?? 0) : 0;
                                    $eq_val = ($canCompare && $stat) ? ($equipped_enchants[$stat] ?? 0) : 0;
                                    $diff = $val - $eq_val;
                                    $suffix = $stat ? ($isPercentStat($stat, true) ? '%' : '') : '';
                                    $isMaxed = $stat ? \App\Domain\Wizard\EnchantmentStrategy::isEnchantMaxed($itemType, $stat, $val) : false;
                                @endphp
                                @if($stat)
                                    <div class="flex items-center justify-between gap-1 text-xs">
                                        <span class="truncate text-gray-200">{{ $formatStatName($stat) }}</span>
                                        <span class="flex items-center gap-1 shrink-0">
                                            <span class="font-bold {{ $isMaxed ? 'text-yellow-400 font-extrabold' : 'text-gray-200' }}">{{ $val > 0 ? '+' : '' }}{{ $formatNumber($val) }}{{ $suffix }}</span>
                                            <span x-show="compare" class="text-[10px] font-bold {{ $diff > 0 ? 'text-green-400 font-extrabold' : ($diff < 0 ? 'text-red-400 font-extrabold' : 'text-gray-500') }}">
                                                @if($diff > 0)(+{{ $formatNumber($diff) }}{{ $suffix }})@elseif($diff < 0)({{ $formatNumber($diff) }}{{ $suffix }})@else(- )@endif
                                            </span>
                                        </span>
                                    </div>
                                @else
                                    <div class="flex items-center gap-1.5 text-gray-600 italic text-xs">
                                        <i class="fa-solid fa-slash text-[9px] shrink-0"></i> - brak -
                                    </div>
                                @endif
                            @endfor
                        </div>
                    @endif
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
                <div class="flex gap-3">
                    <div class="flex-1 min-w-0 text-sm text-gray-300 space-y-1">
                        @foreach($all_base_keys as $stat)
                            @if(in_array($stat, ['attack_range', 'magic_attack_range', 'magic_burst_range']))
                                @php
                                    $rangeConfigs = [
                                        'attack_range' => ['min' => 'attack_min', 'max' => 'attack_max', 'label' => 'Atak'],
                                        'magic_attack_range' => ['min' => 'magic_attack_min', 'max' => 'magic_attack_max', 'label' => 'Magiczny Atak'],
                                        'magic_burst_range' => ['min' => 'magic_burst_min', 'max' => 'magic_burst_max', 'label' => 'Rozbłysk Magii'],
                                    ];
                                    $cfg = $rangeConfigs[$stat];
                                    $minK = $cfg['min'];
                                    $maxK = $cfg['max'];

                                    $eq_val_raw_min = $equipped_base_stats[$minK] ?? 0;
                                    $eq_val_raw_max = $equipped_base_stats[$maxK] ?? 0;
                                    $eq_val_min = is_array($eq_val_raw_min) ? 0 : $eq_val_raw_min;
                                    $eq_val_max = is_array($eq_val_raw_max) ? 0 : $eq_val_raw_max;
                                    $eq_up_min = $equipped_upgrade_bonus[$minK] ?? 0;
                                    $eq_up_max = $equipped_upgrade_bonus[$maxK] ?? 0;

                                    $eq_isMaxed = $eq_isInstance && method_exists($equippedItem, 'isStatMaxed') && ($equippedItem->isStatMaxed($minK) || $equippedItem->isStatMaxed($maxK));
                                @endphp
                                @if($eq_val_min > 0 || $eq_val_max > 0 || $eq_up_min > 0 || $eq_up_max > 0)
                                    <div class="space-y-0.5">
                                        <div class="flex justify-between items-center gap-2">
                                            <span class="capitalize text-gray-400 shrink-0 whitespace-nowrap">{{ $cfg['label'] }}</span>
                                            <span class="font-bold shrink-0 whitespace-nowrap {{ $eq_isMaxed ? 'text-yellow-400 font-extrabold' : 'text-gray-200' }}">
                                                {{ $formatNumber($eq_val_min) }}-{{ $formatNumber($eq_val_max) }}
                                            </span>
                                        </div>
                                        @if($eq_up_min > 0 || $eq_up_max > 0)
                                            <div class="flex justify-end text-amber-400 font-semibold text-xs leading-none">
                                                (+{{ $formatNumber($eq_up_min) }}-+{{ $formatNumber($eq_up_max) }})
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            @else
                                @php
                                    $eq_val_raw = $equipped_base_stats[$stat] ?? 0;
                                    $eq_val = is_array($eq_val_raw) ? 0 : $eq_val_raw;
                                    $eq_up_val = $equipped_upgrade_bonus[$stat] ?? 0;
                                    $eq_isMaxed = $eq_isInstance && method_exists($equippedItem, 'isStatMaxed') && $equippedItem->isStatMaxed($stat);
                                    $suffix = $isPercentStat($stat) ? '%' : '';
                                @endphp
                                @if($eq_val > 0 || $eq_up_val > 0)
                                    <div class="flex justify-between items-center gap-2">
                                        <span class="capitalize text-gray-400 shrink-0 whitespace-nowrap">{{ $formatStatName($stat) }}</span>
                                        <div class="flex items-center gap-1 shrink-0 whitespace-nowrap">
                                            <span class="font-bold {{ $eq_isMaxed ? 'text-yellow-400 font-extrabold' : 'text-gray-200' }}">+{{ $formatNumber($eq_val) }}{{ $suffix }}</span>
                                            @if($eq_up_val > 0)
                                                <span class="text-amber-400 font-semibold text-xs ml-0.5">(+{{ $formatNumber($eq_up_val) }}{{ $suffix }})</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endif
                        @endforeach
                    </div>

                    @if($isEnchantable)
                        <div class="w-px self-stretch bg-slate-700"></div>

                        <div class="w-[152px] shrink-0 space-y-1.5">
                            <p class="text-[10px] uppercase tracking-wider text-gray-400 font-bold mb-1 flex items-center gap-1">
                                <i class="fa-solid fa-wand-sparkles text-gray-400"></i> Zaczarowania
                            </p>
                            @php
                                $eqEnchantKeys = array_keys($equipped_enchants);
                                $eqType = $equippedItem->template->type ?? $template->type ?? '';
                            @endphp
                            @for ($i = 0; $i < 5; $i++)
                                @php
                                    $stat = $eqEnchantKeys[$i] ?? null;
                                    $val = $stat ? ($equipped_enchants[$stat] ?? 0) : 0;
                                    $suffix = $stat ? ($isPercentStat($stat, true) ? '%' : '') : '';
                                    $isMaxed = $stat ? \App\Domain\Wizard\EnchantmentStrategy::isEnchantMaxed($eqType, $stat, $val) : false;
                                @endphp
                                @if($stat)
                                    <div class="flex items-center justify-between gap-1 text-xs">
                                        <span class="truncate text-gray-200">{{ $formatStatName($stat) }}</span>
                                        <span class="font-bold shrink-0 {{ $isMaxed ? 'text-yellow-400 font-extrabold' : 'text-gray-200' }}">{{ $val > 0 ? '+' : '' }}{{ $formatNumber($val) }}{{ $suffix }}</span>
                                    </div>
                                @else
                                    <div class="flex items-center gap-1.5 text-gray-600 italic text-xs">
                                        <i class="fa-solid fa-slash text-[9px] shrink-0"></i> - brak -
                                    </div>
                                @endif
                            @endfor
                        </div>
                    @endif
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
