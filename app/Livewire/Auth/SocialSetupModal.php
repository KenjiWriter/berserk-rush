<?php

namespace App\Livewire\Auth;

use Livewire\Component;

use Illuminate\Support\Facades\Auth;

class SocialSetupModal extends Component
{
    public $name = '';

    public function mount()
    {
        if (Auth::check()) {
            $this->name = Auth::user()->name;
            if (str_starts_with($this->name, 'Bohater')) {
                $this->name = ''; 
            }
        }
    }

    public function rules()
    {
        return [
            'name' => 'required|string|min:3|max:30|unique:users,name,' . Auth::id(),
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Nazwa bohatera jest wymagana.',
            'name.min' => 'Nazwa bohatera musi mieć co najmniej 3 znaki.',
            'name.max' => 'Nazwa bohatera nie może być dłuższa niż 30 znaków.',
            'name.unique' => 'Ta nazwa bohatera jest już zajęta. Wybierz inną.',
        ];
    }

    public function save()
    {
        $this->validate();

        $user = Auth::user();
        $user->update([
            'name' => $this->name,
            'is_social_setup_pending' => false,
        ]);

        return redirect()->route('homepage');
    }

    public function render()
    {
        return view('livewire.auth.social-setup-modal');
    }
}
