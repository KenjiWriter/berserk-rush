<div>
    <h3 class="text-xl font-bold text-amber-500 mb-4 border-b border-gray-700 pb-2">Deck Umiejętności</h3>
    
    <div class="mb-4">
        <p class="text-gray-400 text-sm mb-2">Możesz mieć aktywne maksymalnie 3 umiejętności jednocześnie. Będą one używane automatycznie w walce zamiast zwykłego ataku, gdy odnowi się ich czas (cooldown).</p>
        <p class="text-gray-400 text-sm mb-2">Pamiętaj, że niektóre umiejętności wymagają założenia odpowiedniego typu broni (np. łuk dla trującej strzały).</p>
    </div>

    @if($skills->isEmpty())
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 text-center">
            <p class="text-gray-400">Nie odblokowałeś jeszcze żadnych umiejętności.</p>
            <p class="text-gray-500 text-sm mt-2">Odwiedź Czarnoksiężnika w Mieście, aby nauczyć się nowych zdolności za Punkty Umiejętności.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4">
            @foreach($skills as $characterSkill)
                @php
                    $skill = $characterSkill->skill;
                    $isActive = $characterSkill->is_equipped;
                @endphp
                <div class="bg-gray-800 border {{ $isActive ? 'border-amber-500 shadow-[0_0_10px_rgba(245,158,11,0.2)]' : 'border-gray-700' }} rounded-lg p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 transition-all duration-300">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            @if($skill->icon)
                                <img src="{{ route('assets.skills.icons', ['filename' => $skill->icon]) }}" class="w-7 h-7 object-contain bg-gray-900 border border-gray-700 rounded p-0.5" alt="{{ $skill->name }}">
                            @endif
                            <h4 class="text-lg font-bold text-amber-400">{{ $skill->name }}</h4>
                            <span class="bg-gray-700 text-gray-300 px-2 py-0.5 rounded text-xs">Poziom {{ $characterSkill->level }}</span>
                            @if($skill->weapon_requirement)
                                <span class="bg-slate-700 border border-slate-600 text-slate-300 px-2 py-0.5 rounded text-xs" title="Wymaga broni typu: {{ ucfirst($skill->weapon_requirement) }}">Wymaga: {{ ucfirst($skill->weapon_requirement) }}</span>
                            @endif
                        </div>
                        <p class="text-gray-300 text-sm mb-2">{{ $skill->description }}</p>
                        
                        <div class="flex flex-wrap gap-2 text-xs">
                            <span class="bg-indigo-900/50 text-indigo-300 px-2 py-1 rounded">Czas odnowienia: {{ $skill->base_cooldown }} tur</span>
                            @if($skill->base_duration > 0)
                                <span class="bg-purple-900/50 text-purple-300 px-2 py-1 rounded">Czas trwania: {{ $skill->base_duration }} tur</span>
                            @endif
                            @if($skill->type === 'dot_poison')
                                <span class="bg-green-900/50 text-green-400 px-2 py-1 rounded">Trucizna: {{ $skill->base_value + ($characterSkill->level * $skill->scaling_value) }}% aktualnego HP</span>
                            @elseif($skill->type === 'dot_fire')
                                <span class="bg-red-900/50 text-red-400 px-2 py-1 rounded">Ogień: {{ $skill->base_value + ($characterSkill->level * $skill->scaling_value) }}% max HP</span>
                            @elseif($skill->type === 'buff_damage')
                                <span class="bg-blue-900/50 text-blue-400 px-2 py-1 rounded">Wzmocnienie: +{{ $skill->base_value + ($characterSkill->level * $skill->scaling_value) }}% obrażeń fizycznych</span>
                            @endif
                        </div>
                    </div>
                    
                    <button wire:click="equipSkill('{{ $characterSkill->id }}')" 
                            class="px-4 py-2 rounded font-bold transition-colors w-full sm:w-auto {{ $isActive ? 'bg-red-900/80 hover:bg-red-800 text-red-200 border border-red-700' : 'bg-green-900/80 hover:bg-green-800 text-green-200 border border-green-700' }}">
                        {{ $isActive ? 'Wyposażono' : 'Wyposaż' }}
                    </button>
                </div>
            @endforeach
        </div>
    @endif
</div>
