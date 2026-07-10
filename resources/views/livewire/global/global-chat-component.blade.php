<div
    x-data="{
        scrollToBottom() {
            const el = this.$refs.chatBox;
            if (el) el.scrollTop = el.scrollHeight;
        },
        init() {
            this.$nextTick(() => this.scrollToBottom());
            this.$watch('$wire.messages', () => this.$nextTick(() => this.scrollToBottom()));
        }
    }"
    class="fixed bottom-0 right-0 m-4 z-50 w-80 font-sans select-none"
    style="font-family: 'Cinzel', serif;"
>
    {{-- ========== MINIMIZED BUBBLE ========== --}}
    @if (!$isOpen)
        <button
            wire:click="toggleChat"
            class="ml-auto flex items-center gap-2 bg-gradient-to-r from-amber-900/95 to-stone-900/95 border border-amber-700/60 rounded-full px-4 py-2 shadow-2xl hover:from-amber-800/95 transition-all duration-200 text-amber-200 font-bold text-sm cursor-pointer backdrop-blur-md"
        >
            <span class="text-base">💬</span>
            <span>Czat Globalny</span>
        </button>
    @endif

    {{-- ========== EXPANDED PANEL ========== --}}
    @if ($isOpen)
    <div
        class="flex flex-col rounded-xl overflow-hidden shadow-2xl border border-amber-700/40 backdrop-blur-md"
        style="background: linear-gradient(160deg, rgba(20,10,5,0.97) 0%, rgba(40,20,8,0.97) 100%);"
    >
        {{-- ---- Header ---- --}}
        <div class="flex items-center justify-between px-3 py-2 border-b border-amber-800/50"
             style="background: linear-gradient(90deg, rgba(120,53,15,0.6) 0%, rgba(60,25,8,0.6) 100%);">
            <div class="flex items-center gap-2">
                <span class="text-amber-400 text-sm">⚔️</span>
                <span class="text-amber-200 font-bold text-sm tracking-wider uppercase">Czat Globalny</span>
                <span class="w-2 h-2 rounded-full bg-emerald-400 shadow-lg shadow-emerald-400/50 animate-pulse"></span>
            </div>
            <button
                wire:click="toggleChat"
                class="text-amber-500 hover:text-amber-200 text-lg leading-none cursor-pointer transition-colors"
                title="Minimalizuj"
            >−</button>
        </div>

        {{-- ---- Messages Box ---- --}}
        <div
            x-ref="chatBox"
            class="flex flex-col gap-1 overflow-y-auto px-3 py-2 scrollbar-thin"
            style="height: 260px; scrollbar-color: rgba(180,120,30,0.4) transparent;"
        >
            @if (count($messages) === 0)
                <p class="text-amber-600/60 text-xs text-center mt-10 italic">Brak wiadomości. Bądź pierwszy!</p>
            @endif

            @foreach ($messages as $idx => $msg)
                <div class="group flex gap-1 text-xs leading-relaxed">
                    {{-- Timestamp --}}
                    <span class="text-amber-700/50 shrink-0 mt-0.5">{{ substr($msg['sent_at'], 0, 5) }}</span>

                    {{-- Nick + hover tooltip trigger --}}
                    <div class="relative">
                        <button
                            wire:click="loadTooltip('{{ $msg['character_id'] }}')"
                            class="text-amber-400 hover:text-amber-200 font-bold cursor-pointer transition-colors hover:underline decoration-dotted"
                            title="Kliknij aby sprawdzić postać"
                        >{{ $msg['character_name'] }}</button>

                        <span class="text-amber-600/70">[{{ $msg['character_level'] }}]</span>

                        {{-- Tooltip: loaded data for this character --}}
                        @if ($activeTooltipId === $msg['character_id'] && isset($tooltipData[$msg['character_id']]))
                            @php $td = $tooltipData[$msg['character_id']]; @endphp
                            <div
                                class="absolute bottom-full left-0 mb-2 z-50 rounded-lg border border-amber-700/60 shadow-2xl p-3 w-56 text-left"
                                style="background: linear-gradient(160deg, rgba(15,7,2,0.98) 0%, rgba(40,18,4,0.98) 100%);"
                            >
                                {{-- Arrow --}}
                                <div class="absolute -bottom-1.5 left-3 w-3 h-3 rotate-45 bg-amber-900/80 border-r border-b border-amber-700/60"></div>

                                {{-- Close button --}}
                                <button
                                    wire:click="closeTooltip"
                                    class="absolute top-1.5 right-2 text-amber-600 hover:text-amber-200 text-xs cursor-pointer"
                                >✕</button>

                                {{-- Character header --}}
                                <div class="mb-2 border-b border-amber-800/50 pb-1.5">
                                    <p class="text-amber-300 font-bold text-sm">{{ $td['name'] }}</p>
                                    <div class="flex gap-3 text-xs mt-1">
                                        <span class="text-amber-500">Poz. <span class="text-amber-200 font-bold">{{ $td['level'] }}</span></span>
                                        <span class="text-amber-500">CP: <span class="text-amber-200 font-bold">{{ number_format($td['combat_power']) }}</span></span>
                                    </div>
                                </div>

                                {{-- Equipped items --}}
                                <p class="text-amber-600/80 text-xs font-semibold uppercase tracking-wider mb-1.5">Ekwipunek</p>
                                @if (count($td['equipped_items']) === 0)
                                    <p class="text-amber-700/60 text-xs italic">Brak założonego ekwipunku</p>
                                @else
                                    <div class="space-y-1">
                                        @foreach ($td['equipped_items'] as $ei)
                                            @php
                                                $rarityColor = match($ei['rarity'] ?? 'common') {
                                                    'uncommon'  => 'text-green-400',
                                                    'rare'      => 'text-blue-400',
                                                    'epic'      => 'text-purple-400',
                                                    'legendary' => 'text-amber-400',
                                                    default     => 'text-stone-300',
                                                };
                                            @endphp
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="{{ $rarityColor }} truncate max-w-[130px]">
                                                    {{ $ei['name'] }}
                                                    @if ($ei['upgrade_level'] > 0)
                                                        <span class="text-emerald-400">+{{ $ei['upgrade_level'] }}</span>
                                                    @endif
                                                </span>
                                                <span class="text-amber-700 text-[10px] ml-1">{{ number_format($ei['combat_power']) }} CP</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Message text --}}
                    <span class="text-stone-300 break-words min-w-0">: {{ $msg['message'] }}</span>
                </div>
            @endforeach
        </div>

        {{-- ---- Input area ---- --}}
        @if (Auth::check() && session('active_character'))
            <div class="border-t border-amber-800/40 px-2 py-2">
                @error('newMessage')
                    <p class="text-red-400 text-xs mb-1 px-1">{{ $message }}</p>
                @enderror
                <form wire:submit="sendMessage" class="flex gap-1">
                    <input
                        wire:model="newMessage"
                        type="text"
                        maxlength="200"
                        placeholder="Napisz wiadomość…"
                        autocomplete="off"
                        class="flex-1 bg-stone-900/80 border border-amber-800/40 rounded-lg px-3 py-1.5 text-xs text-amber-100 placeholder-amber-700/60 focus:outline-none focus:border-amber-600/60 transition-colors"
                    >
                    <button
                        type="submit"
                        class="shrink-0 bg-gradient-to-b from-amber-700 to-amber-900 hover:from-amber-600 hover:to-amber-800 text-amber-100 rounded-lg px-3 py-1.5 text-xs font-bold transition-all duration-150 hover:shadow-lg hover:shadow-amber-900/50 cursor-pointer"
                    >
                        ▶
                    </button>
                </form>
            </div>
        @endif
    </div>
    @endif
</div>
