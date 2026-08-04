<div class="min-h-screen bg-gray-900 text-gray-100 p-4 sm:p-8 font-sans">
    <div class="max-w-7xl mx-auto space-y-6">

        {{-- Header & Back link --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-800 pb-5">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="text-amber-500 hover:text-amber-400 text-sm font-semibold flex items-center gap-1 mb-1 transition">
                    &larr; Wróć do panelu głównego
                </a>
                <h1 class="text-3xl font-extrabold text-amber-500 tracking-tight flex items-center gap-2">
                    <i class="fa-solid fa-user-secret"></i> Anti-Cheat / Podejrzana Aktywność
                </h1>
                <p class="text-gray-400 text-sm mt-1">Postacie o nienaturalnie wysokim tempie polowań (spam requestów). Sprawdź ręcznie przed podjęciem akcji w panelu Postacie.</p>
            </div>
        </div>

        {{-- Stat Cards Grid --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-gray-800 border border-amber-900/60 rounded-xl p-4 flex items-center gap-4 shadow-lg">
                <div class="w-10 h-10 rounded-lg bg-amber-950/80 border border-amber-600/60 text-amber-400 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-flag"></i>
                </div>
                <div>
                    <p class="text-xs text-amber-400 font-bold uppercase tracking-wider">Otwarte</p>
                    <p class="text-xl font-extrabold text-amber-300">{{ $stats['open'] }}</p>
                </div>
            </div>

            <div class="bg-gray-800 border border-red-900/60 rounded-xl p-4 flex items-center gap-4 shadow-lg">
                <div class="w-10 h-10 rounded-lg bg-red-950/80 border border-red-600/60 text-red-400 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <p class="text-xs text-red-400 font-bold uppercase tracking-wider">Wysokie nasilenie</p>
                    <p class="text-xl font-extrabold text-red-300">{{ $stats['open_high'] }}</p>
                </div>
            </div>

            <div class="bg-gray-800 border border-emerald-900/60 rounded-xl p-4 flex items-center gap-4 shadow-lg">
                <div class="w-10 h-10 rounded-lg bg-emerald-950/80 border border-emerald-600/60 text-emerald-400 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-check"></i>
                </div>
                <div>
                    <p class="text-xs text-emerald-400 font-bold uppercase tracking-wider">Sprawdzone dziś</p>
                    <p class="text-xl font-extrabold text-emerald-300">{{ $stats['reviewed_today'] }}</p>
                </div>
            </div>

            <div class="bg-gray-800 border border-gray-700 rounded-xl p-4 flex items-center gap-4 shadow-lg">
                <div class="w-10 h-10 rounded-lg bg-gray-700 text-gray-300 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-list"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">Wszystkie</p>
                    <p class="text-xl font-extrabold text-white">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>

        {{-- Controls Bar: Search & Filters --}}
        <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4 bg-gray-800 p-4 rounded-xl border border-gray-700 shadow-lg">
            <div class="relative flex-1 min-w-0">
                <input wire:model.live.debounce.300ms="search"
                       type="text"
                       placeholder="Szukaj po nazwie postaci lub e-mailu..."
                       class="w-full bg-gray-900 border border-gray-600 rounded-lg pl-10 pr-4 py-2 text-sm text-gray-100 placeholder-gray-500 focus:outline-none focus:border-amber-500 transition">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-gray-500 text-xs"></i>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-xs font-bold text-gray-400 mr-1 uppercase">Status:</span>
                <button wire:click="$set('filterStatus', 'all')" class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $filterStatus === 'all' ? 'bg-amber-600 text-white shadow-md' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">Wszystkie</button>
                <button wire:click="$set('filterStatus', 'open')" class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $filterStatus === 'open' ? 'bg-amber-600 text-white shadow-md' : 'bg-gray-700 text-amber-400 hover:bg-gray-600' }}">Otwarte</button>
                <button wire:click="$set('filterStatus', 'reviewed')" class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $filterStatus === 'reviewed' ? 'bg-emerald-600 text-white shadow-md' : 'bg-gray-700 text-emerald-400 hover:bg-gray-600' }}">Sprawdzone</button>
                <button wire:click="$set('filterStatus', 'dismissed')" class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $filterStatus === 'dismissed' ? 'bg-gray-600 text-white shadow-md' : 'bg-gray-700 text-gray-400 hover:bg-gray-600' }}">Odrzucone</button>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-xs font-bold text-gray-400 mr-1 uppercase">Nasilenie:</span>
                <button wire:click="$set('filterSeverity', 'all')" class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $filterSeverity === 'all' ? 'bg-amber-600 text-white shadow-md' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">Wszystkie</button>
                <button wire:click="$set('filterSeverity', 'high')" class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $filterSeverity === 'high' ? 'bg-red-600 text-white shadow-md' : 'bg-gray-700 text-red-400 hover:bg-gray-600' }}">Wysokie</button>
                <button wire:click="$set('filterSeverity', 'medium')" class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $filterSeverity === 'medium' ? 'bg-yellow-600 text-white shadow-md' : 'bg-gray-700 text-yellow-400 hover:bg-gray-600' }}">Średnie</button>
            </div>
        </div>

        {{-- Table Container --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-200">
                    <thead class="bg-gray-900/90 text-xs uppercase font-bold text-gray-400 tracking-wider border-b border-gray-700">
                        <tr>
                            <th class="py-3.5 px-4">Wykryto</th>
                            <th class="py-3.5 px-4">Postać / Konto</th>
                            <th class="py-3.5 px-4">Typ</th>
                            <th class="py-3.5 px-4">Nasilenie</th>
                            <th class="py-3.5 px-4">Wynik</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4 text-right">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700/60">
                        @forelse($flags as $flag)
                            <tr class="hover:bg-gray-750 transition-colors">
                                <td class="py-3.5 px-4 whitespace-nowrap text-xs text-gray-400">
                                    <span title="{{ $flag->created_at?->format('d.m.Y H:i') }}">{{ $flag->created_at?->diffForHumans() }}</span>
                                </td>

                                <td class="py-3.5 px-4">
                                    @if($flag->character)
                                        <p class="font-bold text-amber-200 text-xs leading-tight">
                                            {{ $flag->character->name }}
                                            <span class="text-[10px] text-gray-400 font-normal">(Lvl {{ $flag->character->level }})</span>
                                        </p>
                                        <p class="text-[11px] text-gray-400 truncate max-w-[180px]">
                                            {{ $flag->character->user->email ?? 'Brak e-maila' }}
                                        </p>
                                    @else
                                        <span class="text-xs text-gray-500 italic">Postać usunięta</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($flag->type === 'kill_rate')
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-extrabold bg-indigo-950/80 text-indigo-300 border border-indigo-700/60 inline-flex items-center gap-1">
                                            <i class="fa-solid fa-skull"></i> Tempo polowań
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-extrabold bg-gray-700 text-gray-300 border border-gray-600 inline-flex items-center gap-1">
                                            {{ $flag->type }}
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($flag->severity === 'high')
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-extrabold bg-red-950/80 text-red-300 border border-red-700/60">Wysokie</span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-extrabold bg-yellow-950/80 text-yellow-300 border border-yellow-700/60">Średnie</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 whitespace-nowrap text-xs text-gray-300 font-mono">
                                    {{ $flag->metric_value }} walk / {{ $flag->window_minutes }} min
                                    <div class="text-[10px] text-gray-500">próg: {{ $flag->threshold }} &middot; {{ $flag->details['kills_per_minute'] ?? '?' }}/min</div>
                                </td>

                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($flag->status === 'open')
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-extrabold bg-amber-950/80 text-amber-300 border border-amber-700/60">Otwarte</span>
                                    @elseif($flag->status === 'reviewed')
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-extrabold bg-emerald-950/80 text-emerald-300 border border-emerald-700/60">Sprawdzone</span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-extrabold bg-gray-700 text-gray-400 border border-gray-600">Odrzucone</span>
                                    @endif
                                    @if($flag->reviewer)
                                        <div class="text-[10px] text-gray-500 mt-0.5">przez {{ $flag->reviewer->name }}</div>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($flag->status !== 'reviewed')
                                            <button wire:click="markReviewed({{ $flag->id }})"
                                                    class="p-1.5 rounded bg-emerald-950/80 hover:bg-emerald-900 border border-emerald-800/60 text-emerald-300 hover:text-white transition"
                                                    title="Oznacz jako sprawdzone">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        @endif
                                        @if($flag->status !== 'dismissed')
                                            <button wire:click="dismiss({{ $flag->id }})"
                                                    class="p-1.5 rounded bg-gray-700 hover:bg-gray-600 text-gray-300 hover:text-white transition"
                                                    title="Odrzuć (fałszywy alarm)">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        @endif
                                        @if($flag->status !== 'open')
                                            <button wire:click="reopen({{ $flag->id }})"
                                                    class="p-1.5 rounded bg-amber-950/80 hover:bg-amber-900 border border-amber-800/60 text-amber-300 hover:text-white transition"
                                                    title="Przywróć jako otwarte">
                                                <i class="fa-solid fa-rotate-left"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-gray-500">
                                    <i class="fa-solid fa-shield-halved text-3xl mb-2 block"></i>
                                    <p class="text-sm font-semibold">Brak zgłoszeń spełniających kryteria.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($flags->hasPages())
                <div class="p-4 border-t border-gray-700 bg-gray-900/60">
                    {{ $flags->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
