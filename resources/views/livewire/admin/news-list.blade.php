<div class="min-h-screen bg-gray-900 text-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-amber-500">📜 Zarządzanie Aktualnościami</h1>
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
                    {{ $isEditing ? 'Edytuj Aktualność' : 'Dodaj Nową Aktualność' }}
                </h2>

                <form wire:submit.prevent="save">
                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Tytuł</label>
                        <input type="text" wire:model="title" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500" required>
                        @error('title') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Data Publikacji</label>
                        <input type="date" wire:model="published_at" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500">
                        @error('published_at') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Treść</label>
                        <textarea wire:model="content" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500" rows="8" required></textarea>
                        @error('content') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    @if(!$isEditing)
                        <div class="mb-6 bg-indigo-900/40 p-3 rounded border border-indigo-700/60">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model="postToDiscord" class="form-checkbox text-indigo-500 h-4 w-4 rounded bg-gray-700 border-gray-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-indigo-200 font-semibold">
                                    <i class="fab fa-discord mr-1 text-indigo-400"></i> Wyślij powiadomienie na Discord (@Update-log notification)
                                </span>
                            </label>
                        </div>
                    @endif

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
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm w-24">Data</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Tytuł & Źródło</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm text-right w-44">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($news as $item)
                                <tr class="border-b border-gray-700 hover:bg-gray-700/50 transition">
                                    <td class="p-3 text-gray-400 text-sm whitespace-nowrap">
                                        {{ $item->published_at ? $item->published_at->format('Y-m-d') : 'Brak' }}
                                    </td>
                                    <td class="p-3 text-white">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-yellow-500">{{ $item->title }}</span>
                                            @if($item->source === 'discord' || $item->discord_message_id)
                                                <span class="bg-indigo-900/80 text-indigo-300 border border-indigo-600 text-[10px] uppercase font-semibold px-2 py-0.5 rounded-full" title="Zaimportowano / połączono z Discord ID: {{ $item->discord_message_id }}">
                                                    <i class="fab fa-discord"></i> Discord
                                                </span>
                                            @else
                                                <span class="bg-gray-700 text-gray-300 text-[10px] uppercase font-semibold px-2 py-0.5 rounded-full">
                                                    Panel Admina
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-400 truncate max-w-md mt-1">{{ Str::limit($item->content, 80) }}</div>
                                    </td>
                                    <td class="p-3 text-right whitespace-nowrap">
                                        @if(!$item->discord_message_id)
                                            <button wire:click="sendToDiscord({{ $item->id }})" class="text-indigo-400 hover:text-indigo-300 mr-3 text-xs" title="Wyślij ten wpis na Discord update-log">
                                                <i class="fab fa-discord"></i> Wyślij na DS
                                            </button>
                                        @endif
                                        <button wire:click="edit({{ $item->id }})" class="text-blue-400 hover:text-blue-300 mr-3">Edytuj</button>
                                        <button wire:click="delete({{ $item->id }})" class="text-red-400 hover:text-red-300" onclick="confirm('Na pewno usunąć?') || event.stopImmediatePropagation()">Usuń</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="p-4 text-center text-gray-500">Brak aktualności w bazie.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
