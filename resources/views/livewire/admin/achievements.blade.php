<div class="min-h-screen bg-gray-900 text-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-amber-500">🏆 Zarządzanie Osiągnięciami</h1>
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
                <h2 class="text-xl font-bold mb-4 border-b border-gray-700 pb-2 text-white">
                    {{ $isEditing ? 'Edytuj Osiągnięcie' : 'Dodaj Nowe Osiągnięcie' }}
                </h2>

                <form wire:submit.prevent="save">
                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Nazwa</label>
                        <input type="text" wire:model="name" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500" required>
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Opis</label>
                        <textarea wire:model="description" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500" rows="3"></textarea>
                        @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-2">Typ / Event</label>
                            <select wire:model="type" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500" required>
                                <option value="">-- Wybierz Typ --</option>
                                @foreach($achievementTypes as $key => $label)
                                    <option value="{{ $key }}">{{ $label }} ({{ $key }})</option>
                                @endforeach
                                @if(!array_key_exists($type, $achievementTypes) && $type)
                                    <option value="{{ $type }}">{{ $type }}</option>
                                @endif
                            </select>
                            <div class="mt-2">
                                <input type="text" wire:model.defer="type" placeholder="Niestandardowy typ..." class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white text-sm focus:outline-none focus:border-amber-500">
                            </div>
                            @error('type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-2">Cel (Ilość)</label>
                            <input type="number" wire:model="target_value" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500" min="1" required>
                            @error('target_value') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <h3 class="text-lg font-bold mt-6 mb-3 text-amber-500 border-b border-gray-700 pb-2">Nagrody</h3>

                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Punkty Osiągnięć</label>
                        <input type="number" wire:model="reward_points" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500" min="0" required>
                        @error('reward_points') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-2">Przedmiot</label>
                            <select wire:model="reward_item_template_id" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                                <option value="">-- Brak --</option>
                                @foreach($itemTemplates as $template)
                                    <option value="{{ $template->id }}">{{ $template->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-2">Tytuł</label>
                            <select wire:model="reward_title_id" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                                <option value="">-- Brak --</option>
                                @foreach($titles as $title)
                                    <option value="{{ $title->id }}">{{ $title->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-2">Złoto</label>
                            <input type="number" wire:model="reward_gold" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500" min="0" required>
                            @error('reward_gold') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-2">Doświadczenie</label>
                            <input type="number" wire:model="reward_exp" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500" min="0" required>
                            @error('reward_exp') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <h3 class="text-lg font-bold mt-6 mb-3 text-amber-500 border-b border-gray-700 pb-2">Tiers i Pasywne Bonusy</h3>
                    
                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Rodzic (Wymagane - Tier I)</label>
                        <select wire:model="parent_achievement_id" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                            <option value="">-- Brak Rodzica --</option>
                            @foreach($achievements as $ach)
                                @if($ach->id !== $achievementId)
                                    <option value="{{ $ach->id }}">{{ $ach->name }} ({{ $ach->type }})</option>
                                @endif
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Ustaw to, aby osiągnięcie było kontynuacją innego.</p>
                    </div>

                    @if($type === 'monsters_killed')
                        <h3 class="text-lg font-bold mt-6 mb-3 text-amber-500 border-b border-gray-700 pb-2">Warunki (Opcjonalne)</h3>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-gray-400 text-sm font-bold mb-2">Mapa</label>
                                <select wire:model="conditions.map_id" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                                    <option value="">-- Dowolna Mapa --</option>
                                    @foreach($maps as $map)
                                        <option value="{{ $map->id }}">{{ $map->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-400 text-sm font-bold mb-2">Konkretny Potwór</label>
                                <select wire:model="conditions.monster_id" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                                    <option value="">-- Dowolny Potwór --</option>
                                    @foreach($monsters as $monster)
                                        <option value="{{ $monster->id }}">{{ $monster->name }} (Lvl {{ $monster->level }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-gray-400 text-sm font-bold mb-2">Typ Potwora</label>
                                <select wire:model="conditions.monster_type" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                                    <option value="">-- Dowolny Typ --</option>
                                    @foreach(\App\Domain\Combat\Enums\MonsterType::cases() as $type)
                                        <option value="{{ $type->value }}">{{ $type->label() }} ({{ $type->value }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-400 text-sm font-bold mb-2">Ranga Potwora</label>
                                <select wire:model="conditions.monster_rank" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                                    <option value="">-- Dowolna Ranga --</option>
                                    @foreach(\App\Domain\Combat\Enums\MonsterRank::cases() as $rank)
                                        <option value="{{ $rank->value }}">{{ $rank->label() }} ({{ $rank->value }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif

                    <div class="mb-6">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Statystyki Pasywne</label>
                        @foreach($stats_bonus as $index => $stat)
                            <div class="flex items-center gap-2 mb-2">
                                <input type="text" wire:model="stats_bonus.{{ $index }}.key" placeholder="Klucz (np. str, def, max_hp)" class="shadow appearance-none border border-gray-600 rounded w-1/2 py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500" required>
                                <input type="number" wire:model="stats_bonus.{{ $index }}.value" placeholder="Wartość" class="shadow appearance-none border border-gray-600 rounded w-1/3 py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500" required>
                                <button type="button" wire:click="removeStat({{ $index }})" class="text-red-400 hover:text-red-300 px-2 py-1 font-bold rounded">&times;</button>
                            </div>
                        @endforeach
                        <button type="button" wire:click="addStat" class="mt-2 bg-gray-700 hover:bg-gray-600 text-white text-sm font-bold py-1 px-3 rounded border border-gray-600 transition">
                            + Dodaj Statystykę
                        </button>
                    </div>

                    <div class="flex justify-between">
                        <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition">
                            Zapisz
                        </button>
                        @if($isEditing)
                            <button type="button" wire:click="create" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded transition">
                                Anuluj
                            </button>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Lista -->
            <div class="lg:col-span-2">
                <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-900 border-b border-gray-700">
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm w-1/3">Nazwa / Typ</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm text-center">Cel</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm w-1/3">Nagrody</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm text-right">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($achievements as $achievement)
                                <tr class="border-b border-gray-700 hover:bg-gray-700/50 transition">
                                    <td class="p-3 text-white">
                                        <div class="font-bold text-yellow-500">{{ $achievement->name }}</div>
                                        <div class="text-xs text-gray-400">{{ $achievement->type }}</div>
                                        @if($achievement->parent_achievement_id)
                                            <div class="text-xs text-blue-400 mt-1">↳ Zależne od: {{ $achievement->parentAchievement?->name ?? 'Nieznany' }}</div>
                                        @endif
                                    </td>
                                    <td class="p-3 text-center text-xl font-bold text-gray-300">
                                        {{ $achievement->target_value }}
                                    </td>
                                    <td class="p-3 text-xs text-gray-300">
                                        <ul class="list-disc pl-4">
                                            @if($achievement->reward_points > 0)
                                                <li><span class="text-yellow-400">Pkt:</span> {{ $achievement->reward_points }}</li>
                                            @endif
                                            @if($achievement->reward_gold > 0)
                                                <li><span class="text-yellow-500">Złoto:</span> {{ $achievement->reward_gold }}</li>
                                            @endif
                                            @if($achievement->reward_exp > 0)
                                                <li><span class="text-blue-400">Exp:</span> {{ $achievement->reward_exp }}</li>
                                            @endif
                                            @if($achievement->reward_title_id)
                                                <li><span class="text-purple-400">Tytuł:</span> {{ $achievement->title?->name }}</li>
                                            @endif
                                            @if($achievement->reward_item_template_id)
                                                <li><span class="text-green-400">Przedmiot:</span> {{ $achievement->itemTemplate?->name }}</li>
                                            @endif
                                        </ul>
                                    </td>
                                    <td class="p-3 text-right">
                                        <button wire:click="edit('{{ $achievement->id }}')" class="text-blue-400 hover:text-blue-300 mr-3">Edytuj</button>
                                        <button wire:click="delete('{{ $achievement->id }}')" class="text-red-400 hover:text-red-300" onclick="confirm('Na pewno usunąć?') || event.stopImmediatePropagation()">Usuń</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-4 text-center text-gray-500">Brak osiągnięć w bazie.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
