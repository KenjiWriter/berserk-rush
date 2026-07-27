<div
    x-data="{
        message: $wire.entangle('newMessage'),
        showCommands: false,
        isSending: false,
        commands: [
            { cmd: '/donate exp <ilość>', desc: 'Przekaż EXP do gildii', channel: 'guild' },
            { cmd: '/donate gold <ilość>', desc: 'Przekaż złoto do gildii', channel: 'guild' },
            { cmd: '/donate gems <ilość>', desc: 'Przekaż klejnoty do gildii', channel: 'guild' }
            {!! (Auth::check() && Auth::user()->permission_level == 9) ? ", { cmd: '/give <item_id> <ilość>', desc: 'Dodaj przedmiot postaci', channel: 'all' }, { cmd: '/give gold <ilość>', desc: 'Dodaj złoto postaci', channel: 'all' }, { cmd: '/give gems <ilość>', desc: 'Dodaj diamenty na konto', channel: 'all' }, { cmd: '/give pet Leśny Wilk', desc: 'Dodaj chowańca: Leśny Wilk', channel: 'all' }, { cmd: '/give pet Skalny Golem', desc: 'Dodaj chowańca: Skalny Golem', channel: 'all' }, { cmd: '/give pet Magiczna Wróżka', desc: 'Dodaj chowańca: Magiczna Wróżka', channel: 'all' }, { cmd: '/give pet Mroczny Smok', desc: 'Dodaj chowańca: Mroczny Smok', channel: 'all' }, { cmd: '/exp <ilość>', desc: 'Dodaj doświadczenie postaci', channel: 'all' }, { cmd: '/set level <poziom>', desc: 'Ustaw poziom postaci', channel: 'all' }, { cmd: '/set sp <ilość>', desc: 'Dodaj punkty atrybutów (SP)', channel: 'all' }" : "" !!}
        ],
        filteredCommands: [],
        userScrollPos: 0,
        userWasAtBottom: true,
        isUpdating: false,
        lastMsgCount: 0,
        captureScrollState() {
            const el = this.$refs.chatBox;
            if (el) {
                const distanceToBottom = el.scrollHeight - el.scrollTop - el.clientHeight;
                this.userWasAtBottom = distanceToBottom < 30;
                this.userScrollPos = el.scrollTop;
            }
        },
        async openTooltip(id) {
            this.captureScrollState();
            this.isUpdating = true;
            try {
                await this.$wire.loadTooltip(id);
            } finally {
                this.preserveScroll();
            }
        },
        async closeTooltip() {
            this.captureScrollState();
            this.isUpdating = true;
            try {
                await this.$wire.closeTooltip();
            } finally {
                this.preserveScroll();
            }
        },
        async inviteToGuild(id) {
            this.captureScrollState();
            this.isUpdating = true;
            try {
                await this.$wire.inviteToGuild(id);
            } finally {
                this.preserveScroll();
            }
        },
        checkCommands() {
            if ((this.message || '').startsWith('/')) {
                let search = (this.message || '').toLowerCase().split(' ')[0];
                this.filteredCommands = this.commands.filter(c => {
                    if (c.channel !== 'all' && c.channel !== this.$wire.currentChannel) return false;
                    return c.cmd.toLowerCase().startsWith(search);
                });
                this.showCommands = this.filteredCommands.length > 0;
            } else {
                this.showCommands = false;
            }
        },
        selectCommand(cmd) {
            let parts = cmd.split(' ');
            this.message = parts[0] + ' ' + (parts[1] || '') + (parts[1] ? ' ' : '');
            this.showCommands = false;
            if (this.$refs.chatInput) this.$refs.chatInput.focus();
        },
        scrollToBottom() {
            this.$nextTick(() => {
                const el = this.$refs.chatBox;
                if (el) {
                    el.scrollTop = el.scrollHeight;
                    this.userScrollPos = el.scrollTop;
                    this.userWasAtBottom = true;
                }
            });
        },
        preserveScroll() {
            const el = this.$refs.chatBox;
            if (!el) return;

            const wasBottom = this.userWasAtBottom;
            const targetPos = this.userScrollPos;
            this.isUpdating = true;

            const applyScroll = () => {
                if (el) {
                    if (wasBottom) {
                        el.scrollTop = el.scrollHeight;
                        this.userScrollPos = el.scrollTop;
                    } else {
                        el.scrollTop = targetPos;
                    }
                }
            };

            this.$nextTick(() => {
                applyScroll();
                requestAnimationFrame(() => {
                    applyScroll();
                    setTimeout(() => {
                        applyScroll();
                        this.isUpdating = false;
                    }, 120);
                });
            });
        },
        handleScroll() {
            if (this.isUpdating) return;
            const el = this.$refs.chatBox;
            if (!el) return;
            if (el.scrollHeight <= el.clientHeight) return;

            // Ignore transient zero-scroll events during DOM manipulations when user was scrolled down
            if (el.scrollTop === 0 && this.userScrollPos > 20) {
                return;
            }

            const distanceToBottom = el.scrollHeight - el.scrollTop - el.clientHeight;
            this.userScrollPos = el.scrollTop;
            this.userWasAtBottom = distanceToBottom < 30;
        },
        updateScrollPosition() {
            const el = this.$refs.chatBox;
            if (!el) return;
            const currentMsgs = el.querySelectorAll('.chat-msg-appear').length;
            if (currentMsgs > this.lastMsgCount) {
                if (this.userWasAtBottom || this.lastMsgCount === 0) {
                    this.scrollToBottom();
                } else {
                    el.scrollTop = this.userScrollPos;
                }
            } else {
                this.preserveScroll();
            }
            this.lastMsgCount = currentMsgs;
        },
        async sendMsg() {
            if (!this.message || this.message.trim() === '' || this.isSending) return;
            this.isSending = true;
            try {
                await this.$wire.sendMessage();
                this.scrollToBottom();
            } finally {
                this.isSending = false;
                this.$nextTick(() => {
                    if (this.$refs.chatInput) this.$refs.chatInput.focus();
                });
            }
        },
        init() {
            this.$nextTick(() => {
                const el = this.$refs.chatBox;
                if (el) {
                    this.lastMsgCount = el.querySelectorAll('.chat-msg-appear').length;
                    this.scrollToBottom();

                    const observer = new MutationObserver(() => {
                        this.$nextTick(() => this.updateScrollPosition());
                    });
                    observer.observe(el, { childList: true });
                }
            });

            this.$watch('$wire.messages', () => {
                this.preserveScroll();
            });
            this.$watch('message', () => this.checkCommands());
            this.$watch('$wire.currentChannel', () => {
                this.lastMsgCount = 0;
                this.userWasAtBottom = true;
                this.scrollToBottom();
                this.checkCommands();
            });
            this.$watch('$wire.isOpen', (val) => {
                if (val) {
                    this.lastMsgCount = 0;
                    this.userWasAtBottom = true;
                    this.scrollToBottom();
                }
            });
            this.$watch('$wire.activeTooltipId', () => {
                this.preserveScroll();
            });
        }
    }"
    class="fixed bottom-20 lg:bottom-0 right-2 sm:right-4 m-2 sm:m-4 z-[9950] font-sans select-none"
    style="font-family: 'Cinzel', serif;"
