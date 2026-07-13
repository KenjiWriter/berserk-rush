<div class="space-y-6">
    <!-- Active Title Section -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 shadow-sm">
        <h3 class="text-xl font-bold text-amber-500 mb-4 flex items-center gap-2">
            <span>👑</span> Aktywny Tytuł
        </h3>
        <div class="flex flex-col gap-2">
            <select wire:change="selectTitle($event.target.value)" class="bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-amber-500 focus:border-amber-500 block w-full p-2.5">
                <option value="">-- Brak Tytułu --</option>
                @foreach($titles as $charTitle)
                    <option value="{{ $charTitle->title->id }}" @if($character->active_title_id == $charTitle->title->id) selected @endif>
                        {{ $charTitle->title->name }} @if($charTitle->title->prefix) ({{ $charTitle->title->prefix }}) @endif
                    </option>
                @endforeach
            </select>
            
            @if($character->activeTitle)
                <div class="text-sm text-gray-400 mt-2 p-3 bg-gray-900 rounded border border-gray-700">
                    <span class="text-amber-500 font-bold mb-1 block">Bonusy Tytułu:</span>
                    <div class="flex flex-wrap gap-3">
                        @foreach($character->activeTitle->stats_bonus ?? [] as $stat => $val)
                            <span class="text-green-400 font-semibold bg-green-900/30 px-2 py-1 rounded border border-green-800/50">
                                {{ strtoupper(str_replace('bonus_vs_', 'Vs ', $stat)) }}: +{{ $val }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
