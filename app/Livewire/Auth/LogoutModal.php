<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class LogoutModal extends Component
{
    public bool $showModal = false;

    public function openModal()
    {
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->closeModal();
        return redirect()->route('homepage');
    }

    public function render()
    {
        return view('livewire.auth.logout-modal');
    }
}
