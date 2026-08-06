@props(['level' => 0, 'type' => null])

@php
    $showOverlay = in_array($type ?? '', ['weapon', 'armor', 'accessory']);
    $upgradeLevel = (int) ($level ?? 0);
@endphp

@if($showOverlay)
    {{--
        IMPORTANT: This component must be placed inside a container that has:
        - position: relative (so absolute children resolve correctly)
        - overflow: visible (default, but do NOT set overflow:hidden on parent)
        
        The overlay renders two absolutely-positioned children relative to the nearest
        positioned ancestor. Keep z-index low (2) so it stays within the item stacking context.
    --}}

    {{-- +Level badge: floating badge on top-right corner exactly like pet tile --}}
    @if($upgradeLevel > 0)
        @php
            if ($upgradeLevel <= 3) {
                $badgeClass = 'bg-amber-950/90 border border-amber-500/80 text-amber-300 shadow-[0_0_6px_rgba(245,158,11,0.5)]';
            } elseif ($upgradeLevel <= 6) {
                $badgeClass = 'bg-sky-950/90 border border-sky-400/80 text-sky-300 shadow-[0_0_6px_rgba(56,189,248,0.5)]';
            } else {
                $badgeClass = 'bg-amber-950/90 border border-yellow-400 text-yellow-300 shadow-[0_0_8px_rgba(234,179,8,0.7)]';
            }
        @endphp
        <span class="absolute -top-1 -left-1 z-10 pointer-events-none text-[8px] sm:text-[9px] font-extrabold px-1 py-0.5 rounded-md shadow-lg leading-none {{ $badgeClass }}">
            +{{ $upgradeLevel }}
        </span>
    @endif
@endif
