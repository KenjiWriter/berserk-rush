<div class="min-h-screen bg-stone-950 text-amber-100 relative py-8 px-3 sm:px-6 font-sans overflow-x-hidden selection:bg-amber-800 selection:text-amber-100">
    {{-- Dark Fantasy Ambient Lighting Overlay --}}
    <div class="fixed inset-0 bg-gradient-to-b from-stone-950/80 via-amber-950/30 to-stone-950/95 pointer-events-none"></div>
    <div class="fixed inset-0 bg-[radial-gradient(circle_at_center,transparent_0%,rgba(12,10,9,0.85)_100%)] pointer-events-none"></div>

    <div class="w-full max-w-3xl mx-auto relative z-10">
        {{-- Wood Board Decorative Header --}}
        <div class="relative bg-gradient-to-r from-stone-900 via-amber-950/90 to-stone-900 border-2 border-amber-800/70 rounded-2xl p-5 sm:p-6 mb-8 shadow-[0_10px_35px_rgba(0,0,0,0.8)] backdrop-blur-md overflow-hidden">
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/wood-pattern.png')] opacity-20"></div>

            <div class="absolute top-3 left-3 w-3 h-3 rounded-full bg-gradient-to-br from-amber-500 to-stone-800 border border-amber-950 shadow"></div>
            <div class="absolute top-3 right-3 w-3 h-3 rounded-full bg-gradient-to-br from-amber-500 to-stone-800 border border-amber-950 shadow"></div>
            <div class="absolute bottom-3 left-3 w-3 h-3 rounded-full bg-gradient-to-br from-amber-500 to-stone-800 border border-amber-950 shadow"></div>
            <div class="absolute bottom-3 right-3 w-3 h-3 rounded-full bg-gradient-to-br from-amber-500 to-stone-800 border border-amber-950 shadow"></div>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 relative z-10">
                <div class="flex items-center gap-3.5 text-center sm:text-left">
                    <div class="w-13 h-13 sm:w-14 sm:h-14 rounded-2xl bg-gradient-to-br from-amber-700 via-amber-900 to-stone-950 border-2 border-amber-500/60 flex items-center justify-center text-2xl shadow-[0_0_20px_rgba(245,158,11,0.4)] shrink-0">
                        <i class="fa-solid fa-gear text-amber-400"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-bold bg-gradient-to-b from-amber-100 via-amber-300 to-amber-500 bg-clip-text text-transparent medieval-font drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)]">
                            Ustawienia Gry
                        </h1>
                        <p class="text-xs sm:text-sm text-amber-300/70 font-medium tracking-wide">
                            Dźwięk i powiadomienia
                        </p>
                    </div>
                </div>

                <button wire:click="backToHub" @click="$dispatch('location-leave', { text: 'Podróż do Miasta...', icon: 'fa-solid fa-archway', url: '{{ route('city.hub', $character->id) }}' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                        class="w-full sm:w-auto bg-gradient-to-r from-stone-900 to-amber-950/80 hover:from-amber-900/80 hover:to-amber-900 text-amber-200 border border-amber-700/60 hover:border-amber-400/90 px-5 py-2.5 rounded-xl font-bold shadow-lg transition-all duration-200 flex items-center justify-center gap-2 medieval-font text-sm hover:scale-105 active:scale-95 group">
                    <i class="fa-solid fa-archway text-amber-400 transform group-hover:-translate-x-1 transition-transform"></i> Powrót do miasta
                </button>
            </div>
        </div>

        {{-- Settings Card --}}
        <div x-data="{
                volumes: {
                    sfx: parseInt(localStorage.getItem('berserk_sfx_volume') ?? '50'),
                    levelup: parseInt(localStorage.getItem('berserk_levelup_volume') ?? '50'),
                    combat: parseInt(localStorage.getItem('berserk_combat_volume') ?? '50')
                },
                notifyQuestAchievement: localStorage.getItem('berserk_notify_quest_achievement') !== 'false',
                setVolume(category, value) {
                    this.volumes[category] = value;
                    localStorage.setItem('berserk_' + category + '_volume', value);
                    $dispatch('settings-volume-changed', { category, value });
                },
                toggleNotifications() {
                    this.notifyQuestAchievement = !this.notifyQuestAchievement;
                    localStorage.setItem('berserk_notify_quest_achievement', this.notifyQuestAchievement ? 'true' : 'false');
                }
            }"
            class="bg-gradient-to-b from-stone-900/95 via-stone-950/90 to-stone-900/95 border-2 border-amber-800/60 rounded-2xl p-5 sm:p-8 shadow-2xl backdrop-blur-md space-y-8">

            {{-- Section: Volume --}}
            <div>
                <h2 class="text-lg font-bold text-amber-300 mb-5 medieval-font flex items-center gap-2">
                    <i class="fa-solid fa-volume-high text-amber-400"></i> Głośność Dźwięku
                </h2>

                <div class="space-y-6">
                    {{-- General SFX Volume --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-semibold text-amber-100">Efekty Dźwiękowe</label>
                            <span class="text-xs text-amber-400 font-bold" x-text="volumes.sfx + '%'"></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="range" min="0" max="100" step="5"
                                   x-model.number="volumes.sfx"
                                   @input="setVolume('sfx', volumes.sfx)"
                                   class="flex-1 h-2 rounded-full bg-stone-800 border border-amber-900/60 accent-amber-500 cursor-pointer">
                            <button type="button" @click="setVolume('sfx', volumes.sfx); $dispatch('play-audio', { type: 'hover' })"
                                    class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wide bg-gradient-to-b from-amber-800 to-amber-950 border border-amber-600/60 text-amber-200 hover:border-amber-400 transition-colors">
                                Testuj
                            </button>
                        </div>
                    </div>

                    {{-- Level Up Volume --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-semibold text-amber-100">Dźwięk Awansu Poziomu</label>
                            <span class="text-xs text-amber-400 font-bold" x-text="volumes.levelup + '%'"></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="range" min="0" max="100" step="5"
                                   x-model.number="volumes.levelup"
                                   @input="setVolume('levelup', volumes.levelup)"
                                   class="flex-1 h-2 rounded-full bg-stone-800 border border-amber-900/60 accent-amber-500 cursor-pointer">
                            <button type="button" @click="setVolume('levelup', volumes.levelup); $dispatch('play-audio', { type: 'levelup' })"
                                    class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wide bg-gradient-to-b from-amber-800 to-amber-950 border border-amber-600/60 text-amber-200 hover:border-amber-400 transition-colors">
                                Testuj
                            </button>
                        </div>
                    </div>

                    {{-- Combat Volume --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-semibold text-amber-100">Dźwięki Walki</label>
                            <span class="text-xs text-amber-400 font-bold" x-text="volumes.combat + '%'"></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="range" min="0" max="100" step="5"
                                   x-model.number="volumes.combat"
                                   @input="setVolume('combat', volumes.combat)"
                                   class="flex-1 h-2 rounded-full bg-stone-800 border border-amber-900/60 accent-amber-500 cursor-pointer">
                            <button type="button" @click="setVolume('combat', volumes.combat); $dispatch('play-audio', { type: 'combat' })"
                                    class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wide bg-gradient-to-b from-amber-800 to-amber-950 border border-amber-600/60 text-amber-200 hover:border-amber-400 transition-colors">
                                Testuj
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t border-amber-900/40"></div>

            {{-- Section: Notifications --}}
            <div>
                <h2 class="text-lg font-bold text-amber-300 mb-5 medieval-font flex items-center gap-2">
                    <i class="fa-solid fa-bell text-amber-400"></i> Powiadomienia
                </h2>

                <label class="flex items-center justify-between gap-4 p-4 rounded-xl bg-stone-900/80 border border-amber-900/50 cursor-pointer select-none">
                    <div>
                        <span class="block text-sm font-semibold text-amber-100">Osiągnięcia i Questy</span>
                        <span class="block text-xs text-amber-400/70 mt-0.5">Wyskakujące powiadomienia o postępie misji i osiągnięć podczas walki</span>
                    </div>
                    <span class="relative inline-flex items-center shrink-0 w-12 h-6" @click="toggleNotifications()">
                        <span class="absolute inset-0 rounded-full transition-colors duration-200 border"
                              :class="notifyQuestAchievement ? 'bg-gradient-to-r from-amber-700 to-amber-500 border-amber-400' : 'bg-stone-800 border-stone-700'"></span>
                        <span class="absolute left-0.5 top-0.5 w-5 h-5 rounded-full bg-stone-100 shadow transition-transform duration-200"
                              :class="notifyQuestAchievement ? 'translate-x-6' : 'translate-x-0'"></span>
                    </span>
                </label>
            </div>

            {{-- Section: Skrót do Ekranu Głównego (tylko widok mobilny) --}}
            <div class="sm:hidden"
                 x-data="{
                    showModal: false,
                    isStandalone: false,
                    platform: 'other', // 'ios' | 'android' | 'other'
                    browserOk: true,   // false = np. Chrome/Firefox na iOS (trzeba otworzyć w Safari)
                    deferredPrompt: null,
                    canNativeInstall: false,
                    init() {
                        const ua = navigator.userAgent || '';
                        const isIos = /iphone|ipad|ipod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
                        const isAndroid = /android/i.test(ua);

                        this.isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

                        if (isIos) {
                            this.platform = 'ios';
                            this.browserOk = /safari/i.test(ua) && !/crios|fxios|edgios|opios/i.test(ua);
                        } else if (isAndroid) {
                            this.platform = 'android';
                        }

                        window.addEventListener('beforeinstallprompt', (e) => {
                            e.preventDefault();
                            this.deferredPrompt = e;
                            this.canNativeInstall = true;
                        });
                    },
                    async installNative() {
                        if (!this.deferredPrompt) return;
                        this.deferredPrompt.prompt();
                        await this.deferredPrompt.userChoice;
                        this.deferredPrompt = null;
                        this.canNativeInstall = false;
                        this.showModal = false;
                    }
                 }"
                 x-show="!isStandalone" x-cloak>
                <div class="border-t border-amber-900/40 mb-8"></div>

                <h2 class="text-lg font-bold text-amber-300 mb-5 medieval-font flex items-center gap-2">
                    <i class="fa-solid fa-mobile-screen-button text-amber-400"></i> Skrót na Ekranie Głównym
                </h2>

                <div class="p-4 rounded-xl bg-stone-900/80 border border-amber-900/50 space-y-3">
                    <p class="text-xs text-amber-400/70">
                        Dodaj Berserk Rush do ekranu głównego telefonu, aby uruchamiać grę jednym dotknięciem, jak zwykłą aplikację.
                    </p>
                    <button type="button" @click="showModal = true" @click.stop="$dispatch('play-audio', { type: 'hover' })"
                            class="w-full bg-gradient-to-r from-amber-700 to-amber-600 hover:from-amber-600 hover:to-amber-500 text-stone-950 font-bold py-2.5 rounded-xl shadow-lg transition-all duration-200 flex items-center justify-center gap-2 medieval-font text-sm active:scale-95">
                        <i class="fa-solid fa-square-plus"></i> Dodaj skrót do ekranu głównego
                    </button>
                </div>

                {{-- Modal z instrukcją --}}
                <div x-show="showModal" x-cloak x-transition.opacity
                     class="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center bg-black/80 backdrop-blur-sm p-0 sm:p-4"
                     @click.self="showModal = false">
                    <div class="w-full sm:max-w-sm bg-gradient-to-b from-stone-900 to-stone-950 border-t-2 sm:border-2 border-amber-800/70 rounded-t-2xl sm:rounded-2xl p-5 shadow-2xl">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-base font-bold text-amber-300 medieval-font flex items-center gap-2">
                                <i class="fa-solid fa-square-plus text-amber-400"></i> Dodaj skrót
                            </h3>
                            <button type="button" @click="showModal = false" class="text-amber-400/70 hover:text-amber-200 text-lg leading-none">✕</button>
                        </div>

                        {{-- iOS + Safari --}}
                        <template x-if="platform === 'ios' && browserOk">
                            <ol class="space-y-3 text-sm text-amber-100">
                                <li class="flex items-start gap-3">
                                    <span class="shrink-0 w-6 h-6 rounded-full bg-amber-600 text-stone-950 font-bold text-xs flex items-center justify-center">1</span>
                                    <span>Stuknij ikonę <i class="fa-solid fa-arrow-up-from-bracket text-amber-400 mx-1"></i> <strong>Udostępnij</strong> na dolnym pasku Safari.</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="shrink-0 w-6 h-6 rounded-full bg-amber-600 text-stone-950 font-bold text-xs flex items-center justify-center">2</span>
                                    <span>Przewiń listę i wybierz <strong>„Dodaj do ekranu początkowego”</strong>.</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="shrink-0 w-6 h-6 rounded-full bg-amber-600 text-stone-950 font-bold text-xs flex items-center justify-center">3</span>
                                    <span>Stuknij <strong>„Dodaj”</strong> w prawym górnym rogu.</span>
                                </li>
                            </ol>
                        </template>

                        {{-- iOS + inna przeglądarka (Chrome/Firefox/Edge na iOS) --}}
                        <template x-if="platform === 'ios' && !browserOk">
                            <p class="text-sm text-amber-100">
                                Na iOS dodawanie skrótu do ekranu głównego działa tylko w przeglądarce <strong>Safari</strong>.
                                Otwórz tę stronę w Safari, a następnie skorzystaj z ikony <i class="fa-solid fa-arrow-up-from-bracket text-amber-400 mx-1"></i> <strong>Udostępnij</strong> → <strong>„Dodaj do ekranu początkowego”</strong>.
                            </p>
                        </template>

                        {{-- Android --}}
                        <template x-if="platform === 'android'">
                            <div class="space-y-3">
                                <template x-if="canNativeInstall">
                                    <button type="button" @click="installNative()"
                                            class="w-full bg-gradient-to-r from-emerald-700 to-emerald-600 hover:from-emerald-600 hover:to-emerald-500 text-white font-bold py-2.5 rounded-xl shadow-lg transition-all duration-200 flex items-center justify-center gap-2 text-sm active:scale-95 mb-1">
                                        <i class="fa-solid fa-download"></i> Zainstaluj jednym dotknięciem
                                    </button>
                                </template>
                                <ol class="space-y-3 text-sm text-amber-100">
                                    <li class="flex items-start gap-3">
                                        <span class="shrink-0 w-6 h-6 rounded-full bg-amber-600 text-stone-950 font-bold text-xs flex items-center justify-center">1</span>
                                        <span>Stuknij ikonę menu <i class="fa-solid fa-ellipsis-vertical text-amber-400 mx-1"></i> w prawym górnym rogu przeglądarki.</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <span class="shrink-0 w-6 h-6 rounded-full bg-amber-600 text-stone-950 font-bold text-xs flex items-center justify-center">2</span>
                                        <span>Wybierz <strong>„Dodaj do ekranu głównego”</strong> lub <strong>„Zainstaluj aplikację”</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <span class="shrink-0 w-6 h-6 rounded-full bg-amber-600 text-stone-950 font-bold text-xs flex items-center justify-center">3</span>
                                        <span>Potwierdź, stukając <strong>„Dodaj”</strong>.</span>
                                    </li>
                                </ol>
                            </div>
                        </template>

                        {{-- Inne / nierozpoznane --}}
                        <template x-if="platform === 'other'">
                            <p class="text-sm text-amber-100">
                                Otwórz menu swojej przeglądarki i poszukaj opcji <strong>„Dodaj do ekranu głównego”</strong> lub <strong>„Zainstaluj aplikację”</strong>.
                            </p>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
