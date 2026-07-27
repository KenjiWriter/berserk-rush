<div class="min-h-screen bg-gray-900 text-gray-100 p-4 sm:p-8 font-sans">
    <div class="max-w-7xl mx-auto space-y-6">

        {{-- Header & Back link --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-800 pb-5">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="text-amber-500 hover:text-amber-400 text-sm font-semibold flex items-center gap-1 mb-1 transition">
                    &larr; Wróć do panelu głównego
                </a>
                <h1 class="text-3xl font-extrabold text-amber-500 tracking-tight flex items-center gap-2">
                    <span>🧙‍♂️</span> Lista Postaci i Graczy
                </h1>
                <p class="text-gray-400 text-sm mt-1">Podgląd lokalizacji, statusu online/offline, zarządzanie VIP, gemami oraz mute na czacie.</p>
            </div>
        </div>

        {{-- Controls Bar: Search & Filter Tabs --}}
        <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 bg-gray-800 p-4 rounded-xl border border-gray-700 shadow-lg">
            {{-- Search input --}}
            <div class="relative flex-1">
                <input
                    wire:model.live.debounce.300ms="search"
                    type="text"
                    placeholder="Szukaj po nazwie postaci lub e-mailu gracza..."
                    class="w-full bg-gray-900 border border-gray-600 rounded-lg pl-10 pr-4 py-2 text-sm text-gray-100 placeholder-gray-500 focus:outline-none focus:border-amber-500 transition"
                >
                <span class="absolute left-3 top-2.5 text-gray-500 text-sm">🔍</span>
            </div>

            {{-- Filter buttons --}}
            <div class="flex flex-wrap items-center gap-2">
                <button
                    wire:click="$set('filter', 'all')"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ $filter === 'all' ? 'bg-amber-600 text-white shadow-md' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}"
                >
                    Wszyscy
                </button>
                <button
                    wire:click="$set('filter', 'online')"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $filter === 'online' ? 'bg-emerald-600 text-white shadow-md' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}"
                >
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Online
                </button>
                <button
                    wire:click="$set('filter', 'vip')"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1 {{ $filter === 'vip' ? 'bg-yellow-600 text-white shadow-md' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}"
                >
                    👑 VIP
                </button>
                <button
                    wire:click="$set('filter', 'muted')"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1 {{ $filter === 'muted' ? 'bg-red-600 text-white shadow-md' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}"
                >
                    🔇 Wyciszeni
                </button>
            </div>
        </div>

        {{-- Characters Table --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-300">
                    <thead class="bg-gray-900/80 text-amber-400 uppercase text-[11px] font-bold tracking-wider border-b border-gray-700">
                        <tr>
                            <th class="py-3.5 px-4">Postać / Konto</th>
                            <th class="py-3.5 px-4">Lokalizacja</th>
                            <th class="py-3.5 px-4">Status Online</th>
                            <th class="py-3.5 px-4">VIP</th>
                            <th class="py-3.5 px-4">Gemy</th>
                            <th class="py-3.5 px-4">Status Czat (Mute)</th>
                            <th class="py-3.5 px-4 text-right">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700/60">
                        @forelse ($characters as $char)
                            @php
                                $user = $char->user;
                                $isOnline = $char->isOnline();
                                $hasVip = $user?->hasPremium();
                                $isMuted = $user?->isMuted();
                            @endphp
                            <tr class="hover:bg-gray-750 transition-colors">
                                {{-- Postać / Konto --}}
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-gray-900 border border-amber-700/50 flex items-center justify-center text-lg shrink-0">
                                            ⚔️
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-gray-100 text-base">{{ $char->name }}</span>
                                                <span class="text-xs bg-amber-950 text-amber-400 border border-amber-800/60 px-1.5 py-0.5 rounded font-mono">Poz. {{ $char->level }}</span>
                                            </div>
                                            <div class="text-xs text-gray-400 mt-0.5 flex items-center gap-2">
                                                <span>📧 {{ $user?->email ?? 'Brak email' }}</span>
                                                @if($char->guild)
                                                    <span class="text-amber-500 font-semibold">[{{ $char->guild->name }}]</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Lokalizacja --}}
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center gap-1.5 bg-gray-900 border border-gray-700 text-amber-200 text-xs px-2.5 py-1 rounded-md font-medium">
                                        📍 {{ $char->current_location ?? 'Miasto (Centrum)' }}
                                    </span>
                                </td>

                                {{-- Status Online --}}
                                <td class="py-3 px-4">
                                    @if ($isOnline)
                                        <span class="inline-flex items-center gap-1.5 bg-emerald-950/80 border border-emerald-600/60 text-emerald-300 text-xs px-2.5 py-1 rounded-full font-bold shadow-[0_0_8px_rgba(16,185,129,0.2)]">
                                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                            Online
                                        </span>
                                    @else
                                        <div class="flex flex-col">
                                            <span class="inline-flex items-center gap-1.5 bg-gray-900 text-gray-400 border border-gray-700 text-xs px-2.5 py-1 rounded-full font-semibold w-max">
                                                <span class="w-2 h-2 rounded-full bg-gray-500"></span>
                                                Offline
                                            </span>
                                            <span class="text-[11px] text-gray-500 mt-1">
                                                {{ $char->last_active_at ? 'Aktywność: ' . $char->last_active_at->diffForHumans() : 'Brak danych' }}
                                            </span>
                                        </div>
                                    @endif
                                </td>

                                {{-- VIP --}}
                                <td class="py-3 px-4">
                                    @if ($hasVip)
                                        <div class="flex flex-col gap-1 items-start">
                                            <span class="inline-flex items-center gap-1 bg-yellow-950/90 border border-yellow-500/60 text-yellow-300 text-xs px-2.5 py-1 rounded-md font-bold shadow-[0_0_10px_rgba(234,179,8,0.3)] w-max">
                                                ✨ VIP Aktywny
                                            </span>
                                            <span class="text-[10px] text-yellow-500/80">do {{ $user->premium_until->format('Y-m-d H:i') }}</span>
                                            <button wire:click="revokeVip('{{ $user?->id }}')" onclick="confirm('Czy na pewno chcesz odebrać VIP graczowi {{ $char->name }}?') || event.stopImmediatePropagation()" class="text-[10px] text-red-400 hover:text-red-300 hover:underline font-semibold flex items-center gap-1 cursor-pointer">
                                                🚫 Zabierz VIP
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-500 italic">Brak</span>
                                    @endif
                                </td>

                                {{-- Gemy --}}
                                <td class="py-3 px-4 font-mono font-bold text-amber-300 text-sm">
                                    💎 {{ number_format($user?->gems ?? 0) }}
                                </td>

                                {{-- Status Czat (Mute) --}}
                                <td class="py-3 px-4">
                                    @if ($isMuted)
                                        <div class="flex flex-col">
                                            <span class="inline-flex items-center gap-1 bg-red-950/90 border border-red-600/70 text-red-300 text-xs px-2.5 py-1 rounded-md font-bold shadow-[0_0_8px_rgba(239,68,68,0.3)] w-max">
                                                🔇 Wyciszony
                                            </span>
                                            <span class="text-[10px] text-red-400 mt-0.5">do {{ $user->muted_until->format('H:i d.m.Y') }}</span>
                                        </div>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-emerald-400 text-xs font-semibold">
                                            🔊 Aktywny (Brak muta)
                                        </span>
                                    @endif
                                </td>

                                {{-- Akcje --}}
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        {{-- Grant VIP Button --}}
                                        <button
                                            wire:click="openVipModal('{{ $user?->id }}', '{{ $char->name }}')"
                                            class="bg-yellow-900/60 hover:bg-yellow-700/80 border border-yellow-600/60 text-yellow-200 px-2.5 py-1 rounded text-xs font-bold transition flex items-center gap-1"
                                            title="Nadaj VIP"
                                        >
                                            👑 VIP
                                        </button>

                                        {{-- Add Gems Button --}}
                                        <button
                                            wire:click="openGemsModal('{{ $user?->id }}', '{{ $char->name }}')"
                                            class="bg-amber-900/60 hover:bg-amber-700/80 border border-amber-600/60 text-amber-200 px-2.5 py-1 rounded text-xs font-bold transition flex items-center gap-1"
                                            title="Dodaj gemy"
                                        >
                                            💎 +Gemy
                                        </button>

                                        {{-- Mute / Unmute Button --}}
                                        @if ($isMuted)
                                            <button
                                                wire:click="unmuteUser('{{ $user?->id }}')"
                                                class="bg-emerald-900/60 hover:bg-emerald-700/80 border border-emerald-600/60 text-emerald-200 px-2.5 py-1 rounded text-xs font-bold transition flex items-center gap-1"
                                                title="Odcisz użytkownika"
                                            >
                                                🔊 Odcisz
                                            </button>
                                        @else
                                            <button
                                                wire:click="openMuteModal('{{ $user?->id }}', '{{ $char->name }}')"
                                                class="bg-red-900/60 hover:bg-red-700/80 border border-red-600/60 text-red-200 px-2.5 py-1 rounded text-xs font-bold transition flex items-center gap-1"
                                                title="Wycisz użytkownika na czacie"
                                            >
                                                🔇 Wycisz
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-gray-500 italic">
                                    Nie znaleziono żadnych postaci spełniających kryteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($characters->hasPages())
                <div class="p-4 border-t border-gray-700 bg-gray-900/50">
                    {{ $characters->links() }}
                </div>
            @endif
        </div>

        {{-- ========== MODAL: NADAJ / ZARZĄDZAJ VIP ========== --}}
        @if ($showVipModal)
            @php
                $modalUser = $selectedUserId ? \App\Models\User::find($selectedUserId) : null;
                $modalHasVip = $modalUser?->hasPremium();
            @endphp
            <div class="fixed inset-0 z-[10000] bg-black/75 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-gray-800 border border-yellow-600/60 rounded-xl max-w-md w-full p-6 shadow-2xl space-y-4">
                    <h3 class="text-xl font-bold text-yellow-400 flex items-center gap-2">
                        👑 Zarządzaj VIP dla postaci: <span class="text-white">{{ $selectedCharacterName }}</span>
                    </h3>
                    @if($modalHasVip)
                        <div class="bg-yellow-950/60 border border-yellow-600/50 p-3 rounded-lg text-xs text-yellow-200">
                            ✨ Gracz posiada aktywny VIP do: <strong class="text-yellow-400">{{ $modalUser->premium_until->format('Y-m-d H:i') }}</strong>.
                        </div>
                    @endif

                    <p class="text-sm text-gray-300">Wybierz liczbę dni, które chcesz dodać do dostępu VIP (Premium) konta gracza.</p>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Czas trwania (w dniach):</label>
                        <select wire:model="vipDays" class="w-full bg-gray-900 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-yellow-500">
                            <option value="7">7 dni</option>
                            <option value="14">14 dni</option>
                            <option value="30">30 dni (1 miesiąc)</option>
                            <option value="90">90 dni (3 miesiące)</option>
                            <option value="365">365 dni (1 rok)</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-between gap-2 pt-2 border-t border-gray-700">
                        @if($modalHasVip)
                            <button wire:click="revokeVip" onclick="confirm('Czy na pewno chcesz odebrać status VIP temu graczowi?') || event.stopImmediatePropagation()" class="px-3 py-2 bg-red-900/80 hover:bg-red-700 text-red-200 border border-red-600 rounded-lg text-xs font-bold transition flex items-center gap-1 cursor-pointer">
                                🚫 Zabierz VIP
                            </button>
                        @else
                            <div></div>
                        @endif
                        <div class="flex gap-2">
                            <button wire:click="closeVipModal" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm text-gray-300 font-semibold transition cursor-pointer">Anuluj</button>
                            <button wire:click="grantVip" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-500 rounded-lg text-sm text-white font-bold transition cursor-pointer">Przyznaj VIP</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ========== MODAL: DODAJ GEMY ========== --}}
        @if ($showGemsModal)
            <div class="fixed inset-0 z-[10000] bg-black/75 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-gray-800 border border-amber-600/60 rounded-xl max-w-md w-full p-6 shadow-2xl space-y-4">
                    <h3 class="text-xl font-bold text-amber-400 flex items-center gap-2">
                        💎 Dodaj gemy dla postaci: <span class="text-white">{{ $selectedCharacterName }}</span>
                    </h3>
                    <p class="text-sm text-gray-300">Wpisz ilość gemów (diamentów), które chcesz dodać do konta tego gracza.</p>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Ilość gemów do dodania:</label>
                        <input type="number" min="1" wire:model="gemsAmount" class="w-full bg-gray-900 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-amber-500 font-mono">
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button wire:click="closeGemsModal" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm text-gray-300 font-semibold transition">Anuluj</button>
                        <button wire:click="addGems" class="px-4 py-2 bg-amber-600 hover:bg-amber-500 rounded-lg text-sm text-white font-bold transition">Dodaj gemy</button>
                    </div>
                </div>
            </div>
        @endif

        {{-- ========== MODAL: WYCISZ (MUTE) ========== --}}
        @if ($showMuteModal)
            <div class="fixed inset-0 z-[10000] bg-black/75 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-gray-800 border border-red-600/60 rounded-xl max-w-md w-full p-6 shadow-2xl space-y-4">
                    <h3 class="text-xl font-bold text-red-400 flex items-center gap-2">
                        🔇 Wycisz na czacie: <span class="text-white">{{ $selectedCharacterName }}</span>
                    </h3>
                    <p class="text-sm text-gray-300">Gracz po wyciszeniu nie będzie mógł pisać wiadomości na czacie globalnym ani gildyjnym przez określony czas.</p>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Czas wyciszenia (w minutach):</label>
                        <select wire:model="muteMinutes" class="w-full bg-gray-900 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-red-500">
                            <option value="15">15 minut</option>
                            <option value="30">30 minut</option>
                            <option value="60">1 godzina (60 min)</option>
                            <option value="360">6 godzin (360 min)</option>
                            <option value="1440">24 godziny (1440 min)</option>
                            <option value="10080">7 dni (10080 min)</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button wire:click="closeMuteModal" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm text-gray-300 font-semibold transition">Anuluj</button>
                        <button wire:click="muteUser" class="px-4 py-2 bg-red-600 hover:bg-red-500 rounded-lg text-sm text-white font-bold transition">Wycisz gracza</button>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>
