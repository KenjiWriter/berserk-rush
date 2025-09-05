<?php

use App\Livewire\City\Hub;
use App\Livewire\Homepage;
use App\Livewire\City\Witch;
use App\Livewire\Auth\Register;
use App\Livewire\City\Adventure;
use App\Livewire\Characters\Show;
use App\Livewire\City\Armorsmith;
use App\Livewire\City\Weaponsmith;
use App\Livewire\Characters\Create;
use App\Livewire\Adventure\MapStub;
use Illuminate\Support\Facades\Route;
use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\Character;

Route::get('/', Homepage::class)->name('homepage');
Route::get('/register', Register::class)->name('register');

Route::middleware('auth')->group(function () {
    Route::get('/characters/create', Create::class)->name('characters.create');

    // Character selection redirect
    Route::get('/characters/{character}/play', function (Character $character) {
        if (auth()->user()->id !== $character->user_id) {
            abort(403);
        }

        session(['active_character' => $character->id]);
        return redirect()->route('city.hub', $character);
    })->name('characters.play');

    // City Hub and Buildings
    Route::prefix('play/{character}')->name('city.')->group(function () {
        Route::get('/', Hub::class)->name('hub');
        Route::get('/armorsmith', Armorsmith::class)->name('armorsmith');
        Route::get('/weaponsmith', Weaponsmith::class)->name('weaponsmith');
        Route::get('/witch', Witch::class)->name('witch');
        Route::get('/adventure', Adventure::class)->name('adventure');
    });

    // Adventure map routes
    Route::prefix('play/{character}/adventure')->name('adventure.')->group(function () {
        Route::get('/{map}', MapStub::class)->name('map');
    });
});

require __DIR__ . '/auth.php';
