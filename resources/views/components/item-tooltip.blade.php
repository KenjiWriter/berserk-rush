@props([
    'item', 
    'equippedItem' => null,
])

@php
    $template = $item->template ?? $item;
    $upgrade_level = $item->upgrade_level ?? 0;
    
    // Safety fallback for arrays (in case it's a model without json cast loaded properly)
    $base_stats = is_array($template->base_stats) ? $template->base_stats : (json_decode($template->base_stats, true) ?? []);
    
    $roll_stats = [];
    if (isset($item->roll_stats)) {
        $roll_stats = is_array($item->roll_stats) ? $item->roll_stats : (json_decode($item->roll_stats, true) ?? []);
    }
    $enchants = $roll_stats['enchants'] ?? [];
    
    // For comparison:
    $equipped_base_stats = [];
    $equipped_enchants = [];
    
    if ($equippedItem) {
        $eq_template = $equippedItem->template ?? $equippedItem;
        $equipped_base_stats = is_array($eq_template->base_stats) ? $eq_template->base_stats : (json_decode($eq_template->base_stats, true) ?? []);
        
        $eq_roll_stats = [];
        if (isset($equippedItem->roll_stats)) {
            $eq_roll_stats = is_array($equippedItem->roll_stats) ? $equippedItem->roll_stats : (json_decode($equippedItem->roll_stats, true) ?? []);
        }
        $equipped_enchants = $eq_roll_stats['enchants'] ?? [];
    }

    $all_base_keys = array_unique(array_merge(array_keys($base_stats), array_keys($equipped_base_stats)));
    $all_enchant_keys = array_unique(array_merge(array_keys($enchants), array_keys($equipped_enchants)));
    
    $hasAnyStats = count($all_base_keys) > 0 || count($all_enchant_keys) > 0;
    
    // Check if slot matches to allow compare
    $canCompare = false;
    if ($equippedItem && $equippedItem->id !== ($item->id ?? null)) {
        if (($template->slot ?? null) === ($equippedItem->template->slot ?? null)) {
            $canCompare = true;
        }
    }
@endphp

