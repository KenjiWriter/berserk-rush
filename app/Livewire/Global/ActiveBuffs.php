<?php

namespace App\Livewire\Global;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Livewire\Attributes\On;

class ActiveBuffs extends Component
{
    #[On('buff-applied')]
    public function refreshBuffs()
    {
        // Re-renders the component
    }

    public function render()
    {
        $buffs = [];
        if (Auth::check() && Auth::user()->character) {
            $buffs = Auth::user()->character->activeBuffs()
                ->where('expires_at', '>', Carbon::now())
                ->get()
                ->map(function($buff) {
                    return [
                        'id' => $buff->id,
                        'name' => $buff->name,
                        'expires_at_timestamp' => $buff->expires_at->timestamp * 1000,
                        'effects' => $buff->effects
                    ];
                });
        }

        return view('livewire.global.active-buffs', [
            'buffs' => $buffs
        ]);
    }
}
