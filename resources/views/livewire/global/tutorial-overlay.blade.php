<div>
    @if($isVisible)
        <div class="fixed inset-0 z-[100] flex items-end justify-center pointer-events-none p-4 sm:p-0">
            {{-- Darken background --}}
            <div class="absolute inset-0 bg-slate-900/80 pointer-events-auto backdrop-blur-sm transition-opacity"></div>
            
            <div class="relative z-10 w-full max-w-6xl pointer-events-auto flex flex-col sm:flex-row items-end">
                {{-- Captain Avatar --}}
                <div class="flex-shrink-0 z-20 relative w-64 sm:w-[450px] lg:w-[550px] -mb-2 sm:mb-0">
                    <img src="{{ asset('img/characters/captian.png') }}" alt="Kapitan" class="w-full h-auto object-contain object-bottom drop-shadow-[0_15px_15px_rgba(0,0,0,0.9)]" />
                </div>

                {{-- Chat Bubble --}}
                <div class="bg-amber-50 border-4 border-amber-700 rounded-3xl p-6 sm:p-8 shadow-2xl relative text-amber-900 flex-1 w-full z-10 mt-4 sm:mt-0 sm:mb-[25vh] sm:-ml-12">
                    <div class="font-bold text-amber-800 uppercase tracking-widest mb-3 text-sm border-b-2 border-amber-300 pb-2 medieval-font flex items-center">
                        <span class="text-xl mr-2">🎖️</span> Kapitan Obozu
                    </div>
                    
                    <div class="text-lg sm:text-xl leading-relaxed mb-8 font-medium">
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
                            Widzę, że zdobyłeś trochę złota. Pora wyposażyć się w coś lepszego niż ten Zardzewiały Miecz. {{ auth()->user()->character?->name ?? 'Wojowniku' }}, kup u Brońmistrza Miecz Nowicjusza - znacznie ułatwi Ci to początkowe potyczki!
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
                        @endif
                    </div>

                    @if(file_exists(storage_path('app/public/voice/tutorial-' . $step . '.mp3')))
                        <audio autoplay class="hidden">
                            <source src="{{ asset('storage/voice/tutorial-' . $step . '.mp3') }}" type="audio/mpeg">
                        </audio>
                    @endif

                    @if($rewardItem)
                        @php
                            $itemEmoji = '🎁';
                            if ($rewardItem->type === 'weapon') $itemEmoji = '⚔️';
                            elseif ($rewardItem->type === 'armor') $itemEmoji = '🛡️';
                            elseif ($rewardItem->type === 'accessory') $itemEmoji = '💍';
                            elseif ($rewardItem->type === 'consumable') $itemEmoji = '🧪';
                            elseif ($rewardItem->type === 'material') $itemEmoji = '📦';
                        @endphp
                        <div x-data="{ openTooltip: false }" class="mt-4 mb-6 bg-gradient-to-r from-amber-200/50 to-orange-200/50 border-2 border-amber-500 rounded-xl p-4 flex items-center justify-center transition-all" :class="{ 'animate-pulse': !openTooltip }">
                            <div class="flex items-center space-x-4 bg-slate-800/90 rounded-lg p-3 border border-amber-400 shadow-xl relative cursor-help" @mouseenter="openTooltip = true" @mouseleave="openTooltip = false" @click="openTooltip = true">
                                <div class="w-16 h-16 bg-gradient-to-br from-slate-700 to-slate-900 border-2 border-amber-500 rounded flex items-center justify-center text-3xl">
                                    @if($rewardItem->icon)
                                        <img src="{{ route('assets.items', ['filename' => $rewardItem->icon]) }}" class="w-full h-full object-contain drop-shadow-lg p-1" alt="{{ $rewardItem->name }}">
                                    @else
                                        {{ $itemEmoji }}
                                    @endif
                                </div>
                                <div class="text-left pr-2">
                                    <div class="text-amber-400 font-bold text-lg medieval-font">{{ $rewardItem->name }}</div>
                                    <div class="text-slate-300 text-sm italic hidden sm:block">Najedź, by zobaczyć statystyki</div>
                                    <div class="text-slate-300 text-sm italic sm:hidden">Dotknij, by zobaczyć statystyki</div>
                                </div>

                                {{-- Info Box Tooltip (Desktop) / Modal (Mobile) --}}
                                <div x-show="openTooltip" x-transition.opacity class="fixed inset-0 sm:absolute sm:inset-auto sm:bottom-full sm:left-1/2 sm:-translate-x-1/2 sm:mb-4 sm:w-64 z-[200] sm:z-50 flex items-center justify-center sm:block bg-black/80 sm:bg-transparent backdrop-blur-sm sm:backdrop-blur-none p-4 sm:p-0 cursor-default" style="display: none;" @click.stop="openTooltip = false">
                                    <div class="bg-gray-900 border border-blue-500 p-4 rounded shadow-2xl relative text-left font-sans w-full max-w-xs sm:w-auto sm:max-w-none" @click.stop>
                                        <button @click="openTooltip = false" class="absolute top-2 right-2 text-gray-400 hover:text-white text-lg font-bold sm:hidden">✕</button>
                                        
                                        <div class="mb-2 border-b border-gray-700 pb-2 pr-6">
                                            <p class="font-bold text-amber-400 text-lg">{{ $rewardItem->name }}</p>
                                        </div>
                                        <p class="text-gray-400 mb-1 text-sm">Slot: <span class="text-white">{{ ucfirst($rewardItem->slot ?? 'Brak') }}</span></p>
                                        <p class="text-gray-400 mb-2 text-sm">Wymagany Poz: <span class="text-white">{{ $rewardItem->level_requirement }}</span></p>
                                        
                                        @if(count($rewardItem->base_stats ?? []) > 0)
                                            <div class="mt-2 text-green-400 border-t border-gray-700 pt-2 space-y-1 text-sm">
                                                @foreach($rewardItem->base_stats ?? [] as $stat => $val)
                                                    <div class="flex justify-between">
                                                        <span class="capitalize">{{ str_replace('_', ' ', $stat) }}</span>
                                                        <span class="font-bold">+{{ $val }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    <!-- Arrow (Desktop only) -->
                                    <div class="hidden sm:block absolute -bottom-2 left-1/2 -translate-x-1/2 w-4 h-4 bg-gray-900 border-b border-r border-blue-500 transform rotate-45"></div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($rewardXp > 0)
                        <div class="mt-4 mb-6 bg-gradient-to-r from-green-200/50 to-emerald-200/50 border-2 border-green-500 rounded-xl p-4 flex items-center justify-center animate-[pulse_2s_ease-in-out_infinite]">
                            <div class="flex items-center space-x-4 bg-slate-800/90 rounded-lg p-3 border border-green-400 shadow-xl">
                                <div class="w-16 h-16 bg-gradient-to-br from-slate-700 to-slate-900 border-2 border-green-500 rounded flex items-center justify-center text-3xl">
                                    ✨
                                </div>
                                <div class="text-left">
                                    <div class="text-green-400 font-bold text-lg medieval-font">+{{ $rewardXp }} XP</div>
                                    <div class="text-slate-300 text-sm italic">Punkty doświadczenia zdobyte!</div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($rewardGold > 0)
                        <div class="mt-4 mb-6 bg-gradient-to-r from-yellow-200/50 to-amber-200/50 border-2 border-yellow-500 rounded-xl p-4 flex items-center justify-center animate-[pulse_2s_ease-in-out_infinite]">
                            <div class="flex items-center space-x-4 bg-slate-800/90 rounded-lg p-3 border border-yellow-400 shadow-xl">
                                <div class="w-16 h-16 bg-gradient-to-br from-slate-700 to-slate-900 border-2 border-yellow-500 rounded flex items-center justify-center text-3xl">
                                    🪙
                                </div>
                                <div class="text-left">
                                    <div class="text-yellow-400 font-bold text-lg medieval-font">+{{ $rewardGold }} Złota</div>
                                    <div class="text-slate-300 text-sm italic">Otrzymujesz złoto!</div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-end">
                        <button wire:click="nextStep" onclick="new Audio('{{ asset('storage/sound/A_short,_satisfying__%234-1783930877243.mp3') }}').play()" onmouseenter="new Audio('{{ asset('storage/sound/A_very_subtle,_soft__%232-1783932173771.mp3') }}').play()" onmouseleave="new Audio('{{ asset('storage/sound/A_very_subtle,_soft__%232-1783932173771.mp3') }}').play()" class="bg-gradient-to-r from-amber-600 to-amber-800 hover:from-amber-700 hover:to-amber-900 text-white font-bold py-3 px-8 rounded-lg shadow-[0_4px_0_rgb(120,53,15)] hover:shadow-[0_2px_0_rgb(120,53,15)] hover:translate-y-[2px] transition-all border-2 border-amber-900 medieval-font text-lg">
                            Tak jest, Kapitanie!
                        </button>
                    </div>
                    
                    {{-- Tail for chat bubble pointing left --}}
                    <div class="absolute w-0 h-0 border-r-[30px] border-r-amber-50 border-y-[20px] border-y-transparent -left-[30px] top-12 hidden sm:block"></div>
                    <div class="absolute w-0 h-0 border-r-[36px] border-r-amber-700 border-y-[24px] border-y-transparent -left-[36px] top-[44px] -z-10 hidden sm:block"></div>
                </div>
            </div>
        </div>
    @endif
</div>
