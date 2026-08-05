@props([
    'effects' => [],
    'align' => 'left',
])

{{--
    Pasek aktywnych efektów statusowych nad paskiem HP (Faza 3 rebalansu, 2026-08-05).
    `$effects` to [typ => pozostałe_tury] (patrz MapStub::getPlayerStatusEffects()).
    Wspólny dla map-stub / dungeon-run / arena-combat. Etykiety/ikony/kolory z
    App\Helpers\CombatLogHelper. Pusta tablica = nic nie renderuje.
--}}
@if (!empty($effects))
    <div class="flex flex-wrap gap-1 {{ $align === 'right' ? 'justify-end' : '' }} mb-1">
        @foreach ($effects as $key => $turns)
            <span class="inline-flex items-center gap-1 text-[10px] leading-none font-bold px-1.5 py-0.5 rounded-full border {{ \App\Helpers\CombatLogHelper::badgeClasses($key) }} shadow-sm"
                  title="{{ \App\Helpers\CombatLogHelper::label($key) }} - pozostało tur: {{ $turns }}">
                <i class="fa-solid {{ \App\Helpers\CombatLogHelper::icon($key) }}"></i>
                <span class="hidden sm:inline">{{ \App\Helpers\CombatLogHelper::label($key) }}</span>
                <span class="font-mono opacity-90">{{ $turns }}t</span>
            </span>
        @endforeach
    </div>
@endif