>
    {{-- ========== CHAT WRAPPER ========== --}}
    <div class="relative flex flex-col w-80">
        {{-- ========== GLOBAL TOOLTIP (POPOVER) ========== --}}
        @if ($isOpen && $activeTooltipId && isset($tooltipData[$activeTooltipId]))
            @php
                $td = $tooltipData[$activeTooltipId];
                $avatarName = $td['avatar'] ?? 'plate.png';
                if (!str_contains($avatarName, '.')) {
                    $avatarName .= '.png';
                }
                $avatarUrl = asset('img/avatars/' . ltrim($avatarName, '/'));
            @endphp
            
            {{-- Mobile Centered Modal Backdrop --}}
            <div 
                class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/60 backdrop-blur-sm lg:hidden p-4 pointer-events-auto"
                @click.self="closeTooltip()"
            >
                <div
                    @click.outside="if ($wire.activeTooltipId) closeTooltip()"
                    class="relative rounded-xl border border-amber-700/60 shadow-2xl p-4 w-full max-w-[320px] text-left flex flex-col h-auto max-h-[80vh] overflow-y-auto shrink-0"
                    style="background: linear-gradient(160deg, rgba(15,7,2,0.98) 0%, rgba(40,18,4,0.98) 100%);"
                >
                    {{-- Close button --}}
                    <button @click="closeTooltip()" class="absolute top-2.5 right-3 text-amber-500 hover:text-amber-300 font-bold text-xl leading-none cursor-pointer z-10">&times;</button>

                    {{-- Character header --}}
                    <div class="mb-3 border-b border-amber-800/50 pb-2.5 flex items-center gap-3">
                        @if(!empty($td['avatar']))
                            <div class="w-12 h-12 rounded-lg border border-amber-600/50 overflow-hidden shrink-0 bg-stone-900 shadow-md">
                                <img src="{{ $avatarUrl }}" class="w-full h-full object-cover" alt="" onError="this.src='{{ asset('img/avatars/plate.png') }}'">
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            @if(!empty($td['title']))
                                <span class="text-[10px] text-purple-400 font-bold uppercase tracking-wider block truncate">[{{ $td['title'] }}]</span>
                            @endif
                            <p class="text-amber-300 font-bold text-sm truncate medieval-font">{{ $td['name'] }}</p>
                            @if(!empty($td['guild']))
                                <p class="text-amber-500/80 text-[11px] font-semibold truncate"><i class="fa-solid fa-shield-halved mr-1 text-amber-600"></i>{{ $td['guild'] }}</p>
                            @endif
                            <div class="flex gap-3 text-xs mt-0.5">
                                <span class="text-amber-500">Poz. <span class="text-amber-200 font-bold">{{ $td['level'] }}</span></span>
                                <span class="text-amber-500">CP: <span class="text-amber-200 font-bold">{{ number_format($td['combat_power']) }}</span></span>
                            </div>
                        </div>
                    </div>

                    {{-- Equipped items --}}
                    <p class="text-amber-600/80 text-xs font-semibold uppercase tracking-wider mb-2">Ekwipunek</p>
                    @if (count($td['equipped_items']) === 0)
                        <p class="text-amber-700/60 text-xs italic mb-3">Brak założonego ekwipunku</p>
                    @else
                        <div class="space-y-1 mb-3">
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
                                <div class="flex items-center justify-between text-xs py-0.5">
                                    <span class="{{ $rarityColor }} flex items-center gap-1.5 min-w-0 truncate">
                                        @if(!empty($ei['icon']))
                                            <img src="{{ route('assets.items', ['filename' => $ei['icon']]) }}" class="w-4 h-4 object-contain shrink-0" alt="">
                                        @endif
                                        <span class="truncate">
                                            {{ $ei['name'] }}
                                            @if(in_array($ei['type'] ?? '', ['weapon', 'armor', 'accessory']))
                                                <span class="text-emerald-400">+{{ $ei['upgrade_level'] ?? 0 }}</span>
                                            @endif
                                        </span>
                                    </span>
                                    <span class="text-amber-700 text-[10px] ml-1 shrink-0">{{ number_format($ei['combat_power']) }} CP</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Equipped Pet --}}
                    @if(isset($td['pet']) && $td['pet'])
                        <p class="text-amber-600/80 text-xs font-semibold uppercase tracking-wider mb-1 mt-1">Chowaniec</p>
                        @php
                            $petRarityColor = match($td['pet']['rarity'] ?? 'common') {
                                'uncommon'  => 'text-green-400',
                                'rare'      => 'text-blue-400',
                                'epic'      => 'text-purple-400',
                                'legendary' => 'text-amber-400',
                                default     => 'text-stone-300',
                            };
                        @endphp
                        <div class="flex items-center justify-between text-xs mb-3">
                            <span class="{{ $petRarityColor }} truncate max-w-[150px]">
                                <i class="fa-solid fa-paw mr-1"></i>{{ $td['pet']['name'] }}
                                <span class="text-amber-500/70 text-[10px] ml-1">Poz. {{ $td['pet']['level'] }}</span>
                            </span>
                            <span class="text-amber-700 text-[10px] ml-1">{{ number_format($td['pet']['combat_power']) }} CP</span>
                        </div>
                    @endif

                    {{-- Invite to Guild Button --}}
                    <button
                        @click="inviteToGuild('{{ $activeTooltipId }}')"
                        class="mt-2 w-full py-1.5 rounded bg-gradient-to-r from-amber-800 to-amber-900 border border-amber-700/50 hover:from-amber-700 hover:to-amber-800 text-amber-200 text-xs font-bold transition-colors cursor-pointer flex items-center justify-center gap-1.5"
                    >
                        <i class="fa-solid fa-user-plus"></i> Wyślij zaproszenie do gildii
                    </button>
                </div>
            </div>

            {{-- Desktop Absolute Popover --}}
            <div
                @click.outside="if ($wire.activeTooltipId) closeTooltip()"
                class="hidden lg:flex flex-col absolute right-full bottom-0 mr-3 w-80 max-h-[480px] h-auto overflow-y-auto rounded-xl border border-amber-700/60 shadow-2xl p-4 text-left z-[10000] pointer-events-auto shrink-0"
                style="background: linear-gradient(160deg, rgba(15,7,2,0.98) 0%, rgba(40,18,4,0.98) 100%);"
            >
                {{-- Close button --}}
                <button @click="closeTooltip()" class="absolute top-2.5 right-3 text-amber-500 hover:text-amber-300 font-bold text-xl leading-none cursor-pointer z-10">&times;</button>

                {{-- Arrow pointing right (desktop only) --}}
                <div class="absolute bottom-6 -right-1.5 w-3 h-3 rotate-45 bg-amber-900 border-t border-r border-amber-700/60"></div>

                {{-- Character header --}}
                <div class="mb-3 border-b border-amber-800/50 pb-2.5 flex items-center gap-3">
                    @if(!empty($td['avatar']))
                        <div class="w-12 h-12 rounded-lg border border-amber-600/50 overflow-hidden shrink-0 bg-stone-900 shadow-md">
                            <img src="{{ $avatarUrl }}" class="w-full h-full object-cover" alt="" onError="this.src='{{ asset('img/avatars/plate.png') }}'">
                        </div>
                    @endif
                    <div class="min-w-0 flex-1">
                        @if(!empty($td['title']))
                            <span class="text-[10px] text-purple-400 font-bold uppercase tracking-wider block truncate">[{{ $td['title'] }}]</span>
                        @endif
                        <p class="text-amber-300 font-bold text-sm truncate medieval-font">{{ $td['name'] }}</p>
                        @if(!empty($td['guild']))
                            <p class="text-amber-500/80 text-[11px] font-semibold truncate"><i class="fa-solid fa-shield-halved mr-1 text-amber-600"></i>{{ $td['guild'] }}</p>
                        @endif
                        <div class="flex gap-3 text-xs mt-0.5">
                            <span class="text-amber-500">Poz. <span class="text-amber-200 font-bold">{{ $td['level'] }}</span></span>
                            <span class="text-amber-500">CP: <span class="text-amber-200 font-bold">{{ number_format($td['combat_power']) }}</span></span>
                        </div>
                    </div>
                </div>

                {{-- Equipped items --}}
                <p class="text-amber-600/80 text-xs font-semibold uppercase tracking-wider mb-2">Ekwipunek</p>
                @if (count($td['equipped_items']) === 0)
                    <p class="text-amber-700/60 text-xs italic mb-3">Brak założonego ekwipunku</p>
                @else
                    <div class="space-y-1 mb-3">
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
                            <div class="flex items-center justify-between text-xs py-0.5">
                                <span class="{{ $rarityColor }} flex items-center gap-1.5 min-w-0 truncate">
                                    @if(!empty($ei['icon']))
                                        <img src="{{ route('assets.items', ['filename' => $ei['icon']]) }}" class="w-4 h-4 object-contain shrink-0" alt="">
                                    @endif
                                    <span class="truncate">
                                        {{ $ei['name'] }}
                                        @if(in_array($ei['type'] ?? '', ['weapon', 'armor', 'accessory']))
                                            <span class="text-emerald-400">+{{ $ei['upgrade_level'] ?? 0 }}</span>
                                        @endif
                                    </span>
                                </span>
                                <span class="text-amber-700 text-[10px] ml-1 shrink-0">{{ number_format($ei['combat_power']) }} CP</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Equipped Pet --}}
                @if(isset($td['pet']) && $td['pet'])
                    <p class="text-amber-600/80 text-xs font-semibold uppercase tracking-wider mb-1 mt-1">Chowaniec</p>
                    @php
                        $petRarityColor = match($td['pet']['rarity'] ?? 'common') {
                            'uncommon'  => 'text-green-400',
                            'rare'      => 'text-blue-400',
                            'epic'      => 'text-purple-400',
                            'legendary' => 'text-amber-400',
                            default     => 'text-stone-300',
                        };
                    @endphp
                    <div class="flex items-center justify-between text-xs mb-3">
                        <span class="{{ $petRarityColor }} truncate max-w-[150px]">
                            <i class="fa-solid fa-paw mr-1"></i>{{ $td['pet']['name'] }}
                            <span class="text-amber-500/70 text-[10px] ml-1">Poz. {{ $td['pet']['level'] }}</span>
                        </span>
                        <span class="text-amber-700 text-[10px] ml-1">{{ number_format($td['pet']['combat_power']) }} CP</span>
                    </div>
                @endif

                {{-- Invite to Guild Button --}}
                <button
                    @click="inviteToGuild('{{ $activeTooltipId }}')"
                    class="mt-2 w-full py-1.5 rounded bg-gradient-to-r from-amber-800 to-amber-900 border border-amber-700/50 hover:from-amber-700 hover:to-amber-800 text-amber-200 text-xs font-bold transition-colors cursor-pointer flex items-center justify-center gap-1.5"
                >
                    <i class="fa-solid fa-user-plus"></i> Wyślij zaproszenie do gildii
                </button>
            </div>
        @endif

    {{-- ========== CHAT WRAPPER ========== --}}
    <div class="flex flex-col w-80">
        {{-- ========== MINIMIZED BUBBLE ========== --}}
        @if (!$isOpen)
            <button
                wire:click="toggleChat"
                class="ml-auto flex items-center gap-2 bg-gradient-to-r from-amber-900/95 to-stone-900/95 border border-amber-700/60 rounded-full px-4 py-2 shadow-2xl hover:from-amber-800/95 transition-all duration-200 text-amber-200 font-bold text-sm cursor-pointer backdrop-blur-md"
            >
                <div class="relative flex items-center">
                    <span class="text-base"><i class="fa-solid fa-comments"></i></span>
                </div>
                <span>Czat</span>
                @if ($unreadGlobalCount > 0 || $unreadGuildCount > 0)
                    <div class="flex gap-1 ml-1">
                        @if ($unreadGlobalCount > 0)
                            <span class="bg-amber-600 text-white text-[10px] px-1.5 py-0.5 rounded-full" title="Globalny">{{ $unreadGlobalCount }}</span>
                        @endif
                        @if ($unreadGuildCount > 0)
                            <span class="bg-red-600 text-white text-[10px] px-1.5 py-0.5 rounded-full" title="Gildia">{{ $unreadGuildCount }}</span>
                        @endif
                    </div>
                @endif
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
                @php $activeChar = session('active_character') ? \App\Infrastructure\Persistence\Character::find(session('active_character')) : null; @endphp
                <div class="flex items-center gap-2">
                    <button wire:click="setChannel('global')" class="text-xs font-bold uppercase tracking-wider cursor-pointer {{ $currentChannel === 'global' ? 'text-amber-200 underline decoration-amber-500' : 'text-amber-600/70 hover:text-amber-400' }}">Globalny</button>
                    @if($activeChar && $activeChar->guild_id)
                    <span class="text-amber-800">|</span>
                    <button wire:click="setChannel('guild')" class="text-xs font-bold uppercase tracking-wider cursor-pointer flex items-center gap-1 {{ $currentChannel === 'guild' ? 'text-red-300 underline decoration-red-500' : 'text-amber-600/70 hover:text-red-400' }}">
                        <span>Gildia</span>
                    </button>
                    @endif
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 shadow-lg shadow-emerald-400/50 animate-pulse ml-1"></span>
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
                @scroll="handleScroll()"
                wire:ignore.self
                class="flex flex-col gap-1 overflow-y-auto px-3 py-2 scrollbar-thin"
                style="height: 260px; scrollbar-color: rgba(180,120,30,0.4) transparent;"
            >
                @if (count($messages) === 0)
                    <p class="text-amber-600/60 text-xs text-center mt-10 italic">Brak wiadomości. Bądź pierwszy!</p>
                @endif

                @foreach ($messages as $idx => $msg)
                    @if(($msg['channel'] ?? 'global') === $currentChannel)
                    <div
                        class="chat-msg-appear text-xs leading-relaxed break-words py-1 border-b border-amber-900/10"
                        wire:key="chat-msg-{{ $idx }}-{{ $msg['character_id'] }}"
                    >
                        {{-- Timestamp --}}
                        <span class="text-amber-700/60 font-mono text-[10px] mr-1.5 select-none shrink-0">{{ substr($msg['sent_at'], 0, 5) }}</span>

                        @if($msg['character_id'] === 'system')
                            <span class="text-yellow-500 font-bold">[{{ $msg['character_name'] }}]</span>
                            <span class="text-yellow-200 italic ml-1">{{ $msg['message'] }}</span>
                        @else
                            {{-- Nick + title + level inline wrapper --}}
                            <span class="inline space-x-0.5">
                                @if($msg['is_admin'] ?? false)
                                    <span class="text-[9px] text-red-400 font-extrabold bg-red-950/80 px-1 py-0.5 rounded border border-red-600/60 font-mono tracking-wider shadow-[0_0_8px_rgba(239,68,68,0.5)]">[A]</span>
                                @endif
                                @if(!empty($msg['title_prefix']))
                                    <span class="text-[10px] text-purple-400 font-bold uppercase tracking-wider" title="Tytuł">[{{ $msg['title_prefix'] }}]</span>
                                @endif
                                <span
                                    @click.prevent="openTooltip('{{ $msg['character_id'] }}')"
                                    class="font-bold cursor-pointer transition-colors hover:underline decoration-dotted 
                                    {{ ($msg['is_admin'] ?? false) ? 'text-red-500 font-extrabold hover:text-red-400 drop-shadow-[0_0_8px_rgba(239,68,68,0.8)] admin-glow' : (($msg['is_premium'] ?? false) ? 'text-yellow-400 drop-shadow-[0_0_8px_rgba(250,204,21,0.8)] premium-glow' : 'text-amber-400 hover:text-amber-200') }}"
                                >
                                    @if($msg['is_premium'] ?? false)
                                        <span class="inline-block relative">
                                            <span class="absolute -top-1 -right-1 text-[8px] animate-pulse">✨</span>
                                            {{ $msg['character_name'] }}
                                        </span>
                                    @else
                                        {{ $msg['character_name'] }}
                                    @endif
                                </span>
                                <span class="text-amber-600/70 text-[11px] font-semibold">[{{ $msg['character_level'] }}]</span>
                            </span>
                            <span class="text-stone-300 font-normal">: {{ $msg['message'] }}</span>
                        @endif
                    </div>
                    @endif
                @endforeach
            </div>

        {{-- ---- Input area ---- --}}
        @if (Auth::check() && session('active_character'))
            @if (Auth::user()->isMuted())
                @php
                    $muteSeconds = Auth::user()->getMuteRemainingSeconds();
                @endphp
                <div
                    x-data="{
                        secondsLeft: {{ $muteSeconds }},
                        timer: null,
                        formatTime(sec) {
                            if (sec <= 0) return '0s';
                            let d = Math.floor(sec / 86400);
                            let h = Math.floor((sec % 86400) / 3600);
                            let m = Math.floor((sec % 3600) / 60);
                            let s = sec % 60;
                            let res = '';
                            if (d > 0) res += d + 'd ';
                            if (h > 0 || d > 0) res += h + 'h ';
                            if (m > 0 || h > 0 || d > 0) res += m + 'm ';
                            res += s + 's';
                            return res.trim();
                        },
                        init() {
                            this.timer = setInterval(() => {
                                if (this.secondsLeft > 0) {
                                    this.secondsLeft--;
                                } else {
                                    clearInterval(this.timer);
                                }
                            }, 1000);
                        }
                    }"
                    class="relative border-t border-red-900/60 bg-red-950/60 px-3 py-2.5 text-center shadow-inner"
                >
                    <div class="flex items-center justify-center gap-1.5 text-xs font-extrabold text-red-300">
                        <i class="fa-solid fa-microphone-slash text-sm animate-pulse mr-1"></i>
                        <span>Zablokowano: <span x-text="formatTime(secondsLeft)" class="font-mono text-red-100 font-bold ml-1"></span></span>
                    </div>
                </div>
            @else
                <div class="relative border-t border-amber-800/40 px-2 py-2">
                    {{-- Autocomplete dropup --}}
                    <div x-show="showCommands" style="display: none;" class="absolute bottom-full left-0 w-full bg-stone-900 border border-amber-800/60 rounded-t-lg shadow-xl overflow-hidden z-[70] mb-1">
                        <template x-for="cmd in filteredCommands" :key="cmd.cmd">
                            <div @click="selectCommand(cmd.cmd)" class="px-3 py-2 border-b border-amber-900/30 hover:bg-amber-900/40 cursor-pointer flex justify-between items-center transition-colors">
                                <span class="text-amber-400 font-bold text-xs font-mono" x-text="cmd.cmd"></span>
                                <span class="text-stone-400 text-[10px]" x-text="cmd.desc"></span>
                            </div>
                        </template>
                    </div>

                    @error('newMessage')
                        <p class="text-red-400 text-xs mb-1 px-1">{{ $message }}</p>
                    @enderror
                    <form @submit.prevent="sendMsg()" class="flex gap-1">
                        <input
                            x-ref="chatInput"
                            wire:model="newMessage"
                            @keydown.tab.prevent="if(showCommands && filteredCommands.length > 0) selectCommand(filteredCommands[0].cmd)"
                            type="text"
                            maxlength="200"
                            placeholder="Napisz wiadomość…"
                            autocomplete="off"
                            :disabled="isSending"
                            class="flex-1 bg-stone-900/80 border border-amber-800/40 rounded-lg px-3 py-1.5 text-xs text-amber-100 placeholder-amber-700/60 focus:outline-none focus:border-amber-600/60 transition-colors disabled:opacity-50"
                        >
                        <button
                            type="submit"
                            :disabled="isSending"
                            class="shrink-0 bg-gradient-to-b from-amber-700 to-amber-900 hover:from-amber-600 hover:to-amber-800 text-amber-100 rounded-lg px-3 py-1.5 text-xs font-bold transition-all duration-150 hover:shadow-lg hover:shadow-amber-900/50 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed relative overflow-hidden"
                        >
                            {{-- Normal state --}}
                            <span x-show="!isSending" class="flex items-center">▶</span>
                            {{-- Sending state --}}
                            <span x-show="isSending" class="flex items-center">
                                <svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </span>
                        </button>
                    </form>
                </div>
            @endif
        @endif
    </div>
    @endif
</div>

<style>
    /* Chat message appear animation */
    @keyframes chatMsgAppear {
        from {
            opacity: 0;
            transform: translateY(8px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .chat-msg-appear {
        animation: chatMsgAppear 0.25s ease-out forwards;
    }
    
    @keyframes premiumGlow {
        0%, 100% { text-shadow: 0 0 4px rgba(250,204,21,0.5), 0 0 8px rgba(250,204,21,0.3); }
        50% { text-shadow: 0 0 8px rgba(250,204,21,0.9), 0 0 16px rgba(250,204,21,0.6); }
    }
    .premium-glow {
        animation: premiumGlow 2s ease-in-out infinite;
    }

    @keyframes adminGlow {
        0%, 100% { text-shadow: 0 0 4px rgba(239,68,68,0.6), 0 0 8px rgba(239,68,68,0.4); }
        50% { text-shadow: 0 0 8px rgba(239,68,68,1), 0 0 16px rgba(239,68,68,0.7); }
    }
    .admin-glow {
        animation: adminGlow 2s ease-in-out infinite;
    }
</style>
