@props(['level' => 0, 'type' => null])

@php
    // Only show overlay for upgradeable item types
    $showOverlay = in_array($type ?? '', ['weapon', 'armor', 'accessory']);
    $upgradeLevel = (int) ($level ?? 0);
@endphp

@if($showOverlay)
    {{-- Upgrade bar overlay: 9 squares, filled up to upgrade level --}}
    <div class="absolute bottom-0 left-0 right-0 flex justify-center gap-[1.5px] px-[3px] pb-[3px] pointer-events-none z-10">
        @for($i = 1; $i <= 9; $i++)
            @php
                $filled = $i <= $upgradeLevel;
                // Color progression: 0-3 bronze, 4-6 silver/blue, 7-9 gold
                if ($i <= 3) {
                    $filledColor = 'bg-amber-600 shadow-[0_0_3px_rgba(180,83,9,0.8)]';
                } elseif ($i <= 6) {
                    $filledColor = 'bg-sky-400 shadow-[0_0_3px_rgba(56,189,248,0.8)]';
                } else {
                    $filledColor = 'bg-yellow-300 shadow-[0_0_4px_rgba(253,224,71,0.9)]';
                }
                $emptyColor = 'bg-stone-800/90 border border-stone-600/60';
            @endphp
            <div class="flex-1 h-[4px] rounded-[1px] transition-all duration-200 {{ $filled ? $filledColor : $emptyColor }}"></div>
        @endfor
    </div>

    {{-- +Level badge in bottom-right corner --}}
    @if($upgradeLevel > 0)
        <div class="absolute top-0 right-0 pointer-events-none z-10">
            <span class="
                text-[8px] font-extrabold leading-none px-[3px] py-[2px] rounded-bl-[4px] rounded-tr-[inherit]
                @if($upgradeLevel <= 3)
                    bg-amber-900/90 text-amber-300 border-b border-l border-amber-600/50
                @elseif($upgradeLevel <= 6)
                    bg-sky-900/90 text-sky-300 border-b border-l border-sky-500/50
                @else
                    bg-yellow-900/90 text-yellow-200 border-b border-l border-yellow-400/60 shadow-[0_0_6px_rgba(253,224,71,0.4)]
                @endif
            ">+{{ $upgradeLevel }}</span>
        </div>
    @endif
@endif
