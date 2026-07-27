<div class="min-h-screen bg-gray-900 text-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-amber-500">⚔️ Zarządzanie Przedmiotami</h1>
                <p class="text-gray-400 text-sm mt-1">Twórz, edytuj oraz masowo przeskalowuj statystyki przedmiotów.</p>
            </div>
            <div class="flex items-center gap-4">
                <button type="button" wire:click="$toggle('showBulkModal')" class="bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white font-bold py-2 px-4 rounded shadow-lg transition flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    {{ $showBulkModal ? 'Ukryj Masową Edycję' : '⚡ Masowa Zmiana Statystyk' }}
                </button>
                <a href="{{ route('admin.dashboard') }}" class="text-gray-400 hover:text-white underline">&larr; Powrót do panelu</a>
            </div>
        </div>

        @if (session()->has('message'))
            <div class="bg-green-600 text-white p-3 rounded mb-6 shadow flex justify-between items-center">
                <span>{{ session('message') }}</span>
                <button type="button" onclick="this.parentElement.remove()" class="text-white hover:text-gray-200 font-bold">&times;</button>
            </div>
        @endif

        <!-- Panel Masowej Zmiany Statystyk Przedmiotów -->
        @if($showBulkModal)
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 border-2 border-amber-500/50 rounded-xl p-6 mb-8 shadow-2xl animate-fade-in relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 opacity-10 pointer-events-none text-amber-500 text-9xl font-black">
                    ⚔️
                </div>
                
                <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-gray-700 pb-4 mb-6 gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-amber-400 flex items-center gap-2">
                            ⚡ Masowa Zmiana Statystyk Przedmiotów (Wzmocnienie / Osłabienie)
                        </h2>
                        <p class="text-xs text-gray-400 mt-1">
                            • <strong>Wzmocnienie (Buff)</strong>: np. <code class="bg-gray-900 px-1.5 py-0.5 rounded text-green-400 font-bold">1.5</code> (+50%), <code class="bg-gray-900 px-1.5 py-0.5 rounded text-green-400 font-bold">2.0</code> (x2)<br>
                            • <strong>Osłabienie (Nerf)</strong>: np. <code class="bg-gray-900 px-1.5 py-0.5 rounded text-red-400 font-bold">0.8</code> (-20%), <code class="bg-gray-900 px-1.5 py-0.5 rounded text-red-400 font-bold">0.5</code> (-50%)<br>
                            • Wartość <code class="bg-gray-900 px-1.5 py-0.5 rounded text-gray-300">1.0</code> oznacza brak zmian.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-1.5 items-center">
                        <span class="text-xs text-gray-400 font-bold w-full sm:w-auto">Presety:</span>
                        <button type="button" wire:click="setBulkPreset(0.5)" class="text-xs bg-red-950/80 hover:bg-red-900 text-red-300 border border-red-700 py-1 px-2.5 rounded transition font-bold" title="Osłabienie o 50%">-50% (x0.5)</button>
                        <button type="button" wire:click="setBulkPreset(0.8)" class="text-xs bg-orange-950/80 hover:bg-orange-900 text-orange-300 border border-orange-700 py-1 px-2.5 rounded transition font-bold" title="Osłabienie o 20%">-20% (x0.8)</button>
                        <button type="button" wire:click="resetBulkMultipliers" class="text-xs bg-gray-700 hover:bg-gray-600 text-gray-200 py-1 px-2.5 rounded transition font-bold">Reset (x1.0)</button>
                        <button type="button" wire:click="setBulkPreset(1.2)" class="text-xs bg-emerald-950/80 hover:bg-emerald-900 text-emerald-300 border border-emerald-700 py-1 px-2.5 rounded transition font-bold" title="Wzmocnienie o 20%">+20% (x1.2)</button>
                        <button type="button" wire:click="setBulkPreset(1.5)" class="text-xs bg-green-950/80 hover:bg-green-900 text-green-300 border border-green-700 py-1 px-2.5 rounded transition font-bold" title="Wzmocnienie o 50%">+50% (x1.5)</button>
                    </div>
                </div>

                <!-- Filtry Docelowe -->
                <div class="mb-6 bg-gray-900/60 p-4 rounded-lg border border-gray-700/60">
                    <h3 class="text-sm font-bold text-gray-300 mb-3 flex items-center gap-2">
                        <span>🎯 Filtr przedmiotów (opcjonalnie):</span>
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 mb-1">Typ Przedmiotu</label>
                            <select wire:model.live="bulkType" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-1.5 text-sm text-white focus:outline-none focus:border-amber-500">
                                <option value="">-- Wszystkie Typy --</option>
                                <option value="weapon">Broń</option>
                                <option value="armor">Pancerz</option>
                                <option value="accessory">Akcesorium</option>
                                <option value="consumable">Użytkowe</option>
                                <option value="material">Materiał</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 mb-1">Min Level Wymagany</label>
                            <input type="number" wire:model.live="bulkMinLevel" placeholder="Brak" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-1.5 text-sm text-white focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 mb-1">Max Level Wymagany</label>
                            <input type="number" wire:model.live="bulkMaxLevel" placeholder="Brak" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-1.5 text-sm text-white focus:outline-none focus:border-amber-500">
                        </div>
                    </div>
                </div>

                <!-- Mnożniki Statystyk -->
                <div class="mb-6">
                    <h3 class="text-sm font-bold text-gray-300 mb-3">✖️ Mnożniki Statystyk Przedmiotów</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
                        <div class="bg-gray-900 p-2.5 rounded border border-gray-700">
                            <label class="block text-xs font-bold text-orange-400 mb-1">Atak *</label>
                            <input type="number" step="0.01" min="0" wire:model="bulkAtkMult" class="w-full bg-gray-800 border border-gray-600 rounded py-1 px-2 text-white font-bold text-sm focus:border-amber-500">
                        </div>
                        <div class="bg-gray-900 p-2.5 rounded border border-gray-700">
                            <label class="block text-xs font-bold text-blue-400 mb-1">Obrona *</label>
                            <input type="number" step="0.01" min="0" wire:model="bulkDefMult" class="w-full bg-gray-800 border border-gray-600 rounded py-1 px-2 text-white font-bold text-sm focus:border-amber-500">
                        </div>
                        <div class="bg-gray-900 p-2.5 rounded border border-gray-700">
                            <label class="block text-xs font-bold text-red-400 mb-1">HP Bonus *</label>
                            <input type="number" step="0.01" min="0" wire:model="bulkHpMult" class="w-full bg-gray-800 border border-gray-600 rounded py-1 px-2 text-white font-bold text-sm focus:border-amber-500">
                        </div>
                        <div class="bg-gray-900 p-2.5 rounded border border-gray-700">
                            <label class="block text-xs font-bold text-cyan-400 mb-1">Mana Bonus *</label>
                            <input type="number" step="0.01" min="0" wire:model="bulkManaMult" class="w-full bg-gray-800 border border-gray-600 rounded py-1 px-2 text-white font-bold text-sm focus:border-amber-500">
                        </div>
                        <div class="bg-gray-900 p-2.5 rounded border border-gray-700">
                            <label class="block text-xs font-bold text-yellow-400 mb-1">Siła (STR) *</label>
                            <input type="number" step="0.01" min="0" wire:model="bulkStrMult" class="w-full bg-gray-800 border border-gray-600 rounded py-1 px-2 text-white font-bold text-sm focus:border-amber-500">
                        </div>
                        <div class="bg-gray-900 p-2.5 rounded border border-gray-700">
                            <label class="block text-xs font-bold text-green-400 mb-1">Zręczność (AGI) *</label>
                            <input type="number" step="0.01" min="0" wire:model="bulkAgiMult" class="w-full bg-gray-800 border border-gray-600 rounded py-1 px-2 text-white font-bold text-sm focus:border-amber-500">
                        </div>
                        <div class="bg-gray-900 p-2.5 rounded border border-gray-700">
                            <label class="block text-xs font-bold text-pink-400 mb-1">Witalność (VIT) *</label>
                            <input type="number" step="0.01" min="0" wire:model="bulkVitMult" class="w-full bg-gray-800 border border-gray-600 rounded py-1 px-2 text-white font-bold text-sm focus:border-amber-500">
                        </div>
                        <div class="bg-gray-900 p-2.5 rounded border border-gray-700">
                            <label class="block text-xs font-bold text-purple-400 mb-1">Inteligencja (INT) *</label>
                            <input type="number" step="0.01" min="0" wire:model="bulkIntMult" class="w-full bg-gray-800 border border-gray-600 rounded py-1 px-2 text-white font-bold text-sm focus:border-amber-500">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" 
                        wire:click="applyBulkStatChanges" 
                        onclick="return confirm('Czy na pewno chcesz przeliczyć statystyki dla wybranych przedmiotów? Akcja zmieni dane w bazie!')"
                        class="bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-white font-bold py-2.5 px-6 rounded-lg shadow-lg transition flex items-center gap-2 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Zastosuj Masową Zmianę Przedmiotów
                    </button>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            <!-- Formularz -->
            <div class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 h-fit lg:order-last lg:sticky lg:top-8 max-h-[calc(100vh-4rem)] overflow-y-auto relative">
                
                <!-- Loading Overlay -->
                <div wire:loading wire:target="edit" class="absolute inset-0 bg-gray-900/80 z-50 flex items-center justify-center rounded-lg backdrop-blur-sm">
                    <div class="text-amber-500 font-bold flex flex-col items-center">
                        <svg class="animate-spin h-10 w-10 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Wczytywanie danych...
                    </div>
                </div>

                <h2 class="text-xl font-bold mb-4 border-b border-gray-700 pb-2">
                    {{ $editingId ? 'Edytuj Przedmiot' : 'Dodaj Nowy Przedmiot' }}
                </h2>

                <form wire:submit.prevent="save">
                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Nazwa</label>
                        <input type="text" wire:model.live="name" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500">
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">ID (generowane z nazwy)</label>
                        <input type="text" wire:model="template_id" disabled class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-800 text-gray-500 leading-tight focus:outline-none">
                        @error('template_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex gap-4 mb-4">
                        <div class="w-1/2">
                            <label class="block text-gray-400 text-sm font-bold mb-2">Typ</label>
                            <select wire:model="type" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                                <option value="">-- Wybierz --</option>
                                <option value="weapon">Broń</option>
                                <option value="armor">Pancerz</option>
                                <option value="accessory">Akcesorium</option>
                                <option value="consumable">Użytkowe</option>
                                <option value="material">Materiał</option>
                                <option value="egg">Jajo Peta (Egg)</option>
                            </select>
                            @error('type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="w-1/2">
                            <label class="block text-gray-400 text-sm font-bold mb-2">Slot (Eq)</label>
                            <select wire:model="slot" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                                <option value="">-- Brak / Materiał --</option>
                                <option value="head">Głowa (head)</option>
                                <option value="chest">Zbroja (chest)</option>
                                <option value="feet">Buty (feet)</option>
                                <option value="main_hand">Broń (main_hand)</option>
                                <option value="off_hand">Tarcza (off_hand)</option>
                                <option value="neck">Naszyjnik (neck)</option>
                                <option value="ring">Pierścień (ring)</option>
                            </select>
                            @error('slot') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Wymagany Level</label>
                        <input type="number" wire:model="level_requirement" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500">
                        @error('level_requirement') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Opis (opcjonalnie)</label>
                        <textarea wire:model="description" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500" rows="3"></textarea>
                        @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Ikona Przedmiotu</label>
                        @if(empty($availableIcons))
                            <p class="text-sm text-gray-500">Brak ikon w katalogu <code>storage/app/assets/items/</code>. Dodaj tam pliki graficzne.</p>
                        @else
                            <div class="grid grid-cols-6 sm:grid-cols-8 gap-2 max-h-48 overflow-y-auto p-2 bg-gray-900 rounded border border-gray-700">
                                @foreach($availableIcons as $availableIcon)
                                    <div 
                                        wire:click="setIcon('{{ $availableIcon }}')" 
                                        class="relative cursor-pointer border-2 rounded p-1 transition-all flex flex-col items-center justify-center {{ $icon === $availableIcon ? 'border-amber-500 bg-amber-500/20' : 'border-transparent hover:border-gray-500 hover:bg-gray-800' }}"
                                        title="{{ $availableIcon }}"
                                    >
                                        @if(in_array($availableIcon, $usedIcons))
                                            <div class="absolute -top-1 -right-1 bg-red-600 text-white text-[9px] px-1 py-0.5 rounded shadow border border-gray-900 pointer-events-none whitespace-nowrap" title="Ikona w użyciu">W użyciu</div>
                                        @endif
                                        <img src="{{ asset('assets/items/' . $availableIcon) }}?v={{ @filemtime(storage_path('app/assets/items/' . $availableIcon)) }}" alt="{{ $availableIcon }}" class="w-10 h-10 object-contain drop-shadow-md" />
                                    </div>
                                @endforeach
                            </div>
                            @error('icon') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        @endif
                        @if($icon)
                            <div class="mt-2 text-xs text-amber-500 font-semibold flex items-center gap-2">
                                Wybrano: {{ $icon }}
                                <button type="button" wire:click="$set('icon', null)" class="text-red-400 hover:text-red-300 ml-2">Usuń</button>
                            </div>
                        @endif
                    </div>

                    @if($type === 'consumable')
                    <div class="mb-4 p-3 bg-blue-900/30 border border-blue-800 rounded">
                        <label class="block text-blue-400 text-sm font-bold mb-2">Czas trwania buffu (w minutach)</label>
                        <input type="number" wire:model="duration_minutes" class="shadow appearance-none border border-blue-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-blue-500">
                        <p class="text-xs text-gray-400 mt-1">Zdefiniuj przez ile minut będą aktywne wybrane poniżej statystyki po spożyciu mikstury.</p>
                        @error('duration_minutes') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    @endif

                    <div class="mb-6">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Statystyki bazowe (+0)</label>
                        <div class="grid grid-cols-2 gap-2 bg-gray-900 p-3 rounded border border-gray-700 max-h-64 overflow-y-auto">
                            @php
                                $availableStats = [
                                    'attack_min' => 'Atak Min',
                                    'attack_max' => 'Atak Max',
                                    'magic_attack_min' => 'Magiczny Atak Min',
                                    'magic_attack_max' => 'Magiczny Atak Max',
                                    'defense' => 'Obrona',
                                    'hp_bonus' => 'Bonus HP',
                                    'mana_bonus' => 'Bonus Mana',
                                    'str_bonus' => 'Siła (STR)',
                                    'agi_bonus' => 'Zręczność (AGI)',
                                    'int_bonus' => 'Inteligencja (INT)',
                                    'vit_bonus' => 'Witalność (VIT)',
                                    'crit_chance' => 'Szansa na cios kryt.',
                                ];
                            @endphp
                            @foreach($availableStats as $key => $label)
                                <div class="flex items-center space-x-2">
                                    <input type="checkbox" wire:model.live="selectedStats" value="{{ $key }}" id="stat_{{ $key }}" class="rounded bg-gray-700 border-gray-600 text-amber-500 focus:ring-amber-500">
                                    <label for="stat_{{ $key }}" class="text-xs text-gray-300 w-28 truncate" title="{{ $label }}">{{ $label }}</label>
                                    @if(in_array($key, $selectedStats))
                                        <input type="number" wire:model.live="statValues.{{ $key }}" placeholder="0" class="w-16 p-1 text-xs bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-amber-500">
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-6 p-4 bg-gray-900 border border-amber-900 rounded-lg flex justify-between items-center">
                        <div>
                            <span class="text-gray-400 text-sm">Szacowana Moc Bojowa (CP) tego przedmiotu:</span>
                        </div>
                        <div class="text-2xl font-bold text-amber-400">
                            {{ $previewCP }} ⚡
                        </div>
                    </div>

                    <div class="flex justify-between">
                        <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition">
                            Zapisz
                        </button>
                        @if($editingId)
                            <button type="button" wire:click="$set('editingId', null); $reset(['template_id', 'name', 'type', 'slot', 'level_requirement', 'selectedStats', 'statValues', 'previewCP'])" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded transition">
                                Anuluj
                            </button>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Lista -->
            <div class="lg:col-span-2">
                <!-- Filtrowanie -->
                <div class="bg-gray-800 p-4 rounded-lg shadow-lg border border-gray-700 mb-4 flex gap-4 items-center">
                    <div class="w-2/3">
                        <input type="text" wire:model.live="search" placeholder="Szukaj po nazwie lub ID..." class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500">
                    </div>
                    <div class="w-1/3">
                        <select wire:model.live="filterType" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                            <option value="">Wszystkie Typy</option>
                            <option value="weapon">Broń</option>
                            <option value="armor">Pancerz</option>
                            <option value="accessory">Akcesorium</option>
                            <option value="consumable">Użytkowe</option>
                            <option value="material">Materiał</option>
                            <option value="egg">Jajo Peta (Egg)</option>
                        </select>
                    </div>
                </div>

                <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-900 border-b border-gray-700">
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Item</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Typ / Slot</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Level Req</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm text-right">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($templates as $item)
                                <tr class="border-b border-gray-700 hover:bg-gray-700/50 transition cursor-pointer" wire:click="edit('{{ $item->id }}')">
                                    <td class="p-3 text-white">
                                        <div class="flex items-center gap-3">
                                            @if($item->icon)
                                                <img src="{{ asset('assets/items/' . $item->icon) }}?v={{ $item->updated_at?->timestamp ?? 1 }}-{{ $cacheBuster }}" class="w-10 h-10 object-contain drop-shadow-md bg-gray-800 rounded p-1" alt="icon">
                                            @else
                                                <div class="w-10 h-10 bg-gray-700 rounded flex items-center justify-center text-xs text-gray-500">Brak</div>
                                            @endif
                                            <div>
                                                <div class="font-bold text-yellow-500">{{ $item->name }}</div>
                                                <div class="text-xs text-gray-500">{{ $item->id }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-3 text-gray-300">
                                        {{ $item->type }}
                                        @if($item->slot)
                                            <br><span class="text-xs text-blue-400">[{{ $item->slot }}]</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-gray-300">{{ $item->level_requirement }}</td>
                                    <td class="p-3 text-right">
                                        <button wire:click.stop="delete('{{ $item->id }}')" class="text-red-400 hover:text-red-300" onclick="confirm('Na pewno usunąć?') || event.stopImmediatePropagation()">Usuń</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $templates->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
