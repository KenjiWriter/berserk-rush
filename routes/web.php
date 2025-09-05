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
use Illuminate\Support\Facades\Route;
use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\Character;

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

    // Adventure map routes
    Route::prefix('play/{character}/adventure')->name('adventure.')->group(function () {
        Route::get('/{map}', function (Character $character, Map $map) {
            // Sprawdź autoryzację
            if (auth()->user()->id !== $character->user_id) {
                abort(403);
            }

            // Check if map is accessible
            if (!$map->isAccessibleBy($character)) {
                abort(403, 'Twój poziom nie pozwala na wejście na tę mapę.');
            }

            // Stub for now - future EncounterList component
            return view('adventure.map-stub', compact('character', 'map'));
        })->name('map');
    });
});

require __DIR__ . '/auth.php';
