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

    {{-- Bottom bar: 9 upgrade squares --}}
    <div style="position:absolute;bottom:3px;left:4px;right:4px;display:flex;align-items:flex-end;gap:1.5px;pointer-events:none;z-index:2;">
        @for($i = 1; $i <= 9; $i++)
            @php
                $filled = $i <= $upgradeLevel;
                if ($filled) {
                    if ($i <= 3) {
                        $style = 'flex:1;height:5px;border-radius:1px;background:linear-gradient(180deg,#fbbf24 0%,#b45309 45%,#78350f 100%);border:0.5px solid #92400e;box-shadow:0 0 4px rgba(180,83,9,0.9),inset 0 1px 0 rgba(255,255,255,0.35);';
                    } elseif ($i <= 6) {
                        $style = 'flex:1;height:5px;border-radius:1px;background:linear-gradient(180deg,#7dd3fc 0%,#0284c7 45%,#0c4a6e 100%);border:0.5px solid #0369a1;box-shadow:0 0 4px rgba(14,165,233,0.9),inset 0 1px 0 rgba(255,255,255,0.3);';
                    } else {
                        $style = 'flex:1;height:5px;border-radius:1px;background:linear-gradient(180deg,#fef08a 0%,#eab308 45%,#713f12 100%);border:0.5px solid #ca8a04;box-shadow:0 0 6px rgba(234,179,8,1),inset 0 1px 0 rgba(255,255,255,0.4);';
                    }
                } else {
                    $style = 'flex:1;height:5px;border-radius:1px;background:linear-gradient(180deg,#57534e 0%,#292524 100%);border:0.5px solid #44403c;box-shadow:inset 0 1px 0 rgba(0,0,0,0.6);';
                }
            @endphp
            <div style="{{ $style }}"></div>
        @endfor
    </div>

    {{-- +Level badge: top-right of the item cell --}}
    @if($upgradeLevel > 0)
        @php
            if ($upgradeLevel <= 3) {
                $badgeStyle = 'position:absolute;top:2px;right:2px;z-index:2;pointer-events:none;font-size:9px;font-weight:900;line-height:1;padding:1.5px 3.5px;border-radius:4px;background:linear-gradient(135deg,#92400e,#78350f);color:#fcd34d;border:1px solid #b45309;box-shadow:0 0 5px rgba(180,83,9,0.7),inset 0 1px 0 rgba(255,255,255,0.15);text-shadow:0 0 4px rgba(180,83,9,0.9);';
            } elseif ($upgradeLevel <= 6) {
                $badgeStyle = 'position:absolute;top:2px;right:2px;z-index:2;pointer-events:none;font-size:9px;font-weight:900;line-height:1;padding:1.5px 3.5px;border-radius:4px;background:linear-gradient(135deg,#0c4a6e,#075985);color:#7dd3fc;border:1px solid #0369a1;box-shadow:0 0 5px rgba(14,165,233,0.7),inset 0 1px 0 rgba(255,255,255,0.15);text-shadow:0 0 4px rgba(14,165,233,0.9);';
            } else {
                $badgeStyle = 'position:absolute;top:2px;right:2px;z-index:2;pointer-events:none;font-size:9px;font-weight:900;line-height:1;padding:1.5px 3.5px;border-radius:4px;background:linear-gradient(135deg,#78350f,#451a03);color:#fef08a;border:1px solid #ca8a04;box-shadow:0 0 7px rgba(234,179,8,0.9),inset 0 1px 0 rgba(255,255,255,0.2);text-shadow:0 0 6px rgba(234,179,8,1);';
            }
        @endphp
        <span style="{{ $badgeStyle }}">+{{ $upgradeLevel }}</span>
    @endif
@endif
