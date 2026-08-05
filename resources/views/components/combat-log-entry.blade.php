@props([
    'turn',
    'index' => 0,
    'playerName' => 'Gracz',
    'enemyName' => 'Wróg',
])

{{--
    Pojedynczy wpis logu walki (Faza 3 rebalansu, 2026-08-05). Wspólny dla widoków
    map-stub / dungeon-run / arena-combat - jedno źródło formatowania tur walki, żeby
    te trzy widoki nie rozjechały się w brzmieniu/kolorach. Renderuje samą ZAWARTOŚĆ
    wpisu (odznaka tury + treść) - każdy widok owija to własnym <li> z wire:key.
    Mapowanie efektów -> etykieta/kolor/ikona: App\Helpers\CombatLogHelper.
--}}
@php
    // Nazwa aktora: jawny `actor_name` (Arena/GvG - konkretny gracz w drużynie) ma
    // pierwszeństwo; w PvE liczona z `actor` + przekazanych nazw.
    $turnEnemyName = $turn['enemy_name'] ?? $enemyName;
    $actorName = $turn['actor_name'] ?? (($turn['actor'] ?? 'enemy') == 'player' ? $playerName : $turnEnemyName);
    // Cel ataku (Arena/GvG 5v5) - renderowany tylko gdy obecny.
    $targetName = $turn['target_name'] ?? null;
    // Numer tury: `round` (Arena) ma pierwszeństwo nad indeksem pętli.
    $turnNo = $turn['round'] ?? ($index + 1);
    $tType = $turn['type'] ?? 'hit';
    // Ujednolicone klucze: skille gracza używają snake_case, skille potworów (Faza 2) - camelCase.
    $skillName = $turn['skillName'] ?? $turn['skill_name'] ?? null;
    $effectKey = $turn['effectType'] ?? $turn['effect_type'] ?? null;
    $isMagicHit = !empty($turn['isMagic']) || !empty($turn['is_magic']) || !empty($turn['magicDamage']);
    // DoT tykający na GRACZU w tej turze (Faza 2 - skille potworów).
    $pDotDmg = $turn['playerDotDamage'] ?? 0;
    $pDotType = $turn['playerDotType'] ?? 'poison';
@endphp
<span class="inline-block w-8 sm:w-9 text-center text-xs font-bold bg-amber-900/80 text-amber-200 rounded-md border border-amber-600/40 px-1 py-0.5 mr-1.5 font-mono">
    T{{ $turnNo }}
</span>
@if ($tType == 'miss')
    <span class="text-slate-300 italic font-semibold">
        <strong class="text-amber-200">{{ $actorName }}</strong>
        pudłuje atak!
        @if (!empty($turn['dotDamage']))
            <span class="text-emerald-400 font-mono font-bold ml-1">(+{{ \App\Helpers\FormatHelper::short($turn['dotDamage']) }})</span>
        @endif
    </span>
@elseif ($tType == 'crowd_controlled')
    <span class="text-yellow-200 font-semibold italic">
        <i class="fa-solid fa-bolt text-yellow-300 mr-0.5"></i>
        <strong class="text-amber-200">{{ $actorName }}</strong> jest unieruchomiony i traci turę!
    </span>
@elseif ($tType == 'player_dot')
    <span class="{{ \App\Helpers\CombatLogHelper::color($pDotType) }} font-semibold italic">
        <i class="fa-solid {{ \App\Helpers\CombatLogHelper::icon($pDotType) }} mr-0.5"></i>
        <strong class="text-amber-200">{{ $playerName }}</strong> pada!
        <span class="inline-flex items-center gap-0.5 text-[10px] font-bold px-1 py-px rounded border {{ \App\Helpers\CombatLogHelper::badgeClasses($pDotType) }} ml-0.5">{{ \App\Helpers\CombatLogHelper::label($pDotType) }} -{{ \App\Helpers\FormatHelper::short($pDotDmg) }} HP</span>
    </span>
@elseif ($tType == 'dot')
    <span class="text-purple-300 font-semibold italic">
        Zadano <strong class="text-purple-200 font-mono">{{ \App\Helpers\FormatHelper::short($turn['value']) }}</strong> obrażeń od statusów.
    </span>
@elseif ($tType == 'skill_heal')
    <span class="text-emerald-300 font-semibold">
        <i class="fa-solid fa-heart text-emerald-400 mr-0.5"></i>
        <strong class="text-amber-200">{{ $actorName }}</strong>
        używa <span class="text-indigo-300 font-bold uppercase">{{ $skillName ?? 'Leczenie' }}</span> i odnawia <strong class="text-emerald-400 font-mono">{{ \App\Helpers\FormatHelper::short($turn['value']) }} HP</strong>!
        @if ($pDotDmg > 0)
            <span class="{{ \App\Helpers\CombatLogHelper::color($pDotType) }} font-mono font-bold ml-1">(-{{ \App\Helpers\FormatHelper::short($pDotDmg) }} {{ \App\Helpers\CombatLogHelper::label($pDotType) }})</span>
        @endif
    </span>
