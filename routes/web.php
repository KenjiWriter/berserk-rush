<?php

use App\Livewire\Homepage;
use App\Livewire\Auth\Register;
use App\Livewire\Characters\Create;
use Illuminate\Support\Facades\Route;

Route::get('/', Homepage::class)->name('homepage');
Route::get('/register', Register::class)->name('register');

Route::middleware('auth')->group(function () {
    Route::get('/characters/create', Create::class)->name('characters.create');
    // Route::get('/characters/{character}', Show::class)->name('characters.show')->can('view', 'character');
});


require __DIR__ . '/auth.php';
