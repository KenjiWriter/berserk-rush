<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\News;
use Carbon\Carbon;

class NewsSeeder extends Seeder
{
    public function run(): void
    {
        $newsItems = [
            [
                'title' => 'Start Projektu Berserk Rush: Faza 1 i 2',
                'content' => "Witamy wszystkich w świecie Berserk Rush! Oddajemy w Wasze ręce pierwsze zręby gry. Udostępniliśmy możliwość tworzenia postaci, walkę PvE z potworami, system poziomów oraz w pełni funkcjonalny ekwipunek. Możecie już zakładać zdobyte z potworów łupy i walczyć o coraz wyższe pozycje w rankingu. To dopiero początek naszej wspólnej przygody!",
                'published_at' => Carbon::now()->subDays(60)
            ],
            [
                'title' => 'Wielkie Otwarcie Kuźni (Faza 3)',
                'content' => "Kowal wreszcie otworzył swoje wrota! Od dziś możecie ulepszać swój ekwipunek na wyższe poziomy (od +0 do +9). Pamiętajcie jednak o ryzyku spalenia przedmiotu na wyższych poziomach. Wprowadziliśmy również nowe algorytmy obliczania szans i losowych statystyk.",
                'published_at' => Carbon::now()->subDays(50)
            ],
            [
                'title' => 'Czarodziej, Magia i Typy Potworów (Faza 4)',
                'content' => "Magia dociera do świata Berserk Rush. U Czarodzieja możecie teraz zaklinać swoje przedmioty, dodając do nich potężne losowe bonusy. Zrewolucjonizowaliśmy również system walki wprowadzając modyfikatory obrażeń przeciwko nowym grupom potworów: Zwierzętom, Demonom, Nieumarłym oraz Orkom.",
                'published_at' => Carbon::now()->subDays(45)
            ],
            [
                'title' => 'Aktualizacja Systemu Administracji i Combat Power (Faza 5)',
                'content' => "Dla lepszej orientacji w sile Waszych postaci dodaliśmy system Combat Power (CP), który oblicza całkowitą moc ekwipunku. Równocześnie wdrożyliśmy zaawansowany system administracyjny, dzięki któremu będziemy w stanie sprawniej zarządzać mapami i przedmiotami.",
                'published_at' => Carbon::now()->subDays(40)
            ],
            [
                'title' => 'Warzenie Mikstur z Czarownicą (Faza 6)',
                'content' => "Zbierajcie zioła i składniki! W Mieście pojawiła się Czarownica ze swoim Kociołkiem. Otworzyliśmy nowy system alchemii pozwalający na tworzenie własnych mikstur wspomagających, w tym potężnej mikstury dodatkowego Doświadczenia.",
                'published_at' => Carbon::now()->subDays(35)
            ],
            [
                'title' => 'Ekonomia i Aspekty Społecznościowe (Faza 7 i 8)',
                'content' => "Wprowadzamy nową erę w grze - Dom Aukcyjny oraz system pocztowy są już otwarte! Możecie bezpiecznie handlować sprzętem z innymi graczami. Aby umilić Wam rozgrywkę, dodaliśmy również Czat Globalny działający w czasie rzeczywistym.",
                'published_at' => Carbon::now()->subDays(25)
            ],
            [
                'title' => 'Zaawansowane Rzemiosło i Optymalizacje (Faza 9 i 10)',
                'content' => "Rozbudowaliśmy rzemiosło - od teraz ulepszajcie swoje przedmioty tworząc z nich potężne artefakty o legendarnych statystykach rzadkości. Przeprowadziliśmy również optymalizację silnika gry i dodaliśmy nowe, wciągające animacje.",
                'published_at' => Carbon::now()->subDays(15)
            ],
            [
                'title' => 'Nadchodzi Endgame: Lochy i PvE (Faza 11)',
                'content' => "Dla najsilniejszych wojowników dodaliśmy Lochy (Dungeony). Zdobądźcie cenne klucze z map i zmierzcie się ze straszliwymi bossami w zamkniętych instancjach. Do gry zostały również dodane urocze Pety, które pomogą Wam na szlaku!",
                'published_at' => Carbon::now()->subDays(10)
            ],
            [
                'title' => 'Aktualizacja: Arena PvP i Wojny Gildii (Faza 12)',
                'content' => "Nadszedł czas na sprawdzenie swoich umiejętności na tle innych graczy. Arena PvP jest już otwarta! Walczcie, zdobywajcie ELO, wygrywajcie Żetony Gladiatora i zdobywajcie ekskluzywny sprzęt. Prawdziwe Wojny Gildii rozpoczynają się dzisiaj!",
                'published_at' => Carbon::now()->subDays(5)
            ],
            [
                'title' => 'Rozbudowa: Tutorial, Questy i Osiągnięcia (Faza 14, 15, 16)',
                'content' => "To największa aktualizacja wprowadzająca dla nowych graczy - udostępniliśmy pełen interaktywny Samouczek. Oprócz tego na Tablicy Zadań w mieście znajdziecie pełno nowych questów. Odblokowujcie osiągnięcia, zdobywajcie tytuły i pochwalcie się swoimi zdobyczami przed znajomymi!",
                'published_at' => Carbon::now()->subHours(5)
            ]
        ];

        foreach ($newsItems as $news) {
            News::create($news);
        }
    }
}
