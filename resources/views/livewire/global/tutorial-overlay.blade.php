<div>
    @if($isVisible)
        <div class="fixed inset-0 z-[100] flex items-end justify-center pointer-events-none p-2 sm:p-4 md:p-6">
            {{-- Ambient backdrop with 75% transparency to keep location visible in background --}}
            <div class="absolute inset-0 bg-black/75 pointer-events-auto backdrop-blur-[2px] transition-opacity"></div>

            <div class="relative z-10 w-full max-w-5xl pointer-events-auto flex flex-col-reverse sm:flex-row items-center sm:items-end pb-1 sm:pb-4">
                {{-- Captain Avatar with Ambient Gold Glow --}}
                <div class="flex-shrink-0 z-20 relative w-32 xs:w-40 sm:w-[320px] md:w-[380px] lg:w-[440px] -mt-3 sm:mt-0 -mb-2 sm:mb-0 drop-shadow-[0_15px_25px_rgba(0,0,0,0.95)]">
                    <div class="absolute inset-x-6 bottom-0 top-1/4 bg-amber-500/15 rounded-full blur-2xl sm:blur-3xl pointer-events-none"></div>
                    <img src="{{ asset('img/characters/captain_' . $randomCaptainIndex . '.png') }}" alt="Kapitan" class="w-full h-auto object-contain object-bottom relative z-10 max-h-[22vh] sm:max-h-none" />
                </div>

                {{-- Chat Bubble (Medieval Dark Parchment Scroll Frame) --}}
                <div class="relative bg-gradient-to-b from-[#1c1510]/95 via-[#150f0b]/95 to-[#0d0906]/95 border-2 border-amber-600/80 rounded-2xl sm:rounded-3xl p-3.5 sm:p-5 md:p-6 shadow-[0_20px_60px_rgba(0,0,0,0.95),0_0_35px_rgba(245,158,11,0.2)] text-amber-100 flex-1 w-full z-10 sm:mb-4 md:mb-6 lg:mb-8 sm:-ml-8 md:-ml-10 backdrop-blur-lg max-h-[68vh] sm:max-h-[80vh] flex flex-col justify-between overflow-y-auto custom-scrollbar">
                    {{-- Parchment texture pattern overlay --}}
                    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/wood-pattern.png')] opacity-15 pointer-events-none"></div>
                    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(245,158,11,0.15),transparent_70%)] pointer-events-none"></div>

                    {{-- Metallic Corner Rivets --}}
                    <div class="absolute top-2.5 left-2.5 sm:top-3 sm:left-3 w-2 sm:w-2.5 h-2 sm:h-2.5 rounded-full bg-gradient-to-br from-amber-400 via-amber-600 to-stone-900 border border-amber-950 shadow"></div>
                    <div class="absolute top-2.5 right-2.5 sm:top-3 sm:right-3 w-2 sm:w-2.5 h-2 sm:h-2.5 rounded-full bg-gradient-to-br from-amber-400 via-amber-600 to-stone-900 border border-amber-950 shadow"></div>
                    <div class="absolute bottom-2.5 left-2.5 sm:bottom-3 sm:left-3 w-2 sm:w-2.5 h-2 sm:h-2.5 rounded-full bg-gradient-to-br from-amber-400 via-amber-600 to-stone-900 border border-amber-950 shadow"></div>
                    <div class="absolute bottom-2.5 right-2.5 sm:bottom-3 sm:right-3 w-2 sm:w-2.5 h-2 sm:h-2.5 rounded-full bg-gradient-to-br from-amber-400 via-amber-600 to-stone-900 border border-amber-950 shadow"></div>

                    <div>
                        {{-- Header Banner --}}
                        <div class="font-bold uppercase tracking-widest mb-2.5 sm:mb-3 text-[11px] sm:text-xs border-b border-amber-700/60 pb-2 sm:pb-2.5 medieval-font flex items-center justify-between relative z-10">
                            <div class="flex items-center gap-2">
                                <span class="bg-gradient-to-r from-amber-200 via-amber-300 to-amber-500 bg-clip-text text-transparent drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)] text-xs sm:text-base">Kapitan Obozu</span>
                            </div>
                            <span class="text-[9px] sm:text-xs text-amber-400/60 font-mono tracking-normal bg-amber-950/60 px-2 sm:px-2.5 py-0.5 rounded-full border border-amber-800/40">Przewodnik</span>
                        </div>
                        
                        {{-- Text Content --}}
                        <div class="text-xs sm:text-base md:text-lg leading-relaxed mb-3 sm:mb-4 font-serif text-amber-100/90 drop-shadow-[0_1px_2px_rgba(0,0,0,0.9)] relative z-10">
                            @if($step == 1)
                                Witaj rekrucie! Widzę, że dopiero przybyłeś do naszego obozu. Nie stój tak bezczynnie! Musisz najpierw stworzyć swoją pierwszą postać, abyśmy mogli wyruszyć w bój i powstrzymać to szaleństwo. Kliknij w przycisk tworzenia nowej postaci!
                            @elseif($step == 2)
                                Świetnie! Teraz nadszedł czas na określenie Twoich predyspozycji. W naszej armii nie ma z góry ustalonych klas! To, czy będziesz walczył wręcz, czy z dystansu, czy też polegał na magii, zależy tylko od tego jak rozdasz swoje punkty atrybutów. Pamiętaj, każda decyzja kształtuje Twój styl walki. Wybieraj mądrze!
                            @elseif($step == 3)
                                Doskonale! Twoja postać jest gotowa do walki. Pora opuścić to spokojne miejsce i wkroczyć do prawdziwego świata! Wybierz swojego bohatera z listy, aby wejść do Głównego Obozu, gdzie rozpoczniesz swój trening.
                            @elseif($step == 4)
                                Witaj w Głównym Obozie, naszym centrum operacyjnym. Zanim w ogóle pomyślisz o wyruszeniu za mury miasta, musisz mieć czym walczyć! Przyjmij ten podstawowy ekwipunek na dobry początek.
                            @elseif($step == 5)
                                Świetnie. Teraz spójrz na listę dostępnych miejsc. Na początku musisz wejść w zakładkę Profil, by obejrzeć i założyć swoją nową broń. Spróbuj tam wejść!
                            @elseif($step == 6)
                                To jest Twój profil. Po lewej stronie widzisz założony ekwipunek, a na dole podsumowanie Twojej mocy bojowej. W lewym dolnym rogu jest centrum atrybutów – z każdym poziomem dostajesz tu 3 punkty do rozdania, które wpływają na statystyki w zakładce obok. Po prawej masz swój plecak. Najedź teraz na Zardzewiały Miecz, który Ci dałem, a następnie kliknij go, żeby go wyposażyć!
                            @elseif($step == 8)
                                Doskonale! Z bronią w ręku wyglądasz od razu groźniej. Przyjmij to doświadczenie w ramach mojego uznania. Teraz powróć do Głównego Obozu!
                            @elseif($step == 9)
                                Wygląda na to, że jesteśmy już w pełni gotowi, aby wyruszyć w Twoją pierwszą podróż poza mury! Najwyższy czas sprawdzić Twoje nowe wyposażenie w walce. Wybierz zakładkę "Przygoda" z listy miejsc.
                            @elseif($step == 10)
                                To jest centrum wypraw. Znajdziesz tu listę wszystkich map dostępnych w naszej krainie. Odblokowują się one wraz z Twoim poziomem doświadczenia. Pamiętaj, każda mapa skrywa potężnego World Bossa - jeśli zbierze się was wystarczająco dużo, możecie zdobyć niezwykłe łupy! Możesz także podejrzeć dokładną listę przeciwników oraz to, co z nich wypada. Zwróć też uwagę na zakładkę Lochów - to zamknięte instancje z cennymi nagrodami, wymagające specjalnych kluczy. Na razie jednak nie zapędzaj się za daleko. Kliknij pierwszą mapę (dla poziomów 0-15) i wyrusz w bój!
                            @elseif($step == 12)
                                Znalazłeś pierwszego przeciwnika. Pokaż na co cię stać! Kiedy będziesz gotów, kliknij "Rozpocznij Walkę". Na czas tej pierwszej potyczki tryb automatyczny został zablokowany, żebyś mógł zobaczyć przebieg walki.
                            @elseif($step == 13)
                                Znakomita robota! Gratuluję wygranej. W nagrodę za pomyślne ukończenie pierwszego starcia otrzymujesz ten hełm. Możesz zostać i powalczyć tutaj dłużej, by zdobyć trochę doświadczenia i złota, albo wrócić. Będę czekać na Ciebie w mieście!
                            @elseif($step == 14)
                                Witaj z powrotem! Cieszę się, że wróciliście cali i zdrowi, nie każdy miał to szczęście... Podczas walki zdobyłeś doświadczenie i nowy poziom. Pora rozdać punkty atrybutów! Wejdź w swój profil, rozdaj zdobyte punkty umiejętności i wróć do mnie, gdy skończysz.
                            @elseif($step == 16)
                                Świetnie sobie poradziłeś z atrybutami! Gratuluję samodzielności. Za twoje zaangażowanie i szybkie postępy wręczam Ci 150 sztuk złota. Przyda Ci się na dalszą drogę!
                            @elseif($step == 17)
                                Teraz, kiedy już potrafisz walczyć i zarządzać swoim rozwojem, pora poznać mieszkańców naszego miasta. Na początek zabiorę Cię do naszego miejscowego Brońmistrza. Przejdźmy tam!
                            @elseif($step == 18)
                                Witaj u Brońmistrza! To tutaj możesz kupować nowe bronie, sprzedawać zbędny balast, którego nie chcesz wystawiać na Targowisku, a co najważniejsze - ulepszać swój ekwipunek. Pamiętaj, że każdy przedmiot można ulepszyć od poziomu +0 aż do +9 w Kuźni Ulepszeń!
                            @elseif($step == 19)
                                Widzę, że zdobyłeś trochę złota. Pora wyposażyć się w coś lepszego niż ten Zardzewiały Miecz. {{ auth()->user()->character?->name ?? 'Wojowniku' }}, kup u Brońmistrza nową broń - znacznie ułatwi Ci to początkowe potyczki!
                            @elseif($step == 21)
                                Wspaniale! Wygląda na to, że jesteś już w pełni gotowy, by samodzielnie przetrwać w tym brutalnym świecie. Mój wstępny trening dobiegł końca, jednak jestem pewien, że spotkamy się jeszcze w przyszłości. Na koniec, przyjmij ode mnie tę Skórzaną Zbroję. Niech chroni Cię przed atakami! Powodzenia!
                            @elseif($step == 23)
                                Witaj po przerwie! Widzę, że od naszej ostatniej rozmowy nabrałeś sporo doświadczenia. W mieście pojawiła się nowa Tablica Wyzwań. Odwiedź ją, gdy tylko będziesz gotowy!
                            @elseif($step == 24)
                                Witaj przy Tablicy Wyzwań! To miejsce, gdzie mieszkańcy i gildie wywieszają różne zadania. Możesz tu zdobyć dodatkowe złoto, doświadczenie, a czasem nawet cenne przedmioty. Podejdź i odbierz swoją pierwszą misję!
                            @elseif($step == 26)
                                Świetnie! Po prawej stronie widoczne są informacje o Twojej bieżącej misji oraz postęp w jej realizacji. Teraz udaj się w świat, wykonaj misję i powróć do tego okna, kiedy skończysz!
                            @elseif($step == 27)
                                Wspaniale! Wykonałeś zadanie w stu procentach. W ten sposób pomagasz mieszkańcom i zdobywasz zasoby na dalsze wyprawy. Teraz odbierz swoją zasłużoną nagrodę!
                            @elseif($step == 29)
                                Dobra robota! Odbieranie wyzwań to świetny sposób na zarobek. Ale to nie wszystko! Zwróć uwagę na zakładkę "Osiągnięcia Bohatera". Przejdź tam teraz.
                            @elseif($step == 30)
                                W tym miejscu możesz śledzić swoje wielkie czyny! Za wypełnianie osiągnięć zdobędziesz dodatkowe złoto, doświadczenie, przedmioty oraz specjalne tytuły, które możesz wyposażyć w swoim profilu. Powodzenia w dalszym rozwoju!
                            @elseif($step == 31)
                                Witaj z powrotem w obozie! Kolejnym mieszkańcem naszej osady jest Czarodziej. Posiada on wiedzę o starożytnych zaklęciach, które mogą nasycić Twój ekwipunek potężną magią. Odwiedź go, aby poznać tajniki zaklinania przedmiotów!
                            @elseif($step == 32)
                                Witaj u Czarodzieja! To tutaj możesz nasycać swoje bronie, pancerze i biżuterię magicznymi właściwościami. Każde udane zaklinanie dodaje nową potężną statystykę do Twojego przedmiotu. Wybierz przedmiot ze swojego ekwipunku i zaklnij go z sukcesem!
                            @elseif($step == 33 || $step == 34)
                                Niesamowite! Przedmiot został pomyślnie nasycony magią! Jak widzisz, czary potrafią znacząco zwiększyć Twoją moc bojową. Przyjmij tę nagrodę i wykorzystuj wiedzę Czarodzieja w swoich kolejnych przygodach!
                            @endif
                        </div>

                        @php
                            $voiceFile = null;
                            if (file_exists(storage_path('app/public/voice/tutorial-' . $step . '.mp3'))) {
                                $voiceFile = 'tutorial-' . $step . '.mp3';
                            } elseif (file_exists(storage_path('app/public/voice/tutorial-' . ($step - 1) . '.mp3'))) {
                                $voiceFile = 'tutorial-' . ($step - 1) . '.mp3';
                            } elseif (auth()->user()?->game_stage && file_exists(storage_path('app/public/voice/tutorial-' . auth()->user()->game_stage . '.mp3'))) {
                                $voiceFile = 'tutorial-' . auth()->user()->game_stage . '.mp3';
                            }
                        @endphp

                        @if($voiceFile)
                            <audio autoplay class="hidden">
                                <source src="{{ asset('storage/voice/' . $voiceFile) }}" type="audio/mpeg">
                            </audio>
                        @endif
                    </div>

                    {{-- Rewards in 1 Row Layout --}}
                    @if($rewardItem || $rewardXp > 0 || $rewardGold > 0)
                        <div class="my-2 sm:my-3 flex flex-wrap sm:flex-nowrap gap-2 sm:gap-3 items-stretch relative z-10">
                            @if($rewardItem)
                                <div x-data="{ openTooltip: false }" class="flex-1 min-w-[140px] sm:min-w-[180px] bg-gradient-to-r from-[#241a13] to-[#18110b] border border-amber-500/70 rounded-xl p-2 sm:p-2.5 flex items-center justify-between transition-all relative shadow-md" :class="{ 'animate-pulse': !openTooltip }">
                                    <div class="flex items-center space-x-2.5 sm:space-x-3 cursor-help w-full" @mouseenter="openTooltip = true" @mouseleave="openTooltip = false" @click="openTooltip = true">
                                        <div class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-br from-stone-800 to-stone-950 border border-amber-500 rounded-lg flex items-center justify-center shadow-inner shrink-0 text-amber-400 font-bold text-[9px] sm:text-[10px]">
                                            @if($rewardItem->icon)
                                                <img src="{{ route('assets.items', ['filename' => $rewardItem->icon]) }}" class="w-full h-full object-contain drop-shadow-lg p-0.5" alt="{{ $rewardItem->name }}">
                                            @else
                                                ITEM
                                            @endif
                                        </div>
                                        <div class="text-left pr-1 min-w-0 flex-1">
                                            <div class="text-amber-300 font-bold text-[11px] sm:text-xs md:text-sm medieval-font truncate">{{ $rewardItem->name }}</div>
                                            <div class="text-amber-200/60 text-[9px] sm:text-[10px] italic truncate">Nagroda rzeczowa</div>
                                        </div>

                                        {{-- Info Box Tooltip --}}
                                        <div x-show="openTooltip" x-transition.opacity class="fixed inset-0 sm:absolute sm:inset-auto sm:bottom-full sm:left-1/2 sm:-translate-x-1/2 sm:mb-3 sm:w-64 z-[200] sm:z-50 flex items-center justify-center sm:block bg-black/80 sm:bg-transparent backdrop-blur-sm sm:backdrop-blur-none p-4 sm:p-0 cursor-default" style="display: none;" @click.stop="openTooltip = false">
                                            <div class="bg-stone-900 border-2 border-amber-500/80 p-3.5 rounded-xl shadow-2xl relative text-left font-sans w-full max-w-xs sm:w-auto sm:max-w-none" @click.stop>
                                                <button @click="openTooltip = false" class="absolute top-2 right-2 text-stone-400 hover:text-white text-lg font-bold sm:hidden">✕</button>
                                                
                                                <div class="mb-2 border-b border-amber-800/60 pb-1.5 pr-6">
                                                    <p class="font-bold text-amber-300 text-base medieval-font">{{ $rewardItem->name }}</p>
                                                </div>
                                                <p class="text-stone-400 mb-1 text-xs">Slot: <span class="text-amber-100 font-semibold">{{ ucfirst($rewardItem->slot ?? 'Brak') }}</span></p>
                                                <p class="text-stone-400 mb-2 text-xs">Wymagany Poz: <span class="text-amber-100 font-semibold">{{ $rewardItem->level_requirement }}</span></p>
                                                
                                                @if(count($rewardItem->base_stats ?? []) > 0)
                                                    <div class="mt-2 text-emerald-400 border-t border-amber-800/60 pt-1.5 space-y-1 text-xs font-mono">
                                                        @foreach($rewardItem->base_stats ?? [] as $stat => $val)
                                                            <div class="flex justify-between">
                                                                <span class="capitalize">{{ str_replace('_', ' ', $stat) }}</span>
                                                                <span class="font-bold">+{{ $val }}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="hidden sm:block absolute -bottom-2 left-1/2 -translate-x-1/2 w-3.5 h-3.5 bg-stone-900 border-b border-r border-amber-500 transform rotate-45"></div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($rewardXp > 0)
                                <div class="flex-1 min-w-[110px] sm:min-w-[130px] bg-gradient-to-r from-[#0d2218] via-[#091811] to-[#050f0b] border border-emerald-500/70 rounded-xl p-2 sm:p-2.5 flex items-center gap-2 sm:gap-3 shadow-md animate-[pulse_2s_ease-in-out_infinite]">
                                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-br from-emerald-950 to-stone-950 border border-emerald-400 rounded-lg flex items-center justify-center text-[10px] sm:text-xs text-emerald-400 font-bold medieval-font shrink-0">
                                        XP
                                    </div>
                                    <div class="text-left min-w-0">
                                        <div class="text-emerald-400 font-bold text-xs sm:text-sm medieval-font truncate">+{{ $rewardXp }} XP</div>
                                        <div class="text-emerald-200/70 text-[9px] sm:text-[10px] italic leading-tight truncate">Doświadczenie</div>
                                    </div>
                                </div>
                            @endif

                            @if($rewardGold > 0)
                                <div class="flex-1 min-w-[110px] sm:min-w-[130px] bg-gradient-to-r from-[#261f0c] via-[#1a1508] to-[#100d04] border border-yellow-500/70 rounded-xl p-2 sm:p-2.5 flex items-center gap-2 sm:gap-3 shadow-md animate-[pulse_2s_ease-in-out_infinite]">
                                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-br from-amber-950 to-stone-950 border border-yellow-400 rounded-lg flex items-center justify-center text-[10px] sm:text-xs text-yellow-400 font-bold medieval-font shrink-0">
                                        GOLD
                                    </div>
                                    <div class="text-left min-w-0">
                                        <div class="text-yellow-400 font-bold text-xs sm:text-sm medieval-font truncate">+{{ $rewardGold }} Złota</div>
                                        <div class="text-yellow-200/70 text-[9px] sm:text-[10px] italic leading-tight truncate">Złote monety</div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="flex justify-end relative z-10 mt-2 sm:mt-3 pt-2 border-t border-amber-900/40">
                        <button wire:click="nextStep" onclick="new Audio('{{ asset('storage/sound/A_short,_satisfying__%234-1783930877243.mp3') }}').play()" onmouseenter="new Audio('{{ asset('storage/sound/A_very_subtle,_soft__%232-1783932173771.mp3') }}').play()" onmouseleave="new Audio('{{ asset('storage/sound/A_very_subtle,_soft__%232-1783932173771.mp3') }}').play()" class="w-full sm:w-auto bg-gradient-to-r from-amber-600 via-amber-500 to-yellow-600 hover:from-amber-500 hover:via-yellow-400 hover:to-amber-500 text-stone-950 font-extrabold py-2 sm:py-2.5 px-5 sm:px-7 rounded-xl shadow-[0_0_20px_rgba(245,158,11,0.4)] hover:shadow-[0_0_30px_rgba(245,158,11,0.7)] hover:scale-105 active:scale-95 transition-all duration-200 border-2 border-amber-300 medieval-font text-xs sm:text-base tracking-wider uppercase flex items-center justify-center">
                            Tak jest, Kapitanie!
                        </button>
                    </div>
                    
                    {{-- Speech bubble tail pointing left (Tablet / Desktop) --}}
                    <div class="absolute w-0 h-0 border-r-[24px] lg:border-r-[26px] border-r-[#1c1510] border-y-[14px] lg:border-y-[16px] border-y-transparent -left-[24px] lg:-left-[26px] top-8 lg:top-10 hidden sm:block z-20"></div>
                    <div class="absolute w-0 h-0 border-r-[28px] lg:border-r-[30px] border-r-amber-600/80 border-y-[17px] lg:border-y-[19px] border-y-transparent -left-[28px] lg:-left-[30px] top-[30px] lg:top-[39px] hidden sm:block z-10"></div>

                    {{-- Speech bubble tail pointing down (Mobile Phone) --}}
                    <div class="absolute w-0 h-0 border-t-[14px] border-t-[#1c1510] border-x-[10px] border-x-transparent left-12 -bottom-[14px] sm:hidden z-20"></div>
                    <div class="absolute w-0 h-0 border-t-[17px] border-t-amber-600/80 border-x-[12px] border-x-transparent left-[46px] -bottom-[17px] sm:hidden z-10"></div>
                </div>
            </div>
        </div>
    @endif
</div>
