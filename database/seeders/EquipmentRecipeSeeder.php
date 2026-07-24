<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\ItemRecipe;
use Illuminate\Support\Str;

class EquipmentRecipeSeeder extends Seeder
{
    public function run(): void
    {
        // Najpierw pobierzmy wszystkie materiały z bazy
        $materials = ItemTemplate::where('type', 'material')->get()->keyBy('name');

        if ($materials->isEmpty()) {
            $this->command->warn('Brakuje materiałów w bazie. Uruchom najpierw MaterialItemSeeder.');
            return;
        }

        // Czyszczenie starych receptur dla ekwipunku
        $equipmentIds = ItemTemplate::whereIn('type', ['weapon', 'armor', 'accessory'])->pluck('id');
        ItemRecipe::whereIn('result_item_template_id', $equipmentIds)->delete();

        // Mapa unikalnych przepisów wg pełnych nazw przedmiotów
        $recipeDefinitions = [
            // ==========================================
            // TIER 5 (Level 5 - Mroczny Las)
            // ==========================================
            'Miecz Leśnego Goblina' => ['gold' => 100, 'req' => ['Gobliński Sztylet' => 3, 'Wilczy Kieł' => 2]],
            'Topór Drwala z Mrocznego Lasu' => ['gold' => 100, 'req' => ['Prastara Kora' => 4, 'Wilczy Kieł' => 2]],
            'Łuk z Pnia Suchodrzewu' => ['gold' => 100, 'req' => ['Prastara Kora' => 3, 'Błona Skrzydła' => 3]],
            'Sztylety z Kości Wilka' => ['gold' => 100, 'req' => ['Wilczy Kieł' => 4, 'Gobliński Sztylet' => 2]],
            'Dzwon Leśnego Szamana' => ['gold' => 100, 'req' => ['Słaby Kryształ Many' => 2, 'Magiczny Mech' => 3]],
            'Różdżka z Cisa' => ['gold' => 100, 'req' => ['Prastara Kora' => 3, 'Słaby Kryształ Many' => 2]],
            'Hełm Leśnego Strażnika' => ['gold' => 100, 'req' => ['Prastara Kora' => 2, 'Wilczy Kieł' => 3]],
            'Pancerz z Wilczej Skóry' => ['gold' => 100, 'req' => ['Wilczy Kieł' => 5, 'Prastara Kora' => 3]],
            'Buty Tropiącego' => ['gold' => 100, 'req' => ['Wilczy Kieł' => 3, 'Magiczny Mech' => 2]],
            'Kaptur Spleciony z Mchu' => ['gold' => 100, 'req' => ['Magiczny Mech' => 4, 'Mroczne Zioło' => 2]],
            'Szata Leśnego Ducha' => ['gold' => 100, 'req' => ['Magiczny Mech' => 5, 'Słaby Kryształ Many' => 2]],
            'Miękkie Mokasyny' => ['gold' => 100, 'req' => ['Magiczny Mech' => 3, 'Błona Skrzydła' => 2]],
            'Maska z Czaszki Wilka' => ['gold' => 100, 'req' => ['Wilczy Kieł' => 3, 'Błona Skrzydła' => 2]],
            'Skórznia Nocnego Łowcy' => ['gold' => 100, 'req' => ['Błona Skrzydła' => 5, 'Wilczy Kieł' => 3]],
            'Ciche Podeszwy' => ['gold' => 100, 'req' => ['Błona Skrzydła' => 3, 'Magiczny Mech' => 2]],
            'Naszyjnik z Kłów' => ['gold' => 100, 'req' => ['Kawałek Poroża' => 1, 'Wilczy Kieł' => 3]],
            'Pierścień Wędrowca' => ['gold' => 100, 'req' => ['Słaby Kryształ Many' => 2, 'Mroczne Zioło' => 3]],

            // ==========================================
            // TIER 15 (Level 15 - Głęboki Las / Król Lasu)
            // ==========================================
            'Ostrze Króla Lasu' => ['gold' => 450, 'req' => ['Kawałek Poroża' => 2, 'Gobliński Sztylet' => 4, 'Prastara Kora' => 3]],
            'Ciężki Topór Enta' => ['gold' => 450, 'req' => ['Prastara Kora' => 6, 'Wilczy Kieł' => 4]],
            'Łuk Nocnego Myśliwego' => ['gold' => 450, 'req' => ['Prastara Kora' => 5, 'Błona Skrzydła' => 5]],
            'Kostur z Serca Suchodrzewu' => ['gold' => 450, 'req' => ['Prastara Kora' => 5, 'Słaby Kryształ Many' => 4]],
            'Dzwon Prastarych Drzew' => ['gold' => 450, 'req' => ['Słaby Kryształ Many' => 5, 'Magiczny Mech' => 4]],
            'Zatrute Sztylety Goblina' => ['gold' => 450, 'req' => ['Gobliński Sztylet' => 5, 'Błona Skrzydła' => 3, 'Mroczne Zioło' => 4]],
            'Wzmocniony Hełm Strażnika' => ['gold' => 450, 'req' => ['Prastara Kora' => 4, 'Kawałek Poroża' => 2]],
            'Zbroja z Twardej Kory' => ['gold' => 450, 'req' => ['Prastara Kora' => 6, 'Wilczy Kieł' => 5]],
            'Okute Buty Leśnika' => ['gold' => 450, 'req' => ['Prastara Kora' => 4, 'Magiczny Mech' => 3]],
            'Kaptur Krwawego Mchu' => ['gold' => 450, 'req' => ['Magiczny Mech' => 5, 'Mroczne Zioło' => 4]],
            'Szata Spaczonego Lasu' => ['gold' => 450, 'req' => ['Magiczny Mech' => 6, 'Słaby Kryształ Many' => 4]],
            'Trzewiki Korzeni' => ['gold' => 450, 'req' => ['Prastara Kora' => 3, 'Magiczny Mech' => 4]],
            'Maska Półcienia' => ['gold' => 450, 'req' => ['Błona Skrzydła' => 4, 'Gobliński Sztylet' => 3]],
            'Płaszcz Liściastego Skrytobójcy' => ['gold' => 450, 'req' => ['Błona Skrzydła' => 6, 'Mroczne Zioło' => 4]],
            'Buty Skoku' => ['gold' => 450, 'req' => ['Błona Skrzydła' => 5, 'Magiczny Mech' => 3]],
            'Amulet Prastarego Dębu' => ['gold' => 450, 'req' => ['Kawałek Poroża' => 2, 'Słaby Kryształ Many' => 3, 'Prastara Kora' => 3]],
            'Pierścień Splecionych Korzeni' => ['gold' => 450, 'req' => ['Prastara Kora' => 4, 'Słaby Kryształ Many' => 3, 'Mroczne Zioło' => 3]],

            // ==========================================
            // TIER 25 (Level 25 - Stare Ruiny)
            // ==========================================
            'Zardzewiały Miecz Szkieletu' => ['gold' => 1200, 'req' => ['Strzaskana Kość' => 5, 'Odłamek Ruin' => 3]],
            'Ząbkowany Topór Upiora' => ['gold' => 1200, 'req' => ['Strzaskana Kość' => 6, 'Zardzewiała Moneta' => 3]],
            'Łuk z Kości Zjaw' => ['gold' => 1200, 'req' => ['Zardzewiały Grot' => 5, 'Strzaskana Kość' => 4]],
            'Sztylety Skrytobójcy Dusz' => ['gold' => 1200, 'req' => ['Zardzewiały Grot' => 4, 'Ektoplazma' => 4]],
            'Dzwon Pokutny' => ['gold' => 1200, 'req' => ['Ektoplazma' => 5, 'Pył Grobowy' => 3]],
            'Różdżka Potępionych Dusz' => ['gold' => 1200, 'req' => ['Pył Grobowy' => 4, 'Fragment Całunu' => 1, 'Ektoplazma' => 3]],
            'Zardzewiały Hełm Rycerza' => ['gold' => 1200, 'req' => ['Strzaskana Kość' => 4, 'Odłamek Ruin' => 3]],
            'Kolczuga Strażnika Ruin' => ['gold' => 1200, 'req' => ['Strzaskana Kość' => 7, 'Zardzewiała Moneta' => 4]],
            'Żelazne Sabatony' => ['gold' => 1200, 'req' => ['Odłamek Ruin' => 4, 'Strzaskana Kość' => 3]],
            'Kaptur Zjaw' => ['gold' => 1200, 'req' => ['Pył Grobowy' => 4, 'Ektoplazma' => 3]],
            'Zbutwiała Szata Licza' => ['gold' => 1200, 'req' => ['Ektoplazma' => 5, 'Fragment Całunu' => 2, 'Pył Grobowy' => 3]],
            'Buty Mgły' => ['gold' => 1200, 'req' => ['Ektoplazma' => 4, 'Pył Grobowy' => 2]],
            'Maska Beztwarzowego Ducha' => ['gold' => 1200, 'req' => ['Ektoplazma' => 4, 'Zardzewiały Grot' => 3]],
            'Skórznia Z Grobowca' => ['gold' => 1200, 'req' => ['Zardzewiały Grot' => 5, 'Pył Grobowy' => 4]],
            'Cmentarne Buty' => ['gold' => 1200, 'req' => ['Pył Grobowy' => 4, 'Zardzewiała Moneta' => 3]],
            'Naszyjnik z Zimnej Stali' => ['gold' => 1200, 'req' => ['Zardzewiała Moneta' => 5, 'Przeklęty Onyks' => 1]],
            'Pierścień Wiecznego Żalu' => ['gold' => 1200, 'req' => ['Przeklęty Onyks' => 1, 'Pył Grobowy' => 4, 'Ektoplazma' => 2]],

            // ==========================================
            // TIER 35 (Level 35 - Jaskinia Trolli)
            // ==========================================
            'Maczuga Ogra' => ['gold' => 2800, 'req' => ['Ogrzy Pazur' => 5, 'Ruda Żelaza' => 4]],
            'Rozłupywacz Czaszek' => ['gold' => 2800, 'req' => ['Ogrzy Pazur' => 4, 'Gruba Skóra Trolla' => 4, 'Ruda Żelaza' => 3]],
            'Łuk z Kości Jaskiniowca' => ['gold' => 2800, 'req' => ['Krew Jaskiniowca' => 4, 'Gruba Skóra Trolla' => 4]],
            'Sztylety z Zębów Nietoperza' => ['gold' => 2800, 'req' => ['Krew Jaskiniowca' => 5, 'Śluz Jaskiniowy' => 3]],
            'Dzwon Szamana Trolli' => ['gold' => 2800, 'req' => ['Szamański Koralik' => 4, 'Błyszczący Grzyb' => 4]],
            'Różdżka Ziemnej Magii' => ['gold' => 2800, 'req' => ['Błyszczący Grzyb' => 5, 'Szamański Koralik' => 3, 'Ruda Żelaza' => 2]],
            'Hełm z Czaszki Ogra' => ['gold' => 2800, 'req' => ['Ogrzy Pazur' => 4, 'Gruba Skóra Trolla' => 3]],
            'Gruboskórny Pancerz Trolla' => ['gold' => 2800, 'req' => ['Gruba Skóra Trolla' => 7, 'Ruda Żelaza' => 4]],
            'Masywne Buciska' => ['gold' => 2800, 'req' => ['Ruda Żelaza' => 5, 'Gruba Skóra Trolla' => 3]],
            'Szamański Kaptur Trolli' => ['gold' => 2800, 'req' => ['Szamański Koralik' => 3, 'Błyszczący Grzyb' => 4]],
            'Szata z Futer Nietoperzy' => ['gold' => 2800, 'req' => ['Krew Jaskiniowca' => 4, 'Śluz Jaskiniowy' => 4, 'Błyszczący Grzyb' => 3]],
            'Buty z Mchu Jaskiniowego' => ['gold' => 2800, 'req' => ['Śluz Jaskiniowy' => 4, 'Błyszczący Grzyb' => 3]],
            'Maska Łowcy Ogrów' => ['gold' => 2800, 'req' => ['Krew Jaskiniowca' => 4, 'Gruba Skóra Trolla' => 3]],
            'Płaszcz Skalnego Cienia' => ['gold' => 2800, 'req' => ['Gruba Skóra Trolla' => 5, 'Śluz Jaskiniowy' => 4]],
            'Buty Cichego Kroku' => ['gold' => 2800, 'req' => ['Śluz Jaskiniowy' => 4, 'Krew Jaskiniowca' => 3]],
            'Amulet Skalnego Trolla' => ['gold' => 2800, 'req' => ['Odłamek Skarbu' => 1, 'Szamański Koralik' => 3, 'Ruda Żelaza' => 3]],
            'Kamienny Pierścień' => ['gold' => 2800, 'req' => ['Odłamek Skarbu' => 1, 'Błyszczący Grzyb' => 4, 'Ruda Żelaza' => 3]],

            // ==========================================
            // TIER 45 (Level 45 - Pustkowia Orków)
            // ==========================================
            'Glewia Wodza Orków' => ['gold' => 6000, 'req' => ['Złamany Kieł Orka' => 5, 'Kamień Szlifierski' => 4, 'Symbol Wodza' => 1]],
            'Topór Berserkera Orków' => ['gold' => 6000, 'req' => ['Złamany Kieł Orka' => 6, 'Szczątki Pancerza' => 3]],
            'Łuk Krwawego Zwiadu' => ['gold' => 6000, 'req' => ['Twarde Rzemienie' => 5, 'Wyschnięty Krzew' => 4]],
            'Sztylety Pustkowi' => ['gold' => 6000, 'req' => ['Twarde Rzemienie' => 4, 'Skóra Pustynna' => 4, 'Kamień Szlifierski' => 3]],
            'Dzwon Krwawego Rytuału' => ['gold' => 6000, 'req' => ['Skrwawiony Totem' => 4, 'Skóra Pustynna' => 3]],
            'Kostur Szamana Krwi' => ['gold' => 6000, 'req' => ['Skrwawiony Totem' => 4, 'Wyschnięty Krzew' => 4, 'Skóra Pustynna' => 3]],
            'Hełm Wodza Orków' => ['gold' => 6000, 'req' => ['Złamany Kieł Orka' => 4, 'Szczątki Pancerza' => 3, 'Symbol Wodza' => 1]],
            'Pancerz z Hartowanej Stali' => ['gold' => 6000, 'req' => ['Szczątki Pancerza' => 5, 'Złamany Kieł Orka' => 5]],
            'Buty Orkowego Wojownika' => ['gold' => 6000, 'req' => ['Szczątki Pancerza' => 4, 'Twarde Rzemienie' => 3]],
            'Kaptur Szamana Krwi' => ['gold' => 6000, 'req' => ['Skrwawiony Totem' => 3, 'Skóra Pustynna' => 4]],
            'Szata Nasączona Krwią' => ['gold' => 6000, 'req' => ['Skrwawiony Totem' => 5, 'Wyschnięty Krzew' => 4]],
            'Trzewiki Rytualne' => ['gold' => 6000, 'req' => ['Skóra Pustynna' => 4, 'Wyschnięty Krzew' => 3]],
            'Maska Pustynnego Wiatru' => ['gold' => 6000, 'req' => ['Skóra Pustynna' => 4, 'Twarde Rzemienie' => 3]],
            'Skórznia Orkowego Zabójcy' => ['gold' => 6000, 'req' => ['Twarde Rzemienie' => 5, 'Skóra Pustynna' => 4]],
            'Buty Burzy Piaskowej' => ['gold' => 6000, 'req' => ['Skóra Pustynna' => 5, 'Kamień Szlifierski' => 3]],
            'Naszyjnik Orkowego Wodza' => ['gold' => 6000, 'req' => ['Symbol Wodza' => 1, 'Skrwawiony Totem' => 3, 'Kamień Szlifierski' => 3]],
            'Pierścień Berserkera' => ['gold' => 6000, 'req' => ['Symbol Wodza' => 1, 'Złamany Kieł Orka' => 4, 'Kamień Szlifierski' => 3]],

            // ==========================================
            // TIER 55 (Level 55 - Bagna Grozy)
            // ==========================================
            'Ostrze z Zęba Hydry' => ['gold' => 15000, 'req' => ['Łuska Hydry' => 4, 'Zgniłe Mięso' => 4, 'Toksyczny Śluz' => 3]],
            'Zbutwiały Topór Topielca' => ['gold' => 15000, 'req' => ['Zgniłe Mięso' => 6, 'Toksyczny Śluz' => 4, 'Skamieniały Torf' => 2]],
            'Łuk z Wierzby Płaczącej' => ['gold' => 15000, 'req' => ['Błotnisty Korzeń' => 5, 'Mętna Woda' => 4]],
            'Zatrute Kły Hydry' => ['gold' => 15000, 'req' => ['Toksyczny Śluz' => 5, 'Łuska Hydry' => 3, 'Zgniłe Mięso' => 3]],
            'Dzwon Utopców' => ['gold' => 15000, 'req' => ['Wiedźmi Amulet' => 3, 'Mętna Woda' => 4, 'Bagienne Zioło' => 3]],
            'Różdżka Wiedźmiej Straży' => ['gold' => 15000, 'req' => ['Wiedźmi Amulet' => 4, 'Bagienne Zioło' => 5]],
            'Zardzewiały Hełm z Głębin' => ['gold' => 15000, 'req' => ['Skamieniały Torf' => 4, 'Zgniłe Mięso' => 4]],
            'Pancerz z Łusek Hydry' => ['gold' => 15000, 'req' => ['Łuska Hydry' => 5, 'Skamieniały Torf' => 4]],
            'Mokre Buty Bagienne' => ['gold' => 15000, 'req' => ['Skamieniały Torf' => 4, 'Mętna Woda' => 3]],
            'Kaptur Wiedźmy Zgnilizny' => ['gold' => 15000, 'req' => ['Wiedźmi Amulet' => 3, 'Bagienne Zioło' => 4]],
            'Szata Tkana z Zielska' => ['gold' => 15000, 'req' => ['Bagienne Zioło' => 6, 'Mętna Woda' => 4, 'Błotnisty Korzeń' => 3]],
            'Buty Bagiennej Mgły' => ['gold' => 15000, 'req' => ['Bagienne Zioło' => 4, 'Mętna Woda' => 3]],
            'Maska z Błota' => ['gold' => 15000, 'req' => ['Toksyczny Śluz' => 4, 'Zgniłe Mięso' => 3]],
            'Skórznia Żmijowa' => ['gold' => 15000, 'req' => ['Łuska Hydry' => 4, 'Zgniłe Mięso' => 4, 'Toksyczny Śluz' => 3]],
            'Podeszwy Bezdźwięku' => ['gold' => 15000, 'req' => ['Błotnisty Korzeń' => 4, 'Bagienne Zioło' => 3]],
            'Naszyjnik z Oka Hydry' => ['gold' => 15000, 'req' => ['Skamieniały Torf' => 3, 'Łuska Hydry' => 2, 'Wiedźmi Amulet' => 2]],
            'Pierścień Zgniłego Mchu' => ['gold' => 15000, 'req' => ['Skamieniały Torf' => 3, 'Bagienne Zioło' => 4, 'Wiedźmi Amulet' => 2]],

            // ==========================================
            // TIER 65 (Level 65 - Góry Cienia)
            // ==========================================
            'Miecz Wykuty z Bazaltu' => ['gold' => 35000, 'req' => ['Odłamek Bazaltu' => 5, 'Górska Ruda Miedzi' => 5]],
            'Topór Kamiennego Golema' => ['gold' => 35000, 'req' => ['Odłamek Bazaltu' => 6, 'Popiół Wulkaniczny' => 3]],
            'Łuk z Piór Harpii' => ['gold' => 35000, 'req' => ['Pióro Harpii' => 5, 'Mroczne Futro' => 4]],
            'Sztylety Skalnego Kła' => ['gold' => 35000, 'req' => ['Odłamek Bazaltu' => 4, 'Pióro Harpii' => 4, 'Górska Ruda Miedzi' => 3]],
            'Dzwon Górskiego Echa' => ['gold' => 35000, 'req' => ['Kryształ Cienia' => 3, 'Zniszczona Księga Magii' => 3, 'Popiół Wulkaniczny' => 3]],
            'Różdżka z Górskiego Kryształu' => ['gold' => 35000, 'req' => ['Zniszczona Księga Magii' => 4, 'Kryształ Cienia' => 3]],
            'Hełm z Czarnego Bazaltu' => ['gold' => 35000, 'req' => ['Odłamek Bazaltu' => 5, 'Mroczne Futro' => 3]],
            'Pancerz Skalnego Golema' => ['gold' => 35000, 'req' => ['Odłamek Bazaltu' => 6, 'Górska Ruda Miedzi' => 4]],
            'Ciężkie Kamienne Buty' => ['gold' => 35000, 'req' => ['Odłamek Bazaltu' => 4, 'Górska Ruda Miedzi' => 4]],
            'Kaptur Burzowych Chmur' => ['gold' => 35000, 'req' => ['Zniszczona Księga Magii' => 3, 'Kryształ Cienia' => 3]],
            'Szata z Piór Harpii' => ['gold' => 35000, 'req' => ['Pióro Harpii' => 6, 'Kryształ Cienia' => 3]],
            'Trzewiki Górskiego Wiatru' => ['gold' => 35000, 'req' => ['Pióro Harpii' => 4, 'Mroczne Futro' => 3]],
            'Maska Nocnego Drapieżnika' => ['gold' => 35000, 'req' => ['Mroczne Futro' => 4, 'Pióro Harpii' => 3]],
            'Płaszcz Górskiego Cienia' => ['gold' => 35000, 'req' => ['Mroczne Futro' => 5, 'Kryształ Cienia' => 3]],
            'Buty Sokolnika' => ['gold' => 35000, 'req' => ['Pióro Harpii' => 5, 'Mroczne Futro' => 3]],
            'Naszyjnik ze Szponu Harpii' => ['gold' => 35000, 'req' => ['Popiół Wulkaniczny' => 4, 'Pióro Harpii' => 3, 'Kryształ Cienia' => 2]],
            'Pierścień Czarnego Kryształu' => ['gold' => 35000, 'req' => ['Kryształ Cienia' => 4, 'Popiół Wulkaniczny' => 3, 'Górska Ruda Miedzi' => 3]],

            // ==========================================
            // TIER 75 (Level 75 - Leże Smoka Cienia)
            // ==========================================
            'Piekielny Miecz Smoka' => ['gold' => 80000, 'req' => ['Łuska Smoka Cienia' => 3, 'Górska Ruda Miedzi' => 8, 'Popiół Wulkaniczny' => 4]],
            'Topór Smoczego Gniewu' => ['gold' => 80000, 'req' => ['Łuska Smoka Cienia' => 3, 'Odłamek Bazaltu' => 6, 'Popiół Wulkaniczny' => 4]],
            'Smoczy Łuk' => ['gold' => 80000, 'req' => ['Łuska Smoka Cienia' => 2, 'Pióro Harpii' => 6, 'Mroczne Futro' => 4]],
            'Sztylety z Cienia Smoka' => ['gold' => 80000, 'req' => ['Łuska Smoka Cienia' => 2, 'Kryształ Cienia' => 4, 'Pióro Harpii' => 4]],
            'Dzwon Oddechu Smoka' => ['gold' => 80000, 'req' => ['Łuska Smoka Cienia' => 2, 'Zniszczona Księga Magii' => 4, 'Popiół Wulkaniczny' => 5]],
            'Różdżka Smoczej Łuski' => ['gold' => 80000, 'req' => ['Łuska Smoka Cienia' => 2, 'Kryształ Cienia' => 5, 'Zniszczona Księga Magii' => 4]],
            'Hełm Smoczej Straży' => ['gold' => 80000, 'req' => ['Łuska Smoka Cienia' => 3, 'Odłamek Bazaltu' => 5]],
            'Pancerz ze Smoczych Łusek' => ['gold' => 80000, 'req' => ['Łuska Smoka Cienia' => 5, 'Górska Ruda Miedzi' => 6]],
            'Sabatony Smoka' => ['gold' => 80000, 'req' => ['Łuska Smoka Cienia' => 3, 'Górska Ruda Miedzi' => 5]],
            'Kaptur Cienia Smoka' => ['gold' => 80000, 'req' => ['Łuska Smoka Cienia' => 2, 'Kryształ Cienia' => 4, 'Zniszczona Księga Magii' => 3]],
            'Szata Smoczego Ognia' => ['gold' => 80000, 'req' => ['Łuska Smoka Cienia' => 3, 'Zniszczona Księga Magii' => 5, 'Popiół Wulkaniczny' => 4]],
            'Buty z Popiołu' => ['gold' => 80000, 'req' => ['Popiół Wulkaniczny' => 6, 'Kryształ Cienia' => 3]],
            'Maska Mrocznego Zabójcy' => ['gold' => 80000, 'req' => ['Łuska Smoka Cienia' => 2, 'Mroczne Futro' => 4, 'Kryształ Cienia' => 3]],
            'Skórznia Łowcy Smoków' => ['gold' => 80000, 'req' => ['Łuska Smoka Cienia' => 4, 'Mroczne Futro' => 5]],
            'Podeszwy Smoczego Lotu' => ['gold' => 80000, 'req' => ['Łuska Smoka Cienia' => 2, 'Pióro Harpii' => 5]],
            'Amulet Smoczego Oka' => ['gold' => 80000, 'req' => ['Łuska Smoka Cienia' => 2, 'Popiół Wulkaniczny' => 5, 'Kryształ Cienia' => 3]],
            'Pierścień Władcy Cienia' => ['gold' => 80000, 'req' => ['Łuska Smoka Cienia' => 2, 'Kryształ Cienia' => 4, 'Popiół Wulkaniczny' => 4]],

            // ==========================================
            // TIER 85 (Level 85 - Wieża Magów)
            // ==========================================
            'Miecz Runicznego Gwardzisty' => ['gold' => 120000, 'req' => ['Runiczny Kamień' => 5, 'Magiczny Rdzeń' => 2, 'Żar Płomieni' => 3]],
            'Topór Magicznego Płomienia' => ['gold' => 120000, 'req' => ['Żar Płomieni' => 5, 'Runiczny Kamień' => 4]],
            'Łuk z Eterycznej Energii' => ['gold' => 120000, 'req' => ['Eteryczny Pył' => 5, 'Szkło Iluzji' => 4]],
            'Sztylety z Czystej Energii' => ['gold' => 120000, 'req' => ['Szkło Iluzji' => 4, 'Eteryczny Pył' => 4, 'Żar Płomieni' => 2]],
            'Dzwon Mistrza Iluzji' => ['gold' => 120000, 'req' => ['Szkło Iluzji' => 5, 'Czysta Mana' => 4]],
            'Kostur Arcymaga' => ['gold' => 120000, 'req' => ['Magiczny Rdzeń' => 4, 'Czysta Mana' => 5, 'Odłamek Kostura Arcymaga' => 1]],
            'Hełm Strażnika Arkanów' => ['gold' => 120000, 'req' => ['Magiczny Rdzeń' => 3, 'Runiczny Kamień' => 4]],
            'Zbroja Runiczna' => ['gold' => 120000, 'req' => ['Runiczny Kamień' => 6, 'Czysty Pergamin' => 4, 'Magiczny Rdzeń' => 2]],
            'Buty Żywiołaka Płomieni' => ['gold' => 120000, 'req' => ['Żar Płomieni' => 4, 'Runiczny Kamień' => 3]],
            'Kaptur Arcymaga' => ['gold' => 120000, 'req' => ['Czysta Mana' => 4, 'Czysty Pergamin' => 4]],
            'Szata Mistrza Iluzji' => ['gold' => 120000, 'req' => ['Szkło Iluzji' => 5, 'Czysta Mana' => 5, 'Czysty Pergamin' => 3]],
            'Buty Lewitacji' => ['gold' => 120000, 'req' => ['Eteryczny Pył' => 4, 'Czysta Mana' => 3]],
            'Maska Niewidzialności' => ['gold' => 120000, 'req' => ['Szkło Iluzji' => 4, 'Eteryczny Pył' => 3]],
            'Skórznia Nasączona Magią' => ['gold' => 120000, 'req' => ['Czysty Pergamin' => 5, 'Eteryczny Pył' => 4, 'Szkło Iluzji' => 3]],
            'Podeszwy z Eteru' => ['gold' => 120000, 'req' => ['Eteryczny Pył' => 5, 'Czysty Pergamin' => 3]],
            'Naszyjnik Runicznej Energii' => ['gold' => 120000, 'req' => ['Odłamek Kostura Arcymaga' => 1, 'Runiczny Kamień' => 4, 'Czysta Mana' => 4]],
            'Pierścień Absolutu' => ['gold' => 120000, 'req' => ['Odłamek Kostura Arcymaga' => 1, 'Magiczny Rdzeń' => 3, 'Czysta Mana' => 4]],

            // ==========================================
            // TIER 95 (Level 95 - Skażone Miasto)
            // ==========================================
            'Ostrze Skażonego Rycerza' => ['gold' => 250000, 'req' => ['Przeklęta Stal' => 5, 'Skażony Metal' => 4]],
            'Topór Czarownicy Zgnilizny' => ['gold' => 250000, 'req' => ['Fiolka Zgnilizny' => 4, 'Przeklęta Stal' => 4]],
            'Łuk Tkany z Pajęczyny Plagi' => ['gold' => 250000, 'req' => ['Jad Pająka Plagi' => 5, 'Skażona Kość' => 4]],
            'Sztylety Jadu Pająka Plagi' => ['gold' => 250000, 'req' => ['Jad Pająka Plagi' => 5, 'Przeklęta Stal' => 3]],
            'Dzwon Ostatniego Tchnienia' => ['gold' => 250000, 'req' => ['Fiolka Zgnilizny' => 4, 'Popioły Miasta' => 4]],
            'Różdżka Zmutowanego Czarownika' => ['gold' => 250000, 'req' => ['Popioły Miasta' => 5, 'Fiolka Zgnilizny' => 4, 'Czarny Kamień Dusz' => 1]],
            'Hełm Rycerza Skazy' => ['gold' => 250000, 'req' => ['Przeklęta Stal' => 5, 'Skażona Kość' => 3]],
            'Pancerz Skażonej Stali' => ['gold' => 250000, 'req' => ['Skażony Metal' => 5, 'Przeklęta Stal' => 5]],
            'Buty Zgnilizny' => ['gold' => 250000, 'req' => ['Przeklęta Stal' => 4, 'Popioły Miasta' => 3]],
            'Kaptur Pająka Plagi' => ['gold' => 250000, 'req' => ['Jad Pająka Plagi' => 4, 'Popioły Miasta' => 3]],
            'Szata z Przeklętego Jedwabiu' => ['gold' => 250000, 'req' => ['Fiolka Zgnilizny' => 4, 'Popioły Miasta' => 5, 'Jad Pająka Plagi' => 3]],
            'Buty Kwasu' => ['gold' => 250000, 'req' => ['Fiolka Zgnilizny' => 4, 'Skażony Metal' => 3]],
            'Maska Cienia Skazy' => ['gold' => 250000, 'req' => ['Skażona Kość' => 4, 'Jad Pająka Plagi' => 3]],
            'Skórznia Upadłego Zabójcy' => ['gold' => 250000, 'req' => ['Skażona Kość' => 5, 'Jad Pająka Plagi' => 4]],
            'Podeszwy Trucizny' => ['gold' => 250000, 'req' => ['Jad Pająka Plagi' => 4, 'Skażony Metal' => 3]],
            'Amulet Zmutowanego Oka' => ['gold' => 250000, 'req' => ['Czarny Kamień Dusz' => 1, 'Fiolka Zgnilizny' => 3, 'Skażona Kość' => 3]],
            'Pierścień Zgnilizny' => ['gold' => 250000, 'req' => ['Czarny Kamień Dusz' => 1, 'Popioły Miasta' => 4, 'Przeklęta Stal' => 3]],

            // ==========================================
            // TIER 99 (Level 99 - Domena Zniszczenia)
            // ==========================================
            'Miecz Pana Zniszczenia' => ['gold' => 500000, 'req' => ['Esencja Zniszczenia' => 3, 'Przeklęta Stal' => 8, 'Skażony Metal' => 6]],
            'Rozdzieracz Światów' => ['gold' => 500000, 'req' => ['Esencja Zniszczenia' => 3, 'Skażony Metal' => 8, 'Przeklęta Stal' => 6]],
            'Łuk Apokalipsy' => ['gold' => 500000, 'req' => ['Esencja Zniszczenia' => 2, 'Jad Pająka Plagi' => 8, 'Skażona Kość' => 6]],
            'Sztylety Ostatecznego Zniszczenia' => ['gold' => 500000, 'req' => ['Esencja Zniszczenia' => 2, 'Przeklęta Stal' => 6, 'Jad Pająka Plagi' => 6]],
            'Dzwon Sądu Ostatecznego' => ['gold' => 500000, 'req' => ['Czarny Kamień Dusz' => 3, 'Fiolka Zgnilizny' => 6, 'Popioły Miasta' => 10]],
            'Kostur Władcy Mroku' => ['gold' => 500000, 'req' => ['Esencja Zniszczenia' => 2, 'Czarny Kamień Dusz' => 3, 'Popioły Miasta' => 10]],
            'Korona Pana Zniszczenia' => ['gold' => 500000, 'req' => ['Esencja Zniszczenia' => 2, 'Przeklęta Stal' => 6, 'Czarny Kamień Dusz' => 2]],
            'Pancerz Absolutnego Chaosu' => ['gold' => 500000, 'req' => ['Esencja Zniszczenia' => 3, 'Skażony Metal' => 10, 'Przeklęta Stal' => 8]],
            'Buty Deptania Światów' => ['gold' => 500000, 'req' => ['Esencja Zniszczenia' => 2, 'Skażony Metal' => 8]],
            'Kaptur Pożeracza Dusz' => ['gold' => 500000, 'req' => ['Czarny Kamień Dusz' => 3, 'Fiolka Zgnilizny' => 6]],
            'Szata Mrocznej Pustki' => ['gold' => 500000, 'req' => ['Esencja Zniszczenia' => 2, 'Popioły Miasta' => 15, 'Czarny Kamień Dusz' => 3]],
            'Buty Otchłani' => ['gold' => 500000, 'req' => ['Czarny Kamień Dusz' => 2, 'Popioły Miasta' => 10]],
            'Maska Bezwzględnego Zniszczenia' => ['gold' => 500000, 'req' => ['Czarny Kamień Dusz' => 2, 'Jad Pająka Plagi' => 6]],
            'Płaszcz Końca Czasu' => ['gold' => 500000, 'req' => ['Esencja Zniszczenia' => 2, 'Skażona Kość' => 10, 'Jad Pająka Plagi' => 6]],
            'Ciche Podeszwy Zmierzchu' => ['gold' => 500000, 'req' => ['Czarny Kamień Dusz' => 2, 'Skażona Kość' => 8]],
            'Serce Pana Zniszczenia' => ['gold' => 500000, 'req' => ['Esencja Zniszczenia' => 2, 'Czarny Kamień Dusz' => 2, 'Popioły Miasta' => 15]],
            'Sygnet Apokalipsy' => ['gold' => 500000, 'req' => ['Esencja Zniszczenia' => 2, 'Czarny Kamień Dusz' => 2, 'Przeklęta Stal' => 10]],
        ];

        $generatedCount = 0;

        // Pobieramy szablony ekwipunku
        $equipmentTemplates = ItemTemplate::whereIn('type', ['weapon', 'armor', 'accessory'])->get();

        foreach ($equipmentTemplates as $item) {
            if (!isset($recipeDefinitions[$item->name])) {
                $this->command->warn("Brak zdefiniowanego przepisu dla przedmiotu: {$item->name}");
                continue;
            }

            $def = $recipeDefinitions[$item->name];
            $ingredients = [];
            $hasMissingMaterials = false;

            foreach ($def['req'] as $matName => $quantity) {
                if (!isset($materials[$matName])) {
                    $this->command->warn("Brakuje materiału w bazie: {$matName} (dla {$item->name})");
                    $hasMissingMaterials = true;
                    continue;
                }

                $ingredients[] = [
                    'template_id' => $materials[$matName]->id,
                    'quantity' => $quantity
                ];
            }

            if (!$hasMissingMaterials && !empty($ingredients)) {
                ItemRecipe::create([
                    'id' => (string) Str::ulid(),
                    'result_item_template_id' => $item->id,
                    'ingredients' => $ingredients,
                    'gold_cost' => $def['gold']
                ]);
                $generatedCount++;
            }
        }

        $this->command->info("EquipmentRecipeSeeder: Wygenerowano {$generatedCount} unikalnych receptur dla rzemiosła ekwipunku!");
    }
}
