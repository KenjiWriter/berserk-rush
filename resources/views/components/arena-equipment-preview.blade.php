@props([
    'slots' => [],
])

@php
    $slotOrder = ['head', 'chest', 'main_hand', 'neck', 'ring', 'feet'];
    $slotMeta = [
        'head' => ['label' => 'Głowa', 'icon' => 'fa-helmet-safety'],
        'chest' => ['label' => 'Klatka', 'icon' => 'fa-shield-halved'],
        'main_hand' => ['label' => 'Główna Ręka', 'icon' => 'fa-khanda'],
        'neck' => ['label' => 'Szyja', 'icon' => 'fa-gem'],
        'ring' => ['label' => 'Pierścień', 'icon' => 'fa-ring'],
        'feet' => ['label' => 'Stopy', 'icon' => 'fa-shoe-prints'],
    ];
@endphp

<div class="p-3 bg-gray-900 border-2 border-slate-600 rounded-xl shadow-2xl pointer-events-auto w-[240px] sm:w-[264px]" @click.stop>
    <p class="text-[11px] uppercase tracking-wider text-amber-400 font-bold mb-2 flex items-center gap-1.5 border-b border-slate-700 pb-1.5">
        <i class="fa-solid fa-shield-halved"></i> Ekwipunek Areny
    </p>
    <div class="grid grid-cols-3 gap-2">
        @foreach($slotOrder as $slot)
            @php $item = $slots[$slot] ?? null; @endphp
            <div x-data="{ 
                itemOpen: false,
                itemTimeout: null,
                openItem() {
                    clearTimeout(this.itemTimeout);
                    this.itemOpen = true;
                },
                closeItem(e) {
                    if (e && e.relatedTarget) {
                        if ($el && $el.contains(e.relatedTarget)) return;
                        if (e.relatedTarget.closest && e.relatedTarget.closest('[data-item-tooltip]')) return;
                        if (e.relatedTarget.closest && e.relatedTarget.closest('[data-tooltip-container]')) return;
                    }
                    clearTimeout(this.itemTimeout);
                    this.itemTimeout = setTimeout(() => {
                        this.itemOpen = false;
                    }, 500);
                }
            }" class="relative" @if($item) @mouseenter="openItem()" @mouseleave="closeItem($event)" @endif>
                <div class="w-full aspect-square bg-stone-900/90 border-2 {{ $item ? \App\Helpers\ItemHelper::getRarityBorderClass($item->rarity ?? 'common') : 'border-stone-700/70' }} rounded-lg flex items-center justify-center overflow-hidden transition-colors cursor-pointer relative {{ ($item && \App\Helpers\ItemHelper::isEnchanted($item)) ? 'enchanted-effect' : '' }}">
                    @if($item && \App\Helpers\ItemHelper::isEnchanted($item))
                        <div class="absolute top-0.5 left-0.5 z-10 text-[9px] text-fuchsia-300 drop-shadow-[0_0_4px_rgba(217,70,239,0.9)] enchanted-sparkle-icon pointer-events-none" title="Przedmiot zaczarowany"><i class="fa-solid fa-wand-sparkles"></i></div>
                    @endif
                    @if($item)
                        @if($item->template->icon ?? null)
                            <img src="{{ route('assets.items', ['filename' => $item->template->icon]) }}" class="w-full h-full object-contain p-1" alt="{{ $item->template->name }}">
                        @else
                            <span class="text-[9px] text-amber-200 font-semibold text-center px-0.5 truncate">{{ $item->template->name }}</span>
                        @endif
                    @else
                        <i class="fa-solid {{ $slotMeta[$slot]['icon'] }} text-stone-600 text-base"></i>
                    @endif
                </div>

                @if($item)
                    <div x-show="itemOpen" data-item-tooltip x-transition.opacity style="display:none" class="hidden sm:block absolute z-[100000] left-1/2 -translate-x-1/2 bottom-full pb-3 pointer-events-auto" @mouseenter="openItem()" @mouseleave="closeItem($event)">
                        <x-item-tooltip :item="$item" />
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
