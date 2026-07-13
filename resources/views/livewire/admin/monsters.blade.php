<div class="min-h-screen bg-gray-900 text-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-amber-500">🧟 Zarządzanie Potworami</h1>
            <a href="{{ route('admin.dashboard') }}" class="text-gray-400 hover:text-white underline">&larr; Powrót do panelu</a>
        </div>

        @if (session()->has('message'))
            <div class="bg-green-600 text-white p-3 rounded mb-4 shadow">
                {{ session('message') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Formularz -->
            <div class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 h-fit">
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

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-2">HP</label>
                            <input type="number" wire:model.live="hp" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-2">Atak</label>
                            <input type="number" wire:model.live="atk" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-2">Obrona</label>
                            <input type="number" wire:model.live="def" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-2">Crit %</label>
                            <input type="number" step="0.01" wire:model.live="crit" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
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
                                        wire:click="$set('avatar', '{{ $availableAvatar }}')" 
                                        class="cursor-pointer border-2 rounded p-1 transition-all flex flex-col items-center justify-center {{ $avatar === $availableAvatar ? 'border-amber-500 bg-amber-500/20' : 'border-transparent hover:border-gray-500 hover:bg-gray-800' }}"
                                        title="{{ $availableAvatar }}"
                                    >
                                        <img src="{{ route('assets.monsters.avatars', ['filename' => $availableAvatar]) }}" alt="{{ $availableAvatar }}" class="w-10 h-10 object-contain drop-shadow-md" />
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
                            <button type="button" wire:click="$set('editingId', null); $reset(['map_id', 'name', 'level', 'type', 'rank', 'hp', 'atk', 'def', 'crit', 'loot_table_id', 'avatar'])" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded transition">
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
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Stats (HP/Atk/Def/Crit)</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm text-center">CP ⚡</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm text-right">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($monsters as $monster)
                                <tr class="border-b border-gray-700 hover:bg-gray-700/50 transition">
                                    <td class="p-3 text-white font-bold">
                                        <div class="flex items-center gap-3">
                                            @if($monster->avatar)
                                                <img src="{{ route('assets.monsters.avatars', ['filename' => $monster->avatar]) }}" class="w-10 h-10 object-contain drop-shadow-md bg-gray-800 rounded p-1" alt="avatar">
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
                                    <td class="p-3 text-yellow-500">{{ $monster->level }}</td>
                                    <td class="p-3 text-gray-300">{{ $monster->map?->name ?? 'Brak mapy' }}</td>
                                    <td class="p-3 text-gray-400 text-xs">
                                        {{ $monster->stats['hp'] ?? 0 }} / {{ $monster->stats['atk'] ?? 0 }} / {{ $monster->stats['def'] ?? 0 }} / {{ $monster->stats['crit'] ?? 0 }}%
                                    </td>
                                    <td class="p-3 text-center text-amber-400 font-bold">
                                        {{ $this->calculateMonsterCP($monster->stats ?? []) }} ⚡
                                    </td>
                                    <td class="p-3 text-right">
                                        <button wire:click="edit('{{ $monster->id }}')" class="text-blue-400 hover:text-blue-300 mr-3">Edytuj</button>
                                        <button wire:click="delete('{{ $monster->id }}')" class="text-red-400 hover:text-red-300" onclick="confirm('Na pewno usunąć?') || event.stopImmediatePropagation()">Usuń</button>
                                    </td>
                                </tr>
                            @endforeach
                            @if($monsters->isEmpty())
                                <tr>
                                    <td colspan="5" class="p-6 text-center text-gray-500">Brak potworów w bazie danych.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
