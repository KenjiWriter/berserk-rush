<?php

/**
 * Jedno źródło prawdy dla balansu systemu Petów (Chowańców).
 * Zarówno logika domenowa (app/Domain/Pets), serwisy (app/Application/Pets)
 * jak i panel informacyjny w UI (city.pets) czerpią wyłącznie z tego pliku,
 * żeby liczby nigdy się nie rozjechały.
 */
return [

    // Definicja 6 tierów peta (dawne "rzadkości"). Klucz = tier (1-6).
    'tiers' => [
        1 => [
            'name' => 'Pospolity',
            'slug' => 't1',
            'hatch_hours' => 1,
            'level_norm' => 1.00,
            'feed_level_min' => 0,
        ],
        2 => [
            'name' => 'Zwykły',
            'slug' => 't2',
            'hatch_hours' => 1.5,
            'level_norm' => 1.30,
            'feed_level_min' => 15,
        ],
        3 => [
            'name' => 'Nietypowy',
            'slug' => 't3',
            'hatch_hours' => 2,
            'level_norm' => 1.60,
            'feed_level_min' => 30,
        ],
        4 => [
            'name' => 'Rzadki',
            'slug' => 't4',
            'hatch_hours' => 2.5,
            'level_norm' => 1.90,
            'feed_level_min' => 45,
        ],
        5 => [
            'name' => 'Epicki',
            'slug' => 't5',
            'hatch_hours' => 3,
            'level_norm' => 2.20,
            'feed_level_min' => 60,
        ],
        6 => [
            'name' => 'Legendarny',
            'slug' => 't6',
            'hatch_hours' => 4,
            'level_norm' => 2.50,
            'feed_level_min' => 75,
        ],
    ],

    // Szansa (%) na wynikowy tier peta w zależności od tieru wykluwanego jajka.
    // Każdy wiersz (klucz zewnętrzny = tier jajka) sumuje się do 100.
    'hatch_matrix' => [
        1 => [1 => 100],
        2 => [1 => 25, 2 => 75],
        3 => [1 => 10, 2 => 25, 3 => 65],
        4 => [2 => 10, 3 => 45, 4 => 45],
        5 => [3 => 20, 4 => 60, 5 => 20],
        6 => [4 => 35, 5 => 60, 6 => 5],
    ],

    // Fuzja: 2 pety tego samego tieru -> 1 pet tier+1.
    // Klucz = tier wejściowy obu petów, wartość = bazowa szansa sukcesu (%).
    'fusion_base_chance' => [
        1 => 80,
        2 => 65,
        3 => 50,
        4 => 35,
        5 => 20,
    ],

    // Dodatkowa szansa (%) za każdą "ewolucję" (growth_stage) osiągniętą
    // przez którykolwiek z 2 łączonych petów, sumowane z obu.
    // 2 w pełni dorosłe pety (growth_stage 3 + 3 = 6 ewolucji) dają +20%.
    'fusion_growth_stage_bonus_percent' => 3.3333,

    // Progi poziomowe wizualnych/mechanicznych etapów wzrostu peta (te same dla każdego tieru).
    // stage => minimalny poziom peta, by uznać ten etap za osiągnięty.
    'growth_stage_thresholds' => [
        0 => 1,
        1 => 25,
        2 => 50,
        3 => 75, // "Forma Dorosła"
    ],

    // Bazowa pula staty peta na poziom (przed mnożnikiem tieru/fuzji).
    'base_stat_per_level' => 2.35,

    // % dodatkowej puli staty ORAZ % dodatkowego wymaganego EXP na każdy punkt fusion_count.
    'fusion_count_bonus_percent' => 10,

    // Bazowy wymagany EXP na poziom (mnożony przez poziom i fusion_count).
    'exp_per_level_base' => 100,

    // Ile niezałożonych petów może jednocześnie posiadać gracz (stajnia).
    'stable_base_capacity' => 20,

    // Typy przedmiotów ekwipunku peta (ItemTemplate::type).
    'gear_types' => [
        'collar' => 'pet_collar',
        'charm' => 'pet_charm',
    ],

    // Koszt (złoto) samej PRÓBY fuzji - pobierany zawsze, niezależnie od
    // wyniku (sukces/porażka). Klucz = tier obu łączonych petów (T6 brak,
    // bo nie można fuzjonować najwyższego tieru).
    'fusion_cost_gold' => [
        1 => 100,
        2 => 250,
        3 => 500,
        4 => 1000,
        5 => 2000,
    ],

    // Warianty tego, co się dzieje z petami PRZY NIEUDANEJ fuzji - dokładnie
    // jeden z nich jest losowany wg wagi (% sumuje się do 100). Koszt fuzji
    // (wyżej) jest pobierany zawsze, niezależnie od wylosowanego wariantu.
    // "devolve" = Pet::demoteGrowthStage() (cofnięcie o 1 etap dojrzałości).
    'fusion_failure_outcomes' => [
        'lose_both' => 2,
        'lose_one' => 10,
        'devolve_both' => 20,
        'devolve_one' => 25,
        'no_loss' => 43,
    ],

    // Pasywka Rodzaju (Atakujący/Obrony/Wspomagający) aktywnego towarzysza:
    // bonus % = fusion_count * ta_wartość * tier (tłumione tak samo jak
    // reszta wkładu peta, patrz Pet::getArchetypeBonusPercentFor()).
    'fusion_count_archetype_bonus_percent' => 1,
];
