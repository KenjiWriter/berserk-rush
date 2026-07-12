<div class="min-h-screen bg-gray-900 text-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-amber-500">📜 Zarządzanie Questami</h1>
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
                    {{ $editingId ? 'Edytuj Questa' : 'Nowy Quest' }}
                </h2>
                <form wire:submit.prevent="save">
                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Nazwa</label>
                        <input type="text" wire:model="name" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500">
                        @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Opis</label>
                        <textarea wire:model="description" rows="3" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500"></textarea>
                        @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="flex gap-4 mb-4">
                        <div class="w-1/2">
                            <label class="block text-gray-400 text-sm font-bold mb-2">Typ</label>
                            <select wire:model.live="type" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                                @foreach($types as $enumType)
                                    <option value="{{ $enumType->value }}">{{ $enumType->value }}</option>
                                @endforeach
                            </select>
                            @error('type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="w-1/2">
                            <label class="block text-gray-400 text-sm font-bold mb-2" title="np. ID potwora / przedmiotu">Target ID</label>
                            
                            @if($type === 'hunting')
                                <div class="flex gap-2 mb-2">
                                    <label class="inline-flex items-center text-xs text-gray-300">
                                        <input type="radio" wire:model.live="hunting_type" value="monster" class="mr-1"> Konkretny potwór
                                    </label>
                                    <label class="inline-flex items-center text-xs text-gray-300">
                                        <input type="radio" wire:model.live="hunting_type" value="map" class="mr-1"> Cała mapa
                                    </label>
                                </div>
                                @if($hunting_type === 'monster')
                                    <select wire:model="target_id" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                                        <option value="">-- Wybierz Potwora --</option>
                                        @foreach($monsters as $m)
                                            <option value="{{ $m->id }}">{{ $m->name }} (Lvl {{ $m->level }})</option>
                                        @endforeach
                                    </select>
                                @else
                                    <select wire:model="target_id" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                                        <option value="">-- Wybierz Mapę --</option>
                                        @foreach($maps as $m)
                                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            @elseif($type === 'gathering')
                                <select wire:model="target_id" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                                    <option value="">-- Wybierz Przedmiot --</option>
                                    @foreach($items as $i)
                                        <option value="{{ $i->id }}">{{ $i->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" wire:model="target_id" placeholder="np. upgrade_item" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500">
                            @endif
                            @error('target_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Wymagana Ilość</label>
                        <input type="number" wire:model="target_amount" min="1" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500">
                        @error('target_amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="flex gap-4 mb-4">
                        <div class="w-1/2">
                            <label class="block text-gray-400 text-sm font-bold mb-2">Min Level</label>
                            <input type="number" wire:model="required_level" min="1" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500">
                            @error('required_level') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="w-1/2">
                            <label class="block text-gray-400 text-sm font-bold mb-2">Max Level</label>
                            <input type="number" wire:model="max_level" min="1" placeholder="Brak" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500">
                            @error('max_level') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    
                    <div class="flex gap-4 mb-4">
                        <div class="w-1/2">
                            <label class="block text-gray-400 text-sm font-bold mb-2">Nagroda: Gold</label>
                            <input type="number" wire:model="reward_gold" min="0" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500">
                            @error('reward_gold') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="w-1/2">
                            <label class="block text-gray-400 text-sm font-bold mb-2">Nagroda: EXP</label>
                            <input type="number" wire:model="reward_exp" min="0" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500">
                            @error('reward_exp') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    
                    <div class="mb-6 flex items-center">
                        <input type="checkbox" wire:model="is_active" id="is_active" class="rounded bg-gray-700 border-gray-600 text-amber-500 focus:ring-amber-500 mr-2">
                        <label for="is_active" class="text-gray-300 font-bold">Quest Aktywny</label>
                    </div>

                    <div class="flex justify-between">
                        <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition">
                            Zapisz
                        </button>
                        @if($editingId)
                            <button type="button" wire:click="resetForm" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded transition">
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
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Quest</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Typ / Cel</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Level</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm text-right">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($quests as $quest)
                                <tr class="border-b border-gray-700 hover:bg-gray-700/50 transition {{ $quest->is_active ? '' : 'opacity-50' }}">
                                    <td class="p-3 text-white">
                                        <div class="font-bold text-yellow-500">{{ $quest->name }}</div>
                                        <div class="text-xs text-gray-500 truncate w-48" title="{{ $quest->description }}">{{ $quest->description }}</div>
                                    </td>
                                    <td class="p-3 text-gray-300">
                                        <span class="text-amber-400">{{ $quest->type->value }}</span><br>
                                        <span class="text-xs text-gray-400">{{ $quest->target_id ?: '-' }} (x{{ $quest->target_amount }})</span>
                                    </td>
                                    <td class="p-3 text-gray-300">
                                        Lvl {{ $quest->required_level }}
                                        @if($quest->max_level)
                                            - {{ $quest->max_level }}
                                        @else
                                            +
                                        @endif
                                    </td>
                                    <td class="p-3 text-right">
                                        <button wire:click="edit('{{ $quest->id }}')" class="text-blue-400 hover:text-blue-300 mr-3">Edytuj</button>
                                        <button wire:click="delete('{{ $quest->id }}')" class="text-red-400 hover:text-red-300" onclick="confirm('Na pewno usunąć ten quest?') || event.stopImmediatePropagation()">Usuń</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
