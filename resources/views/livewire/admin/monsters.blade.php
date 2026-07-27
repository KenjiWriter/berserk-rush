<div class="min-h-screen bg-gray-900 text-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-amber-500">🧟 Zarządzanie Potworami</h1>
                <p class="text-gray-400 text-sm mt-1">Dodawaj, edytuj oraz masowo modyfikuj statystyki potworów.</p>
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

        <!-- Panel Masowej Zmiany Statystyk -->
        @if($showBulkModal)
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 border-2 border-amber-500/50 rounded-xl p-6 mb-8 shadow-2xl animate-fade-in relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 opacity-10 pointer-events-none text-amber-500 text-9xl font-black">
                    ⚡
                </div>
                
                <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-gray-700 pb-4 mb-6 gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-amber-400 flex items-center gap-2">
                            ⚡ Masowa Zmiana Statystyk Potworów (Wzmocnienie / Osłabienie)
                        </h2>
                        <p class="text-xs text-gray-400 mt-1">
                            • <strong>Wzmocnienie (Buff)</strong>: np. <code class="bg-gray-900 px-1.5 py-0.5 rounded text-green-400 font-bold">1.5</code> (+50%), <code class="bg-gray-900 px-1.5 py-0.5 rounded text-green-400 font-bold">5.0</code> (x5)<br>
                            • <strong>Osłabienie (Nerf)</strong>: np. <code class="bg-gray-900 px-1.5 py-0.5 rounded text-red-400 font-bold">0.8</code> (-20%), <code class="bg-gray-900 px-1.5 py-0.5 rounded text-red-400 font-bold">0.5</code> (-50%)<br>
                            • Wartość <code class="bg-gray-900 px-1.5 py-0.5 rounded text-gray-300">1.0</code> oznacza brak zmian.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-1.5 items-center">
                        <span class="text-xs text-gray-400 font-bold w-full sm:w-auto">Presety:</span>
                        <button type="button" wire:click="setBulkPreset(0.5)" class="text-xs bg-red-950/80 hover:bg-red-900 text-red-300 border border-red-700 py-1 px-2.5 rounded transition font-bold" title="Osłabienie o 50%">-50% (x0.5)</button>
                        <button type="button" wire:click="setBulkPreset(0.8)" class="text-xs bg-orange-950/80 hover:bg-orange-900 text-orange-300 border border-orange-700 py-1 px-2.5 rounded transition font-bold" title="Osłabienie o 20%">-20% (x0.8)</button>
                        <button type="button" wire:click="resetBulkMultipliers" class="text-xs bg-gray-700 hover:bg-gray-600 text-gray-200 py-1 px-2.5 rounded transition font-bold">Reset (x1.0)</button>
                        <button type="button" wire:click="setBulkPreset(1.5)" class="text-xs bg-emerald-950/80 hover:bg-emerald-900 text-emerald-300 border border-emerald-700 py-1 px-2.5 rounded transition font-bold" title="Wzmocnienie o 50%">+50% (x1.5)</button>
                        <button type="button" wire:click="setBulkPreset(3.0)" class="text-xs bg-green-950/80 hover:bg-green-900 text-green-300 border border-green-700 py-1 px-2.5 rounded transition font-bold" title="Wzmocnienie potrójne">x3 (x3.0)</button>
                    </div>
                </div>

                <!-- Filtry Docelowe -->
                <div class="mb-6 bg-gray-900/60 p-4 rounded-lg border border-gray-700/60">
                    <h3 class="text-sm font-bold text-gray-300 mb-3 flex items-center gap-2">
                        <span>🎯 Filtr potworów (opcjonalnie):</span>
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 mb-1">Mapa</label>
                            <select wire:model.live="bulkMapId" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-1.5 text-sm text-white focus:outline-none focus:border-amber-500">
                                <option value="">-- Wszystkie Mapy --</option>
                                @foreach($maps as $m)
                                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 mb-1">Ranga</label>
                            <select wire:model.live="bulkRank" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-1.5 text-sm text-white focus:outline-none focus:border-amber-500">
                                <option value="">-- Wszystkie Rangi --</option>
                                @foreach(\App\Domain\Combat\Enums\MonsterRank::cases() as $r)
                                    <option value="{{ $r->value }}">{{ $r->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 mb-1">Min Level</label>
                            <input type="number" wire:model.live="bulkMinLevel" placeholder="Brak" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-1.5 text-sm text-white focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 mb-1">Max Level</label>
                            <input type="number" wire:model.live="bulkMaxLevel" placeholder="Brak" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-1.5 text-sm text-white focus:outline-none focus:border-amber-500">
                        </div>
                    </div>
                </div>

                <!-- Mnożniki Statystyk -->
                <div class="mb-6">
                    <h3 class="text-sm font-bold text-gray-300 mb-3">✖️ Mnożniki Statystyk</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
                        <div class="bg-gray-900 p-2.5 rounded border border-gray-700">
                            <label class="block text-xs font-bold text-red-400 mb-1">HP *</label>
                            <input type="number" step="0.01" min="0" wire:model="bulkHpMult" class="w-full bg-gray-800 border border-gray-600 rounded py-1 px-2 text-white font-bold text-sm focus:border-amber-500">
                        </div>
                        <div class="bg-gray-900 p-2.5 rounded border border-gray-700">
                            <label class="block text-xs font-bold text-orange-400 mb-1">Atak *</label>
                            <input type="number" step="0.01" min="0" wire:model="bulkAtkMult" class="w-full bg-gray-800 border border-gray-600 rounded py-1 px-2 text-white font-bold text-sm focus:border-amber-500">
                        </div>
                        <div class="bg-gray-900 p-2.5 rounded border border-gray-700">
                            <label class="block text-xs font-bold text-blue-400 mb-1">Obrona *</label>
                            <input type="number" step="0.01" min="0" wire:model="bulkDefMult" class="w-full bg-gray-800 border border-gray-600 rounded py-1 px-2 text-white font-bold text-sm focus:border-amber-500">
                        </div>
                        <div class="bg-gray-900 p-2.5 rounded border border-gray-700">
                            <label class="block text-xs font-bold text-green-400 mb-1">Zręczność *</label>
                            <input type="number" step="0.01" min="0" wire:model="bulkAgiMult" class="w-full bg-gray-800 border border-gray-600 rounded py-1 px-2 text-white font-bold text-sm focus:border-amber-500">
                        </div>
                        <div class="bg-gray-900 p-2.5 rounded border border-gray-700">
                            <label class="block text-xs font-bold text-purple-400 mb-1">Inteligencja *</label>
                            <input type="number" step="0.01" min="0" wire:model="bulkIntMult" class="w-full bg-gray-800 border border-gray-600 rounded py-1 px-2 text-white font-bold text-sm focus:border-amber-500">
                        </div>
                        <div class="bg-gray-900 p-2.5 rounded border border-gray-700">
                            <label class="block text-xs font-bold text-yellow-400 mb-1">Crit % *</label>
                            <input type="number" step="0.01" min="0" wire:model="bulkCritMult" class="w-full bg-gray-800 border border-gray-600 rounded py-1 px-2 text-white font-bold text-sm focus:border-amber-500">
                        </div>
                        <div class="bg-gray-900 p-2.5 rounded border border-gray-700">
                            <label class="block text-xs font-bold text-teal-400 mb-1">Unik % *</label>
                            <input type="number" step="0.01" min="0" wire:model="bulkDodgeMult" class="w-full bg-gray-800 border border-gray-600 rounded py-1 px-2 text-white font-bold text-sm focus:border-amber-500">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" 
                        wire:click="applyBulkStatChanges" 
                        onclick="return confirm('Czy na pewno chcesz przeliczyć statystyki dla wybranych potworów? Akcja zmieni dane w bazie!')"
                        class="bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-white font-bold py-2.5 px-6 rounded-lg shadow-lg transition flex items-center gap-2 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Zastosuj Masową Zmianę
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
                    {{ $editingId ? 'Edytuj Potwora' : 'Dodaj Nowego Potwora' }}
                </h2>

                <form wire:submit.prevent="save">
                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Mapa</label>
                        <select wire:model="map_id" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                            <option value="">-- Wybierz Mapę --</option>
                            @foreach($maps as $map)
                                <option value="{{ $map->id }}">{{ $map->name }} (Lv. {{ $map->level_min }}-{{ $map->level_max }})</option>
                            @endforeach
                        </select>
                        @error('map_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Nazwa</label>
                        <input type="text" wire:model="name" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500">
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex gap-4 mb-4">
                        <div class="w-1/3">
                            <label class="block text-gray-400 text-sm font-bold mb-2">Level</label>
                            <input type="number" wire:model="level" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500">
                            @error('level') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="w-1/3">
                            <label class="block text-gray-400 text-sm font-bold mb-2">Typ</label>
                            <select wire:model="type" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                                <option value="">-- Wybierz Typ --</option>
                                @foreach(\App\Domain\Combat\Enums\MonsterType::cases() as $t)
                                    <option value="{{ $t->value }}">{{ $t->label() }}</option>
                                @endforeach
                            </select>
                            @error('type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="w-1/3">
                            <label class="block text-gray-400 text-sm font-bold mb-2">Ranga</label>
                            <select wire:model="rank" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                                @foreach(\App\Domain\Combat\Enums\MonsterRank::cases() as $r)
                                    <option value="{{ $r->value }}">{{ $r->label() }} ({{ $r->value }})</option>
                                @endforeach
                            </select>
                            @error('rank') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-4 bg-gray-900/50 p-3 rounded border border-gray-700">
                        <div>
                            <label class="block text-gray-400 text-xs font-bold mb-1">HP</label>
                            <input type="number" wire:model.live="hp" class="shadow border border-gray-600 rounded w-full py-1.5 px-2 bg-gray-700 text-white text-sm focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-gray-400 text-xs font-bold mb-1">Atak</label>
                            <input type="number" wire:model.live="atk" class="shadow border border-gray-600 rounded w-full py-1.5 px-2 bg-gray-700 text-white text-sm focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-gray-400 text-xs font-bold mb-1">Obrona</label>
                            <input type="number" wire:model.live="def" class="shadow border border-gray-600 rounded w-full py-1.5 px-2 bg-gray-700 text-white text-sm focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-gray-400 text-xs font-bold mb-1">Zręczność (AGI)</label>
                            <input type="number" wire:model.live="agi" class="shadow border border-gray-600 rounded w-full py-1.5 px-2 bg-gray-700 text-white text-sm focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-gray-400 text-xs font-bold mb-1">Inteligencja (INT)</label>
                            <input type="number" wire:model.live="int" class="shadow border border-gray-600 rounded w-full py-1.5 px-2 bg-gray-700 text-white text-sm focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-gray-400 text-xs font-bold mb-1">Crit %</label>
                            <input type="number" step="0.01" wire:model.live="crit" class="shadow border border-gray-600 rounded w-full py-1.5 px-2 bg-gray-700 text-white text-sm focus:outline-none focus:border-amber-500">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-gray-400 text-xs font-bold mb-1">Unik (Dodge) %</label>
                            <input type="number" step="0.01" wire:model.live="dodge" class="shadow border border-gray-600 rounded w-full py-1.5 px-2 bg-gray-700 text-white text-sm focus:outline-none focus:border-amber-500">
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Avatar Potwora (Opcjonalnie)</label>
                        @if(empty($availableAvatars))
                            <p class="text-sm text-gray-500">Brak avatarów w katalogu <code>storage/app/assets/monsters/avatars/</code>. Dodaj tam pliki graficzne.</p>
                        @else
                            <div class="grid grid-cols-6 sm:grid-cols-8 gap-2 max-h-48 overflow-y-auto p-2 bg-gray-900 rounded border border-gray-700">
                                @foreach($availableAvatars as $availableAvatar)
                                    <div 
                                        wire:click="setAvatar('{{ $availableAvatar }}')" 
                                        class="relative cursor-pointer border-2 rounded p-1 transition-all flex flex-col items-center justify-center {{ $avatar === $availableAvatar ? 'border-amber-500 bg-amber-500/20' : 'border-transparent hover:border-gray-500 hover:bg-gray-800' }}"
                                        title="{{ $availableAvatar }}"
                                    >
                                        @if(in_array($availableAvatar, $usedAvatars))
                                            <div class="absolute -top-1 -right-1 bg-red-600 text-white text-[9px] px-1 py-0.5 rounded shadow border border-gray-900 pointer-events-none" title="Avatar w użyciu">Używany</div>
                                        @endif
                                        <img src="{{ asset('assets/monsters/avatars/' . $availableAvatar) }}?v={{ @filemtime(storage_path('app/assets/monsters/avatars/' . $availableAvatar)) }}" alt="{{ $availableAvatar }}" class="w-10 h-10 object-contain drop-shadow-md" />
                                    </div>
                                @endforeach
                            </div>
                            @error('avatar') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        @endif
                        @if($avatar)
                            <div class="mt-2 text-xs text-amber-500 font-semibold flex items-center gap-2">
                                Wybrano: {{ $avatar }}
                                <button type="button" wire:click="$set('avatar', null)" class="text-red-400 hover:text-red-300 ml-2">Usuń</button>
                            </div>
                        @endif
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Tabela Łupów (Opcjonalnie)</label>
                        <select wire:model="loot_table_id" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                            <option value="">-- Brak --</option>
                            @foreach($lootTables as $lt)
                                <option value="{{ $lt->id }}">{{ $lt->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-6 p-4 bg-gray-900 border border-amber-900 rounded-lg flex justify-between items-center">
                        <div>
                            <span class="text-gray-400 text-sm">Szacowana Moc Bojowa (CP) potwora:</span>
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
                            <button type="button" wire:click="$set('editingId', null); $reset(['map_id', 'name', 'level', 'type', 'rank', 'hp', 'atk', 'def', 'agi', 'int', 'crit', 'dodge', 'loot_table_id', 'avatar'])" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded transition">
                                Anuluj
                            </button>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Lista Potworów -->
            <div class="lg:col-span-2">
                <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-900 border-b border-gray-700">
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Nazwa</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Level</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Mapa</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Stats (HP/Atk/Def/Agi/Int)</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm text-center">CP ⚡</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm text-right">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($monsters as $monster)
                                <tr class="border-b border-gray-700 hover:bg-gray-700/50 transition cursor-pointer" wire:click="edit('{{ $monster->id }}')">
                                    <td class="p-3 text-white font-bold">
                                        <div class="flex items-center gap-3">
                                            @if($monster->avatar)
                                                <img src="{{ asset('assets/monsters/avatars/' . $monster->avatar) }}?v={{ $monster->updated_at?->timestamp ?? 1 }}-{{ $cacheBuster }}" class="w-10 h-10 object-contain drop-shadow-md bg-gray-800 rounded p-1" alt="avatar">
                                            @else
                                                <div class="w-10 h-10 bg-gray-700 rounded flex items-center justify-center text-xs text-gray-500">Brak</div>
                                            @endif
                                            <div>
                                                {{ $monster->name }}
                                                @if($monster->rank?->value === 'worldboss')
                                                    <span class="ml-2 text-xs bg-red-900 text-red-200 px-2 py-0.5 rounded border border-red-700">Worldboss</span>
                                                @elseif($monster->rank?->value === 'boss')
                                                    <span class="ml-2 text-xs bg-purple-900 text-purple-200 px-2 py-0.5 rounded border border-purple-700">Boss</span>
                                                @endif
                                                <div class="text-xs text-gray-500">Typ: {{ $monster->type?->label() ?? 'Nieznany' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-3 text-yellow-500 font-bold">{{ $monster->level }}</td>
                                    <td class="p-3 text-gray-300">{{ $monster->map?->name ?? 'Brak mapy' }}</td>
                                    <td class="p-3 text-gray-400 text-xs">
                                        <div class="font-medium text-gray-200">
                                            HP: {{ $monster->stats['hp'] ?? 0 }} | Atk: {{ $monster->stats['atk'] ?? 0 }} | Def: {{ $monster->stats['def'] ?? 0 }}
                                        </div>
                                        <div class="text-gray-400 text-[11px] mt-0.5">
                                            Agi: {{ $monster->stats['agi'] ?? 0 }} | Int: {{ $monster->stats['int'] ?? 0 }} | Crit: {{ $monster->stats['crit'] ?? 0 }}% | Unik: {{ $monster->stats['dodge'] ?? 0 }}%
                                        </div>
                                    </td>
                                    <td class="p-3 text-center text-amber-400 font-bold">
                                        {{ $this->calculateMonsterCP($monster->stats ?? []) }} ⚡
                                    </td>
                                    <td class="p-3 text-right">
                                        <button wire:click.stop="delete('{{ $monster->id }}')" class="text-red-400 hover:text-red-300" onclick="confirm('Na pewno usunąć?') || event.stopImmediatePropagation()">Usuń</button>
                                    </td>
                                </tr>
                            @endforeach
                            @if($monsters->isEmpty())
                                <tr>
                                    <td colspan="6" class="p-6 text-center text-gray-500">Brak potworów w bazie danych.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
