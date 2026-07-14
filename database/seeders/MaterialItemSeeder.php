<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Infrastructure\Persistence\ItemTemplate;

class MaterialItemSeeder extends Seeder
{
    public function run(): void
    {
        $materials = [
            // Map 1: Mroczny Las
            ['name' => 'Wilczy Kieł', 'desc' => 'Wypada głównie z Wilków.'],
            ['name' => 'Błona Skrzydła', 'desc' => 'Wypada z Nietoperzy.'],
            ['name' => 'Prastara Kora', 'desc' => 'Wypada z Suchodrzewów i Króla Lasu.'],
            ['name' => 'Gobliński Sztylet', 'desc' => 'Wypada z Goblinów Zwiadowców.'],
            ['name' => 'Mroczne Zioło', 'desc' => 'Popularna roślina używana w alchemii.'],
            ['name' => 'Magiczny Mech', 'desc' => 'Mech nasycony lekką aurą, rosnący w lesie.'],
            ['name' => 'Słaby Kryształ Many', 'desc' => 'Odłamek naturalnej magii lasu.'],
            ['name' => 'Kawałek Poroża', 'desc' => 'Fragment rogu potężnego zwierzęcia.'],
            
            // Map 2: Stare Ruiny
            ['name' => 'Strzaskana Kość', 'desc' => 'Wypada ze Szkieletów i Ghuli.'],
            ['name' => 'Ektoplazma', 'desc' => 'Wypada z Duchów.'],
            ['name' => 'Zardzewiały Grot', 'desc' => 'Wypada z Upiornych Łuczników.'],
            ['name' => 'Fragment Całunu', 'desc' => 'Rzadki drop, głównie Licz Cieni.'],
            ['name' => 'Pył Grobowy', 'desc' => 'Zmiecione resztki pradawnych krypt, doskonałe do mrocznych mikstur.'],
            ['name' => 'Zardzewiała Moneta', 'desc' => 'Dawny środek płatniczy z czasów świetności ruin.'],
            ['name' => 'Odłamek Ruin', 'desc' => 'Kawałek rzeźbionego kamienia, nadający się do szlifowania.'],
            ['name' => 'Przeklęty Onyks', 'desc' => 'Mroczny klejnot znajdowany w zgliszczach.'],
            
            // Map 3: Jaskinia Trolli
            ['name' => 'Gruba Skóra Trolla', 'desc' => 'Wypada z potworów typu Troll.'],
            ['name' => 'Szamański Koralik', 'desc' => 'Wypada z Trolli Szamanów.'],
            ['name' => 'Ogrzy Pazur', 'desc' => 'Wypada z Ogrów.'],
            ['name' => 'Krew Jaskiniowca', 'desc' => 'Wypada z Nietoperzy Alfa.'],
            ['name' => 'Ruda Żelaza', 'desc' => 'Powszechna ruda wydobywana wewnątrz jaskiń.'],
            ['name' => 'Błyszczący Grzyb', 'desc' => 'Naturalne źródło światła i składnik mikstur leczących.'],
            ['name' => 'Śluz Jaskiniowy', 'desc' => 'Lepka substancja przydatna w klejeniu i alchemii.'],
            ['name' => 'Odłamek Skarbu', 'desc' => 'Złom skradziony przez trolle, nadający się do przetopienia.'],
            
            // Map 4: Pustkowia Orków
            ['name' => 'Złamany Kieł Orka', 'desc' => 'Wypada z Orczego Zwiadu i Berserkerów.'],
            ['name' => 'Skrwawiony Totem', 'desc' => 'Wypada z Szamanów Krwi.'],
            ['name' => 'Twarde Rzemienie', 'desc' => 'Wypada z Dowódców Watahy i orków.'],
            ['name' => 'Symbol Wodza', 'desc' => 'Wypada z Wodza Orków.'],
            ['name' => 'Skóra Pustynna', 'desc' => 'Wytwardzona przez słońce skóra zwierząt.'],
            ['name' => 'Wyschnięty Krzew', 'desc' => 'Dobra podpałka i materiał dla początkujących rzemieślników.'],
            ['name' => 'Kamień Szlifierski', 'desc' => 'Narzędzie orków, używane do ostrzenia broni.'],
            ['name' => 'Szczątki Pancerza', 'desc' => 'Zardzewiałe blachy pozostawione na polu bitwy.'],
            
            // Map 5: Bagna Grozy
            ['name' => 'Zgniłe Mięso', 'desc' => 'Wypada z Topielców.'],
            ['name' => 'Wiedźmi Amulet', 'desc' => 'Wypada z Wiedźmiej Straży.'],
            ['name' => 'Błotnisty Korzeń', 'desc' => 'Wypada z Drzewców Plugawych.'],
            ['name' => 'Łuska Hydry', 'desc' => 'Wypada z Hydr i Moczarowych Behemotów.'],
            ['name' => 'Bagienne Zioło', 'desc' => 'Silnie toksyczne, lecz lecznicze przy odpowiedniej obróbce.'],
            ['name' => 'Mętna Woda', 'desc' => 'Używana jako baza do trudniejszych mikstur.'],
            ['name' => 'Toksyczny Śluz', 'desc' => 'Żrąca substancja świetna na trucizny.'],
            ['name' => 'Skamieniały Torf', 'desc' => 'Cenne źródło energii termicznej do kuźni.'],
            
            // Map 6: Góry Cienia
            ['name' => 'Mroczne Futro', 'desc' => 'Wypada z Wilków Cienia.'],
            ['name' => 'Odłamek Bazaltu', 'desc' => 'Wypada z Golemów Bazaltowych.'],
            ['name' => 'Pióro Harpii', 'desc' => 'Wypada z Harpii.'],
            ['name' => 'Zniszczona Księga Magii', 'desc' => 'Wypada z Wędrownych Czarowników.'],
            ['name' => 'Łuska Smoka Cienia', 'desc' => 'Wypada ze Smoka Cienia.'],
            ['name' => 'Kryształ Cienia', 'desc' => 'Nasycony mrokiem kryształ, cenny w jubilerstwie.'],
            ['name' => 'Górska Ruda Miedzi', 'desc' => 'Podstawowy surowiec z wysokich partii gór.'],
            ['name' => 'Popiół Wulkaniczny', 'desc' => 'Żyznio-magiczny pył do craftingu alchemicznego.'],
            
            // Map 7: Wieża Magów
            ['name' => 'Runiczny Kamień', 'desc' => 'Wypada z Adeptów Run.'],
            ['name' => 'Magiczny Rdzeń', 'desc' => 'Wypada ze Strażników Arkanów.'],
            ['name' => 'Żar Płomieni', 'desc' => 'Wypada z Żywiołaków Płomieni.'],
            ['name' => 'Szkło Iluzji', 'desc' => 'Wypada z Mistrzów Iluzji.'],
            ['name' => 'Eteryczny Pył', 'desc' => 'Pozostałość po zniszczonych zaklęciach, rezonująca mocą.'],
            ['name' => 'Czysta Mana', 'desc' => 'Skroplona esencja czystej energii magicznej.'],
            ['name' => 'Czysty Pergamin', 'desc' => 'Podstawa do tworzenia zwojów i run.'],
            ['name' => 'Odłamek Kostura Arcymaga', 'desc' => 'Kawałek potężnego artefaktu.'],
            
            // Map 8: Skażone Miasto
            ['name' => 'Skażona Kość', 'desc' => 'Wypada ze Zmutowanych Nieumarłych.'],
            ['name' => 'Fiolka Zgnilizny', 'desc' => 'Wypada z Czarownic Zgnilizny.'],
            ['name' => 'Jad Pająka Plagi', 'desc' => 'Wypada z Pająków Plagi.'],
            ['name' => 'Przeklęta Stal', 'desc' => 'Wypada z Rycerzy Skazy.'],
            ['name' => 'Esencja Zniszczenia', 'desc' => 'Wypada z Pana Zniszczenia.'],
            ['name' => 'Skażony Metal', 'desc' => 'Zniekształcone przez plagę żelazo, wymagające oczyszczenia.'],
            ['name' => 'Czarny Kamień Dusz', 'desc' => 'Pojemnik chłonący negatywną energię.'],
            ['name' => 'Popioły Miasta', 'desc' => 'Ostatnie pamiątki dawnej cywilizacji, przydatne jako nawóz do magicznych ziół.'],
        ];

        foreach ($materials as $material) {
            $existing = ItemTemplate::where('name', $material['name'])->first();

            if ($existing) {
                $existing->update([
                    'description' => $material['desc'],
                ]);
            } else {
                ItemTemplate::create([
                    'id' => Str::ulid(),
                    'name' => $material['name'],
                    'type' => 'material',
                    'level_requirement' => 1,
                    'description' => $material['desc'],
                    'icon' => Str::slug($material['name']) . '.png',
                    'rarity_weights' => json_encode(['common' => 100]),
                ]);
            }
        }
        
        $this->command->info('MaterialItemSeeder completed.');
    }
}
