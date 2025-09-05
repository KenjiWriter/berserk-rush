<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class Homepage extends Component
{
    #[On('user-logged-in')]
    public function refreshAfterLogin()
    {
        // This will trigger a re-render to show the logged-in state
    }

    public function render()
    {
        // Mock data for rankings and admin messages
        $mockData = [
            'activePlayers' => 1234,
            'topCharacters' => [
                ['name' => 'Dragomir Kostka', 'level' => 87, 'guild' => 'Smoczy Klan'],
                ['name' => 'Zoja Wojowniczka', 'level' => 82, 'guild' => 'Rycerze Światła'],
                ['name' => 'Kazimierz Mag', 'level' => 79, 'guild' => 'Mistrzowie Magii'],
                ['name' => 'Bogdan Łucznik', 'level' => 76, 'guild' => 'Leśni Strażnicy'],
                ['name' => 'Agata Nekromanta', 'level' => 74, 'guild' => 'Dzieci Ciemności'],
                ['name' => 'Mieszko Barbarzyńca', 'level' => 71, 'guild' => 'Dzicy Wojownicy'],
                ['name' => 'Elżbieta Uzdrowicielka', 'level' => 68, 'guild' => 'Święci Kapłani'],
                ['name' => 'Władysław Złodziej', 'level' => 65, 'guild' => 'Cienie Nocy'],
                ['name' => 'Jadwiga Czarodziejka', 'level' => 62, 'guild' => 'Mistrzowie Magii'],
                ['name' => 'Stefan Paladyn', 'level' => 60, 'guild' => 'Rycerze Światła'],
            ],
            'topGuilds' => [
                ['name' => 'Smoczy Klan', 'members' => 45, 'avgLevel' => 67],
                ['name' => 'Rycerze Światła', 'members' => 38, 'avgLevel' => 65],
                ['name' => 'Mistrzowie Magii', 'members' => 42, 'avgLevel' => 63],
                ['name' => 'Leśni Strażnicy', 'members' => 35, 'avgLevel' => 61],
                ['name' => 'Dzieci Ciemności', 'members' => 29, 'avgLevel' => 59],
                ['name' => 'Dzicy Wojownicy', 'members' => 33, 'avgLevel' => 58],
                ['name' => 'Święci Kapłani', 'members' => 27, 'avgLevel' => 56],
                ['name' => 'Cienie Nocy', 'members' => 31, 'avgLevel' => 55],
                ['name' => 'Złota Kompania', 'members' => 25, 'avgLevel' => 53],
                ['name' => 'Strażnicy Honoru', 'members' => 22, 'avgLevel' => 51],
            ],
            'adminMessages' => [
                [
                    'title' => 'Nowa aktualizacja systemu walki!',
                    'content' => 'Wprowadziliśmy zbalansowane zmiany w systemie walki. Teraz każda klasa ma równe szanse na zwycięstwo. Sprawdźcie nowe umiejętności w zakładce Bohater!',
                    'date' => '2025-09-03'
                ],
                [
                    'title' => 'Wydarzenie: Podwójna nagroda za questy',
                    'content' => 'Od dziś do końca tygodnia wszystkie questy dają podwójną nagrodę doświadczenia i złota. Idealna okazja do szybkiego rozwoju postaci!',
                    'date' => '2025-09-01'
                ],
                [
                    'title' => 'Nowe przedmioty w sklepie',
                    'content' => 'W sklepie pojawiły się nowe, potężne artefakty. Sprawdźcie sekcję broni i zbroi - znajdziecie tam legendarne przedmioty dla najodważniejszych wojowników.',
                    'date' => '2025-08-28'
                ]
            ]
        ];

        // Get real character data if user is authenticated
        if (Auth::check()) {
            $mockData['myCharacters'] = Auth::user()->getCharacterSlots();
            $mockData['canCreateCharacter'] = !Auth::user()->hasMaxCharacters();
        }

        return view('livewire.homepage', $mockData);
    }
}