<div class="p-4 relative bg-gray-900 border-2 border-slate-600 rounded-lg shadow-2xl pointer-events-auto" x-data="{ compare: {{ $canCompare ? 'true' : 'false' }} }" @click.stop>
    <!-- Górny pasek -->
    <div class="flex justify-between items-center mb-2">
        <div>
            <h4 class="font-bold text-lg text-amber-400">
                {{ $template->name }} 
                @if($upgrade_level > 0)<span class="text-amber-500 text-sm ml-1">+{{ $upgrade_level }}</span>@endif
            </h4>
            <p class="text-xs text-gray-400">Slot: {{ ucfirst($template->slot ?? 'Brak') }} | Typ: {{ ucfirst($template->type ?? 'Nieznany') }} | Poz: {{ $template->level_requirement ?? 1 }}</p>
            @if(isset($roll_stats['mint']))
                <p class="text-red-400 font-bold text-xs uppercase animate-pulse border-b border-red-500/50 pb-1 w-max">
                    <i class="fa-solid fa-fire text-red-500 mr-1"></i> Nakład: {{ $roll_stats['mint'] }} / {{ $roll_stats['max_mint'] }}
                </p>
            @endif
        </div>
        
        @if(method_exists($item, 'getCombatPower'))
            <span class="text-indigo-300 font-bold ml-2 flex items-center gap-1"><i class="fa-solid fa-bolt text-indigo-400"></i> {{ $item->getCombatPower() }}</span>
        @endif
    </div>
    
    @if($canCompare)
        <div class="mb-3 flex items-center justify-between bg-slate-800/80 px-2.5 py-1.5 rounded border border-slate-600/50">
            <span class="text-xs text-amber-300 font-bold flex items-center gap-1.5">
                <i class="fa-solid fa-scale-balanced text-amber-400"></i> Porównanie z założonym
            </span>
            <button @click="compare = !compare" class="text-[11px] text-gray-400 hover:text-amber-200 underline font-semibold transition">
                <span x-show="compare">Ukryj</span>
                <span x-show="!compare">Pokaż</span>
            </button>
        </div>
    @endif

    <div class="flex flex-col sm:flex-row gap-3 sm:gap-4" :class="compare ? 'w-full sm:min-w-[340px] md:min-w-[440px]' : 'w-full sm:min-w-[200px]'">
        <!-- Ten przedmiot -->
        <div class="flex-1">
            @if($hasAnyStats)
                <div class="text-sm text-green-400 space-y-1">
                    @foreach($all_base_keys as $stat)
                        @php
                            $val = $base_stats[$stat] ?? 0;
                            $eq_val = $canCompare ? ($equipped_base_stats[$stat] ?? 0) : 0;
                            $diff = $val - $eq_val;
                        @endphp
                        <div class="flex justify-between items-center" x-show="compare || {{ $val }} > 0">
                            <span class="capitalize text-gray-200">{{ str_replace('_', ' ', $stat) }}</span>
                            <div class="flex items-center gap-2">
                                <span class="font-bold {{ $val > 0 ? 'text-green-400' : 'text-gray-500' }}">+{{ $val }}</span>
                                <span x-show="compare" class="text-xs font-bold w-12 text-right {{ $diff > 0 ? 'text-green-400 font-extrabold' : ($diff < 0 ? 'text-red-400 font-extrabold' : 'text-gray-500') }}">
                                    @if($diff > 0)(+{{ $diff }})@elseif($diff < 0)({{ $diff }})@else(- )@endif
                                </span>
                            </div>
                        </div>
                    @endforeach
                    @foreach($all_enchant_keys as $stat)
                        @php
                            $val = $enchants[$stat] ?? 0;
                            $eq_val = $canCompare ? ($equipped_enchants[$stat] ?? 0) : 0;
                            $diff = $val - $eq_val;
                        @endphp
                        <div class="flex justify-between items-center text-purple-400" x-show="compare || {{ $val }} > 0">
                            <span class="capitalize flex items-center gap-1"><i class="fa-solid fa-star text-purple-400 text-xs"></i> {{ str_replace('_', ' ', $stat) }}</span>
                            <div class="flex items-center gap-2">
                                <span class="font-bold {{ $val > 0 ? 'text-purple-300' : 'text-gray-600' }}">+{{ $val }}</span>
                                <span x-show="compare" class="text-xs font-bold w-12 text-right {{ $diff > 0 ? 'text-green-400 font-extrabold' : ($diff < 0 ? 'text-red-400 font-extrabold' : 'text-gray-500') }}">
                                    @if($diff > 0)(+{{ $diff }})@elseif($diff < 0)({{ $diff }})@else(- )@endif
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
                    @if($equippedItem->upgrade_level > 0)<span class="text-amber-500">+{{ $equippedItem->upgrade_level }}</span>@endif
                </p>
                <div class="text-sm text-gray-300 space-y-1">
                    @foreach($all_base_keys as $stat)
                        @if(($equipped_base_stats[$stat] ?? 0) > 0)
                            <div class="flex justify-between">
                                <span class="capitalize text-gray-400">{{ str_replace('_', ' ', $stat) }}</span>
                                <span class="font-bold text-gray-200">+{{ $equipped_base_stats[$stat] }}</span>
                            </div>
                        @endif
                    @endforeach
                    @foreach($all_enchant_keys as $stat)
                        @if(($equipped_enchants[$stat] ?? 0) > 0)
                            <div class="flex justify-between text-purple-400/80">
                                <span class="capitalize flex items-center gap-1"><i class="fa-solid fa-star text-purple-400 text-xs"></i> {{ str_replace('_', ' ', $stat) }}</span>
                                <span class="font-bold">+{{ $equipped_enchants[$stat] }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
                @if(method_exists($equippedItem, 'getCombatPower'))
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
