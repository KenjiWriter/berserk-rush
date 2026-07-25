<div class="min-h-screen bg-gray-900 text-gray-100 p-4 sm:p-8 font-sans">
    <div class="max-w-7xl mx-auto space-y-6">

        {{-- Header & Back link --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-800 pb-5">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="text-amber-500 hover:text-amber-400 text-sm font-semibold flex items-center gap-1 mb-1 transition">
                    &larr; Wróć do panelu głównego
                </a>
                <h1 class="text-3xl font-extrabold text-amber-500 tracking-tight flex items-center gap-2">
                    <span>💡</span> Sugestie i Uwagi Graczy
                </h1>
                <p class="text-gray-400 text-sm mt-1">Przeglądanie zgłoszeń od graczy, zarządzanie ich statusami oraz dodawanie wewnętrznych notatek.</p>
            </div>
        </div>

        {{-- Stat Cards Grid --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-gray-800 border border-gray-700 rounded-xl p-4 flex items-center gap-4 shadow-lg">
                <div class="w-10 h-10 rounded-lg bg-gray-700 text-amber-400 flex items-center justify-center text-xl font-bold">
                    📋
                </div>
                <div>
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">Wszystkie</p>
                    <p class="text-xl font-extrabold text-white">{{ $stats['total'] }}</p>
                </div>
            </div>

            <div class="bg-gray-800 border border-amber-900/60 rounded-xl p-4 flex items-center gap-4 shadow-lg">
                <div class="w-10 h-10 rounded-lg bg-amber-950/80 border border-amber-600/60 text-amber-400 flex items-center justify-center text-xl font-bold">
                    ✨
                </div>
                <div>
                    <p class="text-xs text-amber-400 font-bold uppercase tracking-wider">Nowe</p>
                    <p class="text-xl font-extrabold text-amber-300">{{ $stats['new'] }}</p>
                </div>
            </div>

            <div class="bg-gray-800 border border-blue-900/60 rounded-xl p-4 flex items-center gap-4 shadow-lg">
                <div class="w-10 h-10 rounded-lg bg-blue-950/80 border border-blue-600/60 text-blue-400 flex items-center justify-center text-xl font-bold">
                    ⏳
                </div>
                <div>
                    <p class="text-xs text-blue-400 font-bold uppercase tracking-wider">W trakcie</p>
                    <p class="text-xl font-extrabold text-blue-300">{{ $stats['in_progress'] }}</p>
                </div>
            </div>

            <div class="bg-gray-800 border border-emerald-900/60 rounded-xl p-4 flex items-center gap-4 shadow-lg">
                <div class="w-10 h-10 rounded-lg bg-emerald-950/80 border border-emerald-600/60 text-emerald-400 flex items-center justify-center text-xl font-bold">
                    ✅
                </div>
                <div>
                    <p class="text-xs text-emerald-400 font-bold uppercase tracking-wider">Rozpatrzone</p>
                    <p class="text-xl font-extrabold text-emerald-300">{{ $stats['resolved'] }}</p>
                </div>
            </div>
        </div>

        {{-- Controls Bar: Search & Filters --}}
        <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4 bg-gray-800 p-4 rounded-xl border border-gray-700 shadow-lg">
            {{-- Search input --}}
            <div class="relative flex-1 min-w-0">
                <input wire:model.live.debounce.300ms="search"
                       type="text"
                       placeholder="Szukaj w treści, graczu lub postaci..."
                       class="w-full bg-gray-900 border border-gray-600 rounded-lg pl-10 pr-4 py-2 text-sm text-gray-100 placeholder-gray-500 focus:outline-none focus:border-amber-500 transition">
                <span class="absolute left-3 top-2.5 text-gray-500 text-sm">🔍</span>
            </div>

            {{-- Status Filter buttons --}}
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-xs font-bold text-gray-400 mr-1 uppercase">Status:</span>
                <button wire:click="$set('filterStatus', 'all')"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $filterStatus === 'all' ? 'bg-amber-600 text-white shadow-md' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                    Wszystkie
                </button>
                <button wire:click="$set('filterStatus', 'new')"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $filterStatus === 'new' ? 'bg-yellow-600 text-white shadow-md' : 'bg-gray-700 text-yellow-400 hover:bg-gray-600' }}">
                    Nowe
                </button>
                <button wire:click="$set('filterStatus', 'in_progress')"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $filterStatus === 'in_progress' ? 'bg-blue-600 text-white shadow-md' : 'bg-gray-700 text-blue-400 hover:bg-gray-600' }}">
                    W trakcie
                </button>
                <button wire:click="$set('filterStatus', 'resolved')"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $filterStatus === 'resolved' ? 'bg-emerald-600 text-white shadow-md' : 'bg-gray-700 text-emerald-400 hover:bg-gray-600' }}">
                    Rozpatrzone
                </button>
                <button wire:click="$set('filterStatus', 'rejected')"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $filterStatus === 'rejected' ? 'bg-red-600 text-white shadow-md' : 'bg-gray-700 text-red-400 hover:bg-gray-600' }}">
                    Odrzucone
                </button>
            </div>
        </div>

        {{-- Table Container --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-200">
                    <thead class="bg-gray-900/90 text-xs uppercase font-bold text-gray-400 tracking-wider border-b border-gray-700">
                        <tr>
                            <th class="py-3.5 px-4">Data</th>
                            <th class="py-3.5 px-4">Gracz / Postać</th>
                            <th class="py-3.5 px-4">Kategoria</th>
                            <th class="py-3.5 px-4">Treść Sugestii</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4">Notatka Admina</th>
                            <th class="py-3.5 px-4 text-right">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700/60">
                        @forelse($suggestions as $suggestion)
                            <tr class="hover:bg-gray-750 transition-colors">
                                {{-- Date --}}
                                <td class="py-3.5 px-4 whitespace-nowrap text-xs text-gray-400 font-mono">
                                    {{ $suggestion->created_at ? $suggestion->created_at->format('d.m.Y H:i') : '-' }}
                                </td>

                                {{-- User & Character --}}
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2.5">
                                        @if($suggestion->character)
                                            <div class="w-8 h-8 rounded-lg bg-gray-900 border border-amber-600/60 overflow-hidden shrink-0">
                                                @if($suggestion->character->avatar && file_exists(public_path('img/avatars/' . $suggestion->character->avatar . '.png')))
                                                    <img src="{{ asset('img/avatars/' . $suggestion->character->avatar . '.png') }}" alt="Avatar" class="w-full h-full object-cover">
                                                @else
                                                    <div class="w-full h-full flex items-center justify-center text-[10px] text-amber-400 font-bold">HERO</div>
                                                @endif
                                            </div>
                                            <div>
                                                <p class="font-bold text-amber-200 text-xs leading-tight">
                                                    {{ $suggestion->character->name }}
                                                    <span class="text-[10px] text-gray-400 font-normal">(Lvl {{ $suggestion->character->level }})</span>
                                                </p>
                                                <p class="text-[11px] text-gray-400 truncate max-w-[150px]">
                                                    {{ $suggestion->user->email ?? 'Brak e-maila' }}
                                                </p>
                                            </div>
                                        @else
                                            <div>
                                                <p class="font-bold text-gray-200 text-xs leading-tight">
                                                    {{ $suggestion->user->name ?? 'Konto usunięte' }}
                                                </p>
                                                <p class="text-[11px] text-gray-400">
                                                    {{ $suggestion->user->email ?? '-' }}
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                {{-- Category --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($suggestion->category === 'błąd')
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-extrabold bg-red-950/80 text-red-300 border border-red-700/60 inline-flex items-center gap-1">
                                            🐛 Błąd
                                        </span>
                                    @elseif($suggestion->category === 'inne')
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-extrabold bg-indigo-950/80 text-indigo-300 border border-indigo-700/60 inline-flex items-center gap-1">
                                            💬 Inne
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-extrabold bg-amber-950/80 text-amber-300 border border-amber-700/60 inline-flex items-center gap-1">
                                            💡 Sugestia
                                        </span>
                                    @endif
                                </td>

                                {{-- Content --}}
                                <td class="py-3.5 px-4 max-w-md">
                                    <div class="text-xs text-gray-200 font-sans whitespace-pre-line break-words max-h-28 overflow-y-auto custom-scrollbar p-1">
                                        {{ $suggestion->content }}
                                    </div>
                                </td>

                                {{-- Status Dropdown / Select --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <select wire:change="updateStatus({{ $suggestion->id }}, $event.target.value)"
                                            class="text-xs font-bold rounded-lg px-2.5 py-1.5 border focus:outline-none transition-colors cursor-pointer
                                                   {{ $suggestion->status === 'new' ? 'bg-amber-950/90 text-yellow-300 border-amber-600/80' : '' }}
                                                   {{ $suggestion->status === 'in_progress' ? 'bg-blue-950/90 text-blue-300 border-blue-600/80' : '' }}
                                                   {{ $suggestion->status === 'resolved' ? 'bg-emerald-950/90 text-emerald-300 border-emerald-600/80' : '' }}
                                                   {{ $suggestion->status === 'rejected' ? 'bg-red-950/90 text-red-300 border-red-600/80' : '' }}">
                                        <option value="new" {{ $suggestion->status === 'new' ? 'selected' : '' }}>✨ Nowa</option>
                                        <option value="in_progress" {{ $suggestion->status === 'in_progress' ? 'selected' : '' }}>⏳ W trakcie</option>
                                        <option value="resolved" {{ $suggestion->status === 'resolved' ? 'selected' : '' }}>✅ Rozpatrzona</option>
                                        <option value="rejected" {{ $suggestion->status === 'rejected' ? 'selected' : '' }}>❌ Odrzucona</option>
                                    </select>
                                </td>

                                {{-- Admin Notes --}}
                                <td class="py-3.5 px-4 max-w-xs">
                                    @if($suggestion->admin_notes)
                                        <div class="text-[11px] text-amber-300 bg-amber-950/30 border border-amber-800/40 rounded p-1.5 italic truncate cursor-pointer"
                                             wire:click="openNotesModal({{ $suggestion->id }})"
                                             title="Kliknij, aby edytować notatkę">
                                            📝 {{ $suggestion->admin_notes }}
                                        </div>
                                    @else
                                        <button wire:click="openNotesModal({{ $suggestion->id }})"
                                                class="text-xs text-gray-500 hover:text-gray-300 underline font-medium">
                                            + Dodaj notatkę
                                        </button>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <button wire:click="openNotesModal({{ $suggestion->id }})"
                                                class="p-1.5 rounded bg-gray-700 hover:bg-gray-600 text-gray-300 hover:text-white transition"
                                                title="Dodaj/Edytuj notatkę">
                                            📝
                                        </button>
                                        <button wire:confirm="Czy na pewno chcesz usunąć tę sugestię?"
                                                wire:click="deleteSuggestion({{ $suggestion->id }})"
                                                class="p-1.5 rounded bg-red-950/80 hover:bg-red-900 border border-red-800/60 text-red-300 hover:text-white transition"
                                                title="Usuń sugestię">
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-gray-500">
                                    <p class="text-3xl mb-2">📭</p>
                                    <p class="text-sm font-semibold">Brak zgłoszonych uwag lub sugestii spełniających kryteria.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($suggestions->hasPages())
                <div class="p-4 border-t border-gray-700 bg-gray-900/60">
                    {{ $suggestions->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Admin Notes Edit Modal --}}
    @if($editingSuggestionId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="bg-gray-800 border border-gray-700 rounded-xl p-6 max-w-lg w-full shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-gray-700 pb-3">
                    <h3 class="text-lg font-bold text-amber-500 flex items-center gap-2">
                        <span>📝</span> Notatka Administratora
                    </h3>
                    <button wire:click="closeNotesModal" class="text-gray-400 hover:text-white">✕</button>
                </div>

                <div>
                    <label class="block text-xs text-gray-400 mb-1">Wpisz prywatną notatkę dotyczącą tego zgłoszenia (widoczną tylko w panelu admina):</label>
                    <textarea wire:model="adminNotesInput"
                              rows="4"
                              placeholder="Np. Wdrożone w aktualizacji 1.4 lub Wymaga sprawdzenia z programistą..."
                              class="w-full bg-gray-900 border border-gray-600 rounded-lg p-3 text-sm text-gray-100 focus:outline-none focus:border-amber-500 transition resize-none"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button wire:click="closeNotesModal" class="px-4 py-2 rounded-lg bg-gray-700 text-gray-300 hover:bg-gray-600 font-bold text-xs">
                        Anuluj
                    </button>
                    <button wire:click="saveNotes" class="px-5 py-2 rounded-lg bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs shadow-md">
                        Zapisz notatkę
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