@elseif ($tType == 'skill' && (int)($turn['value'] ?? 0) === 0)
    {{-- Skill bez bezpośrednich obrażeń (np. potwór nakłada DoT) --}}
    <span class="{{ ($turn['actor'] ?? '') == 'player' ? 'text-blue-300' : 'text-red-300' }} font-semibold">
        @if($effectKey)<i class="fa-solid {{ \App\Helpers\CombatLogHelper::icon($effectKey) }} {{ \App\Helpers\CombatLogHelper::color($effectKey) }} mr-0.5"></i>@endif
        <strong class="text-amber-200">{{ $actorName }}</strong>
        rzuca <span class="text-indigo-300 font-bold uppercase">{{ $skillName ?? 'Umiejętność' }}</span>!@if($effectKey) <span class="inline-flex items-center gap-0.5 text-[10px] font-bold px-1 py-px rounded border {{ \App\Helpers\CombatLogHelper::badgeClasses($effectKey) }} ml-0.5"><i class="fa-solid {{ \App\Helpers\CombatLogHelper::icon($effectKey) }}"></i>{{ \App\Helpers\CombatLogHelper::label($effectKey) }}</span>@endif
    </span>
@elseif ($tType == 'skill')
    <span class="{{ ($turn['actor'] ?? '') == 'player' ? 'text-blue-300' : 'text-red-300' }} font-semibold">
        <strong class="text-amber-200">{{ $actorName }}</strong>
        używa <span class="text-indigo-300 font-bold uppercase">{{ $skillName ?? 'Umiejętność' }}</span>@if($targetName) na <strong class="text-amber-200">{{ $targetName }}</strong>@endif i zadaje <strong class="text-amber-300 font-mono">{{ \App\Helpers\FormatHelper::short($turn['value']) }}</strong>@if ($isMagicHit) <span class="inline-flex items-center gap-0.5 text-[10px] font-bold px-1 py-px rounded border {{ \App\Helpers\CombatLogHelper::badgeClasses('magic') }}"><i class="fa-solid fa-wand-sparkles"></i>MAGIA</span>@endif
        @if (!empty($turn['dotDamage']))
            <span class="text-emerald-400 font-mono font-bold ml-1">(+{{ \App\Helpers\FormatHelper::short($turn['dotDamage']) }})</span>
        @endif
        obrażeń
        @if (!empty($turn['crit'])) <span class="font-bold text-amber-400 drop-shadow-[0_0_10px_rgba(245,158,11,0.8)]">KRYTYK!</span> @endif
        @if(!empty($turn['ccType'])) <span class="inline-flex items-center gap-0.5 text-[10px] font-bold px-1 py-px rounded border {{ \App\Helpers\CombatLogHelper::badgeClasses($turn['ccType']) }}"><i class="fa-solid {{ \App\Helpers\CombatLogHelper::icon($turn['ccType']) }}"></i>{{ \App\Helpers\CombatLogHelper::label($turn['ccType']) }}</span>@endif
    </span>
@else
    <span class="{{ ($turn['actor'] ?? '') == 'player' ? 'text-emerald-300' : 'text-rose-300' }} font-semibold">
        <strong class="text-amber-200">{{ $actorName }}</strong>
        zadaje
        @if(($turn['actor'] ?? '') == 'player' && isset($turn['bonusDamage']) && $turn['bonusDamage'] > 0)
            <strong class="text-amber-300 font-mono">{{ \App\Helpers\FormatHelper::short($turn['baseDamage']) }} (+{{ \App\Helpers\FormatHelper::short($turn['bonusDamage']) }})</strong>
        @elseif(($turn['actor'] ?? '') == 'enemy' && isset($turn['resistDamage']) && $turn['resistDamage'] > 0)
            <strong class="text-amber-300 font-mono">{{ \App\Helpers\FormatHelper::short($turn['baseDamage']) }} (-{{ \App\Helpers\FormatHelper::short($turn['resistDamage']) }})</strong>
        @else
            <strong class="text-amber-300 font-mono">{{ \App\Helpers\FormatHelper::short($turn['value'] ?? 0) }}</strong>
        @endif
        @if ($isMagicHit) <span class="inline-flex items-center gap-0.5 text-[10px] font-bold px-1 py-px rounded border {{ \App\Helpers\CombatLogHelper::badgeClasses('magic') }}"><i class="fa-solid fa-wand-sparkles"></i>MAGIA</span>@endif
        @if($skillName)<span class="text-indigo-300/90 text-xs font-bold ml-0.5">({{ $skillName }})</span>@endif
        @if($targetName) w <strong class="text-amber-200">{{ $targetName }}</strong>@endif
        @if (!empty($turn['dotDamage']))
            <span class="text-emerald-400 font-mono font-bold ml-1">(+{{ \App\Helpers\FormatHelper::short($turn['dotDamage']) }})</span>
        @endif
        obrażeń
        @if (!empty($turn['crit']))
            <span class="font-bold text-amber-400 drop-shadow-[0_0_10px_rgba(245,158,11,0.8)]">KRYTYK!</span>
        @endif
        @if(!empty($turn['ccType'])) <span class="inline-flex items-center gap-0.5 text-[10px] font-bold px-1 py-px rounded border {{ \App\Helpers\CombatLogHelper::badgeClasses($turn['ccType']) }}"><i class="fa-solid {{ \App\Helpers\CombatLogHelper::icon($turn['ccType']) }}"></i>{{ \App\Helpers\CombatLogHelper::label($turn['ccType']) }}</span>@endif
        @if ($pDotDmg > 0)
            <span class="{{ \App\Helpers\CombatLogHelper::color($pDotType) }} font-mono font-bold ml-1">(-{{ \App\Helpers\FormatHelper::short($pDotDmg) }} {{ \App\Helpers\CombatLogHelper::label($pDotType) }})</span>
        @endif
    </span>
@endif
