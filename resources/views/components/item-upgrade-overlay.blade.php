@props(['level' => 0, 'type' => null])

@php
    $showOverlay = in_array($type ?? '', ['weapon', 'armor', 'accessory']);
    $upgradeLevel = (int) ($level ?? 0);
@endphp

@if($showOverlay)
    {{-- Upgrade squares bar - always visible at bottom of icon --}}
    <div class="absolute bottom-0 left-0 right-0 flex items-end justify-center gap-[1px] px-[2px] pb-[2px] pointer-events-none z-10">
        @for($i = 1; $i <= 9; $i++)
            @php $filled = $i <= $upgradeLevel; @endphp
            @if($filled)
                @php
                    // Bronze for 1-3, Blue for 4-6, Gold for 7-9
                    if ($i <= 3) {
                        $gradient = 'linear-gradient(180deg, #fbbf24 0%, #b45309 40%, #78350f 100%)';
                        $border = '#92400e';
                        $glow = 'rgba(180,83,9,0.7)';
                    } elseif ($i <= 6) {
                        $gradient = 'linear-gradient(180deg, #7dd3fc 0%, #0284c7 40%, #0c4a6e 100%)';
                        $border = '#0369a1';
                        $glow = 'rgba(14,165,233,0.7)';
                    } else {
                        $gradient = 'linear-gradient(180deg, #fef08a 0%, #eab308 40%, #713f12 100%)';
                        $border = '#ca8a04';
                        $glow = 'rgba(234,179,8,0.9)';
                    }
                @endphp
                <div class="flex-1 rounded-[1px]"
                     style="height: 6px; background: {{ $gradient }}; border: 0.5px solid {{ $border }}; box-shadow: 0 0 4px {{ $glow }}, inset 0 1px 0 rgba(255,255,255,0.35);"></div>
            @else
                <div class="flex-1 rounded-[1px]"
                     style="height: 6px; background: linear-gradient(180deg, #44403c 0%, #1c1917 100%); border: 0.5px solid #292524; box-shadow: inset 0 1px 0 rgba(0,0,0,0.5);"></div>
            @endif
        @endfor
    </div>

    {{-- +Level badge - top right corner, always visible --}}
    @if($upgradeLevel > 0)
        <div class="absolute top-0 right-0 pointer-events-none z-10">
            @php
                if ($upgradeLevel <= 3) {
                    $bg = 'linear-gradient(135deg, #92400e, #78350f)';
                    $color = '#fcd34d';
                    $borderColor = '#b45309';
                    $shadow = 'rgba(180,83,9,0.6)';
                } elseif ($upgradeLevel <= 6) {
                    $bg = 'linear-gradient(135deg, #0c4a6e, #075985)';
                    $color = '#7dd3fc';
                    $borderColor = '#0369a1';
                    $shadow = 'rgba(14,165,233,0.6)';
                } else {
                    $bg = 'linear-gradient(135deg, #78350f, #713f12)';
                    $color = '#fef08a';
                    $borderColor = '#ca8a04';
                    $shadow = 'rgba(234,179,8,0.8)';
                }
            @endphp
            <span style="
                display: inline-block;
                font-size: 8px;
                font-weight: 900;
                line-height: 1;
                padding: 2px 3px;
                border-radius: 0 inherit 0 4px;
                background: {{ $bg }};
                color: {{ $color }};
                border-bottom: 1px solid {{ $borderColor }};
                border-left: 1px solid {{ $borderColor }};
                box-shadow: 0 0 6px {{ $shadow }}, inset 0 1px 0 rgba(255,255,255,0.15);
                text-shadow: 0 0 4px {{ $shadow }};
            ">+{{ $upgradeLevel }}</span>
        </div>
    @endif
@endif
