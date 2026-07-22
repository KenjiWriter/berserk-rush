<div class="min-h-screen bg-gray-900 text-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-amber-500">⚔️ Zarządzanie Umiejętnościami</h1>
            <a href="{{ route('admin.dashboard') }}" class="text-gray-400 hover:text-white underline">&larr; Powrót do panelu</a>
        </div>

        @if (session()->has('message'))
            <div class="bg-green-600 text-white p-3 rounded mb-4 shadow">
                {{ session('message') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            <!-- Formularz -->
            <div class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 h-fit lg:order-last lg:sticky lg:top-8 max-h-[calc(100vh-4rem)] overflow-y-auto relative">
                
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
                    {{ $editingId ? 'Edytuj Umiejętność' : 'Dodaj Nową Umiejętność' }}
                </h2>

                <form wire:submit.prevent="save">
                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Nazwa</label>
                        <input type="text" wire:model="name" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500">
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Opis</label>
                        <textarea wire:model="description" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500 h-20"></textarea>
                        @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex gap-4 mb-4">
                        <div class="w-1/2">
                            <label class="block text-gray-400 text-sm font-bold mb-2">Typ</label>
                            <select wire:model="type" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                                <option value="active">Aktywna</option>
                                <option value="passive">Pasywna</option>
                            </select>
                            @error('type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="w-1/2">
                            <label class="block text-gray-400 text-sm font-bold mb-2">Wymagana Broń</label>
                            <select wire:model="required_weapon_type" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                                <option value="any">Dowolna</option>
                                <option value="sword">Miecz</option>
                                <option value="bow">Łuk</option>
                                <option value="staff">Kostur</option>
                            </select>
                            @error('required_weapon_type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Rodzaj Efektu</label>
                        <select wire:model="effect_type" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                            <option value="direct_dmg">Bezpośrednie Obrażenia</option>
                            <option value="poison">Trucizna (DoT % Current HP)</option>
                            <option value="fire">Podpalenie (DoT % Max HP)</option>
                            <option value="buff_phys_dmg">Wzmocnienie Obrażeń Fiz.</option>
                        </select>
                        @error('effect_type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-2">Base Value (Dmg/%/itp)</label>
                            <input type="number" wire:model="base_value" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-2">Scaling Value</label>
                            <input type="number" wire:model="scaling_value" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-2">Cooldown (tury)</label>
                            <input type="number" wire:model="base_cooldown" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-2">Czas trwania (tury)</label>
                            <input type="number" wire:model="base_duration" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-2">Wymagany Level</label>
                            <input type="number" wire:model="required_level" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-2">Koszt Odblokowania (SP)</label>
                            <input type="number" wire:model="unlock_cost" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Ikona Skilla (Opcjonalnie)</label>
                        @if(empty($availableIcons))
                            <p class="text-sm text-gray-500">Brak ikon w katalogu <code>storage/app/assets/skills/icons/</code>. Dodaj tam pliki graficzne.</p>
                        @else
                            <div class="grid grid-cols-6 sm:grid-cols-8 gap-2 max-h-48 overflow-y-auto p-2 bg-gray-900 rounded border border-gray-700">
                                @foreach($availableIcons as $availableIcon)
                                    <div 
                                        wire:click="setIcon('{{ $availableIcon }}')" 
                                        class="relative cursor-pointer border-2 rounded p-1 transition-all flex flex-col items-center justify-center {{ $icon === $availableIcon ? 'border-amber-500 bg-amber-500/20' : 'border-transparent hover:border-gray-500 hover:bg-gray-800' }}"
                                        title="{{ $availableIcon }}"
                                    >
                                        @if(in_array($availableIcon, $usedIcons))
                                            <div class="absolute -top-1 -right-1 bg-red-600 text-white text-[9px] px-1 py-0.5 rounded shadow border border-gray-900 pointer-events-none" title="Ikona w użyciu">Używany</div>
                                        @endif
                                        <img src="{{ asset('assets/skills/icons/' . $availableIcon) }}?v={{ @filemtime(storage_path('app/assets/skills/icons/' . $availableIcon)) }}" alt="{{ $availableIcon }}" class="w-10 h-10 object-contain drop-shadow-md" />
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

                    <div class="flex justify-between mt-6">
                        <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition">
                            Zapisz
                        </button>
                        @if($editingId)
                            <button type="button" wire:click="$set('editingId', null); $reset(['name', 'description', 'type', 'required_weapon_type', 'effect_type', 'base_cooldown', 'base_duration', 'base_value', 'scaling_value', 'required_level', 'unlock_cost', 'icon'])" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded transition">
                                Anuluj
                            </button>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Lista Umiejętności -->
            <div class="lg:col-span-2">
                <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-900 border-b border-gray-700">
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Nazwa</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Wymagania</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Efekt / Dmg</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm text-right">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($skills as $skill)
                                <tr class="border-b border-gray-700 hover:bg-gray-700/50 transition cursor-pointer" wire:click="edit('{{ $skill->id }}')">
                                    <td class="p-3 text-white font-bold">
                                        <div class="flex items-center gap-3">
                                            @if($skill->icon)
                                                <img src="{{ asset('assets/skills/icons/' . $skill->icon) }}?v={{ $skill->updated_at?->timestamp ?? 1 }}-{{ $cacheBuster }}" class="w-10 h-10 object-contain drop-shadow-md bg-gray-800 rounded p-1" alt="icon">
                                            @else
                                                <div class="w-10 h-10 bg-gray-700 rounded flex items-center justify-center text-xs text-gray-500">Brak</div>
                                            @endif
                                            <div>
                                                {{ $skill->name }}
                                                <div class="text-xs text-gray-500">{{ $skill->type == 'active' ? 'Aktywna' : 'Pasywna' }} | Koszt: {{ $skill->unlock_cost }} SP</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <div class="text-yellow-500">Lv. {{ $skill->required_level }}</div>
                                        <div class="text-xs text-gray-400">Broń: {{ $skill->required_weapon_type }}</div>
                                    </td>
                                    <td class="p-3">
                                        <div class="text-purple-400">{{ $skill->effect_type }}</div>
                                        <div class="text-xs text-gray-500">Wartość: {{ $skill->base_value }} (CD: {{ $skill->base_cooldown }}, Czas: {{ $skill->base_duration }})</div>
                                    </td>
                                    <td class="p-3 text-right">
                                        <button wire:click.stop="delete('{{ $skill->id }}')" class="text-red-400 hover:text-red-300" onclick="confirm('Na pewno usunąć?') || event.stopImmediatePropagation()">Usuń</button>
                                    </td>
                                </tr>
                            @endforeach
                            @if($skills->isEmpty())
                                <tr>
                                    <td colspan="4" class="p-6 text-center text-gray-500">Brak umiejętności w bazie danych.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
