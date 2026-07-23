<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\Quest;
use App\Infrastructure\Persistence\Monster;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Domain\Quests\Enums\QuestType;

class QuestSeeder extends Seeder
{
    public function run(): void
    {
        $questsData = [
            // AKT I: MROCZNY LAS (Lvl 5 - 12)
            [
                'name' => 'Zagrożenie na Obrzeżu Lasu',
                'description' => 'Strażnicy zgłaszają wzmożoną aktywność drapieżników. Przebij się przez gęstwinę i uszczuplij watahę leśnych wilków.',
                'type' => QuestType::HUNTING->value,
                'required_level' => 5,
                'max_level' => 15,
                'target_type' => 'monster',
                'target_name' => 'Wilk Leśny',
                'target_amount' => 8,
                'reward_gold' => 150,
                'reward_exp' => 250,
            ],
            [
                'name' => 'Skrzydlate Zmory',
                'description' => 'Alchemik potrzebuje nieuszkodzonych błon skrzydeł nietoperzy do stworzenia mikstury widzenia w ciemności. Zdobądź je w głębi lasu.',
                'type' => QuestType::GATHERING->value,
                'required_level' => 7,
                'max_level' => 17,
                'target_type' => 'item',
                'target_name' => 'Błona Skrzydła',
                'target_amount' => 5,
                'reward_gold' => 220,
                'reward_exp' => 380,
            ],
            [
                'name' => 'Prastary Surowiec',
                'description' => 'Suchodrzewy w Mrocznym Lesie nasyciły się mroczną magią. Kowal potrzebuje ich prastarej kory do zahartowania pancerzy.',
                'type' => QuestType::GATHERING->value,
                'required_level' => 9,
                'max_level' => 19,
                'target_type' => 'item',
                'target_name' => 'Prastara Kora',
                'target_amount' => 4,
                'reward_gold' => 300,
                'reward_exp' => 550,
            ],
            [
                'name' => 'Zielona Plaga',
                'description' => 'Zwiadowcy goblinów obserwują szlaki kupieckie. Rozbij ich podjazdy, zanim przygotują zasadzkę na karawany.',
                'type' => QuestType::HUNTING->value,
                'required_level' => 11,
                'max_level' => 21,
                'target_type' => 'monster',
                'target_name' => 'Goblin Zwiadowca',
                'target_amount' => 10,
                'reward_gold' => 400,
                'reward_exp' => 800,
            ],

            // AKT II: STARE RUINY (Lvl 14 - 24)
            [
                'name' => 'Krzyk Martwych',
                'description' => 'Stare Ruiny dawnej katedry ożyły pod wpływem nekromancji. Oczyść dziedziniec ze szkieletów wojowników.',
                'type' => QuestType::HUNTING->value,
                'required_level' => 14,
                'max_level' => 25,
                'target_type' => 'monster',
                'target_name' => 'Szkielet Wojownik',
                'target_amount' => 12,
                'reward_gold' => 600,
                'reward_exp' => 1200,
            ],
            [
                'name' => 'Widmowa Esencja',
                'description' => 'Duchy strażników wciąż strzegą zapomnianych grobowców. Pozyskaj ektoplazmę potrzebną do odprawienia rytuału oczyszczenia.',
                'type' => QuestType::GATHERING->value,
                'required_level' => 17,
                'max_level' => 28,
                'target_type' => 'item',
                'target_name' => 'Ektoplazma',
                'target_amount' => 6,
                'reward_gold' => 850,
                'reward_exp' => 1800,
            ],
            [
                'name' => 'Cmentarni Żerowcy',
                'description' => 'Ghule bezczeszczą krypty w poszukiwaniu szczątków. Wyeliminuj te przerażające potwory, zanim zniszczą groby bohaterów.',
                'type' => QuestType::HUNTING->value,
                'required_level' => 20,
                'max_level' => 30,
                'target_type' => 'monster',
                'target_name' => 'Ghul',
                'target_amount' => 15,
                'reward_gold' => 1200,
                'reward_exp' => 2500,
            ],
            [
                'name' => 'Deszcz Mrocznych Strzał',
                'description' => 'Upiorni łucznicy ostrzeliwują każdego, kto zbliży się do ruin. Zdobądź ich zardzewiałe groty, by zbadać ich truciznę.',
                'type' => QuestType::GATHERING->value,
                'required_level' => 23,
                'max_level' => 33,
                'target_type' => 'item',
                'target_name' => 'Zardzewiały Grot',
                'target_amount' => 8,
                'reward_gold' => 1600,
                'reward_exp' => 3400,
            ],

            // AKT III: JASKINIA TROLLI (Lvl 26 - 36)
            [
                'name' => 'Kamienni Bracia',
                'description' => 'Trolle zablokowały przełęcz górską i niszczą okoliczne posterunki. Powstrzymaj natarcie Trolli Paskudników.',
                'type' => QuestType::HUNTING->value,
                'required_level' => 26,
                'max_level' => 37,
                'target_type' => 'monster',
                'target_name' => 'Troll Paskudnik',
                'target_amount' => 15,
                'reward_gold' => 2200,
                'reward_exp' => 4500,
            ],
            [
                'name' => 'Szamańskie Rytuały',
                'description' => 'Trolle Szamani odprawiają mroczne rytuały w głębi jaskiń. Przerwij ich ceremonie i przynieś ich magiczne koraliki.',
                'type' => QuestType::GATHERING->value,
                'required_level' => 29,
                'max_level' => 40,
                'target_type' => 'item',
                'target_name' => 'Szamański Koralik',
                'target_amount' => 8,
                'reward_gold' => 3000,
                'reward_exp' => 6000,
            ],
            [
                'name' => 'Rozłupywacze Czaszek',
                'description' => 'Ogry dołączyły do trolli, wprowadzając niszczycielską siłę. Pokonaj potężnych Ogrów Rozłupywaczy.',
                'type' => QuestType::HUNTING->value,
                'required_level' => 32,
                'max_level' => 43,
                'target_type' => 'monster',
                'target_name' => 'Ogr Rozłupywacz',
                'target_amount' => 12,
                'reward_gold' => 4000,
                'reward_exp' => 8000,
            ],
            [
                'name' => 'Skrzydła Ciemności',
                'description' => 'Nietoperze Alfa roznoszą jaskiniową gorączkę. Zbierz ich krew dla alchemika, aby stworzyć antidotum.',
                'type' => QuestType::GATHERING->value,
                'required_level' => 35,
                'max_level' => 46,
                'target_type' => 'item',
                'target_name' => 'Krew Jaskiniowca',
                'target_amount' => 10,
                'reward_gold' => 5200,
                'reward_exp' => 10500,
            ],

            // AKT IV: PUSTKOWIA ORKÓW (Lvl 38 - 50)
            [
                'name' => 'Orki U Granic',
                'description' => 'Orki z pustkowi szykują się do wielkiego najazdu. Wyeliminuj ich zwiadowców, zanim przekażą informacje o naszej obronie.',
                'type' => QuestType::HUNTING->value,
                'required_level' => 38,
                'max_level' => 49,
                'target_type' => 'monster',
                'target_name' => 'Orczy Zwiad',
                'target_amount' => 18,
                'reward_gold' => 6800,
                'reward_exp' => 14000,
            ],
            [
                'name' => 'Szał Berserkera',
                'description' => 'Orki Berserkerzi walczą bez względu na rany. Zbierz kły pokonanych osiłków jako dowód przełamania pierwszej linii.',
                'type' => QuestType::GATHERING->value,
                'required_level' => 42,
                'max_level' => 53,
                'target_type' => 'item',
                'target_name' => 'Złamany Kieł Orka',
                'target_amount' => 12,
                'reward_gold' => 8800,
                'reward_exp' => 18500,
            ],
            [
                'name' => 'Krwawa Magia',
                'description' => 'Szamani Krwi rzucają klątwy na nasze oddziały. Zdobądź ich totemy, aby złamać mroczne zaklęcia.',
                'type' => QuestType::GATHERING->value,
                'required_level' => 46,
                'max_level' => 57,
                'target_type' => 'item',
                'target_name' => 'Skrwawiony Totem',
                'target_amount' => 10,
                'reward_gold' => 11500,
                'reward_exp' => 24000,
            ],
            [
                'name' => 'Złamanie Watahy',
                'description' => 'Dowódcy watahy orków zarządzają taktyką wrogich sił. Zgładź dowódców i doprowadź do chaosu w ich szeregach.',
                'type' => QuestType::HUNTING->value,
                'required_level' => 49,
                'max_level' => 60,
                'target_type' => 'monster',
                'target_name' => 'Dowódca Watahy',
                'target_amount' => 10,
                'reward_gold' => 14500,
                'reward_exp' => 31000,
            ],

            // AKT V: BAGNA GROZY (Lvl 52 - 64)
            [
                'name' => 'Ofiary Zgniłych Wód',
                'description' => 'Topielcy wciągają nieostrożnych wędrowców w głębiny bagien. Pozyskaj próbki zgniłego mięsa, by zbadać zarazę.',
                'type' => QuestType::GATHERING->value,
                'required_level' => 52,
                'max_level' => 63,
                'target_type' => 'item',
                'target_name' => 'Zgniłe Mięso',
                'target_amount' => 15,
                'reward_gold' => 18000,
                'reward_exp' => 39000,
            ],
            [
                'name' => 'Wiedźmi Pakt',
                'description' => 'Wiedźmia Straż strzeże tajemnych ołtarzy na moczarach. Odbierz z ich rąk wiedźmie amulety nasycone czarną magią.',
                'type' => QuestType::GATHERING->value,
                'required_level' => 56,
                'max_level' => 67,
                'target_type' => 'item',
                'target_name' => 'Wiedźmi Amulet',
                'target_amount' => 12,
                'reward_gold' => 23000,
                'reward_exp' => 49000,
            ],
            [
                'name' => 'Plugawy Korzeń',
                'description' => 'Ożywione i zatrute drzewca tratują wszystko na swojej drodze. Wycinaj plugawe drzewca i przywróć równowagę.',
                'type' => QuestType::HUNTING->value,
                'required_level' => 60,
                'max_level' => 71,
                'target_type' => 'monster',
                'target_name' => 'Drzewiec Plugawy',
                'target_amount' => 15,
                'reward_gold' => 29000,
                'reward_exp' => 61000,
            ],
            [
                'name' => 'Śmiertelne Ukąszenie Hydry',
                'description' => 'Wielogłowe hydry polują na obrzeżach bagniska. Zdobądź ich twarde łuski dla miejskich pancerzmistrzów.',
                'type' => QuestType::GATHERING->value,
                'required_level' => 63,
                'max_level' => 74,
                'target_type' => 'item',
                'target_name' => 'Łuska Hydry',
                'target_amount' => 10,
                'reward_gold' => 36000,
                'reward_exp' => 75000,
            ],

            // AKT VI: GÓRY CIENIA (Lvl 66 - 74)
            [
                'name' => 'Oczy w Ciemności',
                'description' => 'Wilki Cienia nawiedzają wysokie przełęcze górskie. Upoluj te bestie i przynieś gęste mroczne futro.',
                'type' => QuestType::GATHERING->value,
                'required_level' => 66,
                'max_level' => 77,
                'target_type' => 'item',
                'target_name' => 'Mroczne Futro',
                'target_amount' => 15,
                'reward_gold' => 44000,
                'reward_exp' => 92000,
            ],
            [
                'name' => 'Niewzruszony Bazalt',
                'description' => 'Golemy powstałe ze skalnych grani taranują szlaki. Rozbij te bazaltowe potwory na kawałki.',
                'type' => QuestType::HUNTING->value,
                'required_level' => 69,
                'max_level' => 80,
                'target_type' => 'monster',
                'target_name' => 'Golem Bazaltowy',
                'target_amount' => 16,
                'reward_gold' => 53000,
                'reward_exp' => 112000,
            ],
            [
                'name' => 'Władczynie Wiatrów',
                'description' => 'Harpie atakują ze szczytów, nie dając wytchnienia wędrowcom. Zbierz ich pióra do skomponowania strzał arkanowych.',
                'type' => QuestType::GATHERING->value,
                'required_level' => 72,
                'max_level' => 83,
                'target_type' => 'item',
                'target_name' => 'Pióro Harpii',
                'target_amount' => 14,
                'reward_gold' => 64000,
                'reward_exp' => 136000,
            ],
            [
                'name' => 'Upadli Czarownicy',
                'description' => 'Wędrowny czarownicy ulegli mrocznym wpływom Cienia. Zbierz ich zniszczone księgi magii.',
                'type' => QuestType::GATHERING->value,
                'required_level' => 74,
                'max_level' => 85,
                'target_type' => 'item',
                'target_name' => 'Zniszczona Księga Magii',
                'target_amount' => 10,
                'reward_gold' => 76000,
                'reward_exp' => 162000,
            ],

            // AKT VII: WIEŻA MAGÓW (Lvl 76 - 84)
            [
                'name' => 'Pieczęcie Run',
                'description' => 'Adepci w Wieży Magów utracili kontrolę nad zaklęciami. Odbierz im kamienie runiczne rezonujące potężną energią.',
                'type' => QuestType::GATHERING->value,
                'required_level' => 76,
                'max_level' => 87,
                'target_type' => 'item',
                'target_name' => 'Runiczny Kamień',
                'target_amount' => 15,
                'reward_gold' => 90000,
                'reward_exp' => 192000,
            ],
            [
                'name' => 'Konstrukty Magii',
                'description' => 'Strażnicy Arkanów patrolują korytarze wieży i niszczą intruzów. Zdobądź ich tętniące mocą magiczne rdzenie.',
                'type' => QuestType::GATHERING->value,
                'required_level' => 79,
                'max_level' => 90,
                'target_type' => 'item',
                'target_name' => 'Magiczny Rdzeń',
                'target_amount' => 12,
                'reward_gold' => 106000,
                'reward_exp' => 228000,
            ],
            [
                'name' => 'Płomień Arkanów',
                'description' => 'Żywiołaki płomieni zagrażają spłonieniem starożytnej biblioteki magów. Ugaś ich gniew i zbierz ognisty żar.',
                'type' => QuestType::GATHERING->value,
                'required_level' => 82,
                'max_level' => 93,
                'target_type' => 'item',
                'target_name' => 'Żar Płomieni',
                'target_amount' => 15,
                'reward_gold' => 125000,
                'reward_exp' => 270000,
            ],
            [
                'name' => 'Złudzenia i Rzeczywistość',
                'description' => 'Mistrzowie iluzji mamią zmysły wojowników na wyższych piętrach wieży. Pokonaj ich w bezpośrednim starciu.',
                'type' => QuestType::HUNTING->value,
                'required_level' => 84,
                'max_level' => 95,
                'target_type' => 'monster',
                'target_name' => 'Mistrz Iluzji',
                'target_amount' => 18,
                'reward_gold' => 148000,
                'reward_exp' => 320000,
            ],

            // AKT VIII: SKAŻONE MIASTO (Lvl 86 - 90+)
            [
                'name' => 'Zmutowany Szczep',
                'description' => 'Ruiny miasta są opanowane przez mutacje plagi. Zbierz skażone kości zniekształconych nieumarłych.',
                'type' => QuestType::GATHERING->value,
                'required_level' => 86,
                'max_level' => 99,
                'target_type' => 'item',
                'target_name' => 'Skażona Kość',
                'target_amount' => 18,
                'reward_gold' => 175000,
                'reward_exp' => 380000,
            ],
            [
                'name' => 'Jad Skazy',
                'description' => 'Gigantyczne pająki plagi zalęgły się w katakumbach miasta. Pobierz próbki ich śmiertelnego jadu.',
                'type' => QuestType::GATHERING->value,
                'required_level' => 88,
                'max_level' => 99,
                'target_type' => 'item',
                'target_name' => 'Jad Pająka Plagi',
                'target_amount' => 16,
                'reward_gold' => 205000,
                'reward_exp' => 450000,
            ],
            [
                'name' => 'Rycerze Upadku',
                'description' => 'Rycerze Skazy stanowią elitarne siły obronne plagi. Pokonaj ich i odbierz przeklętą stal z ich pancerzy.',
                'type' => QuestType::GATHERING->value,
                'required_level' => 90,
                'max_level' => 99,
                'target_type' => 'item',
                'target_name' => 'Przeklęta Stal',
                'target_amount' => 15,
                'reward_gold' => 240000,
                'reward_exp' => 530000,
            ],
        ];

        foreach ($questsData as $q) {
            $targetId = null;

            if ($q['target_type'] === 'monster') {
                $monster = Monster::where('name', $q['target_name'])->first();
                if ($monster) {
                    $targetId = (string)$monster->id;
                } else {
                    $this->command->warn("Nie znaleziono potwora: {$q['target_name']}");
                    continue;
                }
            } elseif ($q['target_type'] === 'item') {
                $item = ItemTemplate::where('name', $q['target_name'])->first();
                if ($item) {
                    $targetId = (string)$item->id;
                } else {
                    $this->command->warn("Nie znaleziono przedmiotu: {$q['target_name']}");
                    continue;
                }
            }

            Quest::updateOrCreate(
                ['name' => $q['name']],
                [
                    'description' => $q['description'],
                    'type' => $q['type'],
                    'required_level' => $q['required_level'],
                    'max_level' => $q['max_level'],
                    'target_type' => $q['target_type'],
                    'target_id' => $targetId,
                    'target_amount' => $q['target_amount'],
                    'reward_gold' => $q['reward_gold'],
                    'reward_exp' => $q['reward_exp'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('QuestSeeder zakończony - zasilono 31 fabularnych questów (lvl 5 - 90).');
    }
}
