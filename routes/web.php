<?php

use App\Livewire\Homepage;
use App\Livewire\Auth\Register;
use App\Livewire\Characters\Create;
use App\Livewire\Characters\Show;
use App\Livewire\City\Hub;
use App\Livewire\City\Armorsmith;
use App\Livewire\City\Weaponsmith;
use App\Livewire\City\Witch;
use App\Livewire\City\Adventure;
use App\Infrastructure\Persistence\Character;
use Illuminate\Support\Facades\Route;

Route::get('/', Homepage::class)->name('homepage');
Route::get('/register', Register::class)->name('register');

Route::middleware('auth')->group(function () {
    Route::get('/characters/create', Create::class)->name('characters.create');
    // Route::get('/characters/{character}', Show::class)->name('characters.show')->can('view', 'character');

    // Character selection redirect - z prawidłowym model binding
    Route::get('/characters/{character}/play', function (Character $character) {
        // Sprawdź autoryzację manualnie
        if (auth()->user()->id !== $character->user_id) {
            abort(403);
        }

        session(['active_character' => $character->id]);
        return redirect()->route('city.hub', $character);
    })->name('characters.play');

    // City Hub and Buildings - z prawidłowym model binding  
    Route::prefix('play/{character}')->name('city.')->group(function () {
        Route::get('/', Hub::class)->name('hub');
        Route::get('/armorsmith', Armorsmith::class)->name('armorsmith');
        Route::get('/weaponsmith', Weaponsmith::class)->name('weaponsmith');
        Route::get('/witch', Witch::class)->name('witch');
        Route::get('/adventure', Adventure::class)->name('adventure');
    });
});

require __DIR__ . '/auth.php';
