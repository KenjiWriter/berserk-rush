<?php

namespace App\Livewire\Auth;

use Livewire\Component;

use Illuminate\Support\Facades\Auth;

class SocialSetupModal extends Component
{
    public $name = '';
    public $deletion_code = '';

    public function mount()
    {
        if (Auth::check()) {
            $this->name = Auth::user()->name;
            if (str_starts_with($this->name, 'Bohater')) {
                $this->name = ''; 
            }
            $this->deletion_code = Auth::user()->deletion_code ?? '';
        }
    }

    public function rules()
    {
        return [
            'name' => 'required|string|min:3|max:30|unique:users,name,' . Auth::id(),
            'deletion_code' => 'required|string|min:7|max:50',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Nazwa bohatera jest wymagana.',
            'name.min' => 'Nazwa bohatera musi mieć co najmniej 3 znaki.',
            'name.max' => 'Nazwa bohatera nie może być dłuższa niż 30 znaków.',
            'name.unique' => 'Ta nazwa bohatera jest już zajęta. Wybierz inną.',
            'deletion_code.required' => 'Kod usunięcia postaci jest wymagany.',
            'deletion_code.min' => 'Kod usunięcia postaci musi mieć co najmniej 7 znaków.',
            'deletion_code.max' => 'Kod usunięcia postaci może mieć maksymalnie 50 znaków.',
        ];
    }

    public function save()
    {
        $this->validate();

        $user = Auth::user();
        $user->update([
            'name' => $this->name,
            'deletion_code' => $this->deletion_code,
            'is_social_setup_pending' => false,
        ]);

        return redirect()->route('homepage');
    }

    public function render()
    {
        return view('livewire.auth.social-setup-modal');
    }
}
