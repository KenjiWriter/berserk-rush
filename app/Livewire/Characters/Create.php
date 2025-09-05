<?php

namespace App\Livewire\Characters;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Application\Characters\CreateCharacter;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;

class Create extends Component
{
    public string $name = '';
    public int $str = 0;
    public int $int = 0;
    public int $vit = 0;
    public int $agi = 0;
    public ?string $avatar = null;

    public array $availableAvatars = [];

    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:16',
                'regex:/^[A-Za-z0-9_]+$/',
                Rule::unique('characters', 'name'),
            ],
            'str' => 'required|integer|min:0|max:10',
            'int' => 'required|integer|min:0|max:10',
            'vit' => 'required|integer|min:0|max:10',
            'agi' => 'required|integer|min:0|max:10',
            'avatar' => ['nullable', 'string', Rule::in(array_keys($this->availableAvatars))],
        ];
    }

    protected $messages = [
        'name.required' => 'Podaj nazwę postaci',
        'name.min' => 'Nazwa musi mieć co najmniej 3 znaki',
        'name.max' => 'Nazwa może mieć maksymalnie 16 znaków',
        'name.regex' => 'Nazwa może zawierać tylko litery, cyfry i podkreślenia',
        'name.unique' => 'Ta nazwa postaci jest już zajęta',
        'str.min' => 'Siła nie może być ujemna',
        'str.max' => 'Siła nie może przekroczyć 10',
        'int.min' => 'Inteligencja nie może być ujemna',
        'int.max' => 'Inteligencja nie może przekroczyć 10',
        'vit.min' => 'Witalność nie może być ujemna',
        'vit.max' => 'Witalność nie może przekroczyć 10',
        'agi.min' => 'Zręczność nie może być ujemna',
        'agi.max' => 'Zręczność nie może przekroczyć 10',
    ];

    public function mount(): void
    {
        if (Auth::user()->hasMaxCharacters()) {
            session()->flash('error', 'Osiągnięto limit 4 postaci na konto.');
            $this->redirect(route('homepage'), navigate: true);
        }

        $this->loadAvailableAvatars();

        // Set first avatar as default
        if (!empty($this->availableAvatars)) {
            $this->avatar = array_key_first($this->availableAvatars);
        }
    }

    private function loadAvailableAvatars(): void
    {
        $avatarPath = public_path('img/avatars');

        if (!File::exists($avatarPath)) {
            $this->availableAvatars = [];
            return;
        }

        $files = File::files($avatarPath);
        $this->availableAvatars = [];

        foreach ($files as $file) {
            if (in_array($file->getExtension(), ['png', 'jpg', 'jpeg', 'webp'])) {
                $filename = $file->getFilenameWithoutExtension();
                $this->availableAvatars[$filename] = asset('img/avatars/' . $file->getFilename());
            }
        }
    }

    public function selectAvatar(string $avatarKey): void
    {
        if (array_key_exists($avatarKey, $this->availableAvatars)) {
            $this->avatar = $avatarKey;
        }
    }

    public function updated($propertyName): void
    {
        if (in_array($propertyName, ['str', 'int', 'vit', 'agi'])) {
            $this->validateStatAllocation();
        }

        $this->validateOnly($propertyName);
    }

    public function incrementStat(string $stat): void
    {
        if ($this->getRemainingPoints() > 0 && $this->$stat < 10) {
            $this->$stat++;
            $this->validateStatAllocation();
        }
    }

    public function decrementStat(string $stat): void
    {
        if ($this->$stat > 0) {
            $this->$stat--;
            $this->validateStatAllocation();
        }
    }

    public function getRemainingPoints(): int
    {
        return 10 - ($this->str + $this->int + $this->vit + $this->agi);
    }

    private function validateStatAllocation(): void
    {
        $total = $this->str + $this->int + $this->vit + $this->agi;

        if ($total > 10) {
            $this->addError('stats', 'Nie możesz przeznaczyć więcej niż 10 punktów.');
        } else {
            $this->resetErrorBag('stats');
        }
    }

    public function createCharacter(): void
    {
        $this->validate();

        // Additional validation for total points
        if ($this->getRemainingPoints() !== 0) {
            $this->addError('stats', 'Musisz przeznaczyć dokładnie 10 punktów atrybutów.');
            return;
        }

        $createCharacterService = app(CreateCharacter::class);

        $result = $createCharacterService->handle(
            user: Auth::user(),
            name: $this->name,
            str: $this->str,
            int: $this->int,
            vit: $this->vit,
            agi: $this->agi,
            avatar: $this->avatar
        );

        if ($result->isError()) {
            $this->addError('form', $result->getErrorMessage());
            return;
        }

        session()->flash('success', 'Postać została utworzona pomyślnie!');
        $this->redirect(route('homepage'), navigate: true);
    }

    public function render()
    {
        return view('livewire.characters.create');
    }
}
