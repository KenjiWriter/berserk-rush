<?php

use App\Models\User;
use Livewire\Volt\Volt;

test('login screen redirects to homepage', function () {
    $response = $this->get('/login');

    $response
        ->assertRedirect(route('homepage'));
});

test('users can authenticate using the login modal', function () {
    $user = User::factory()->create();

    $component = \Livewire\Livewire::test(\App\Livewire\Auth\LoginModal::class)
        ->set('email', $user->email)
        ->set('password', 'password');

    $component->call('login');

    $component
        ->assertHasNoErrors()
        ->assertRedirect(route('homepage', absolute: false));

    $this->assertAuthenticated();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $component = \Livewire\Livewire::test(\App\Livewire\Auth\LoginModal::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password');

    $component->call('login');

    $component
        ->assertHasErrors()
        ->assertNoRedirect();

    $this->assertGuest();
});

test('authenticated users can access homepage', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get('/');

    $response
        ->assertOk();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = \Livewire\Livewire::test(\App\Livewire\Auth\LogoutModal::class);

    $component->call('logout');

    $component
        ->assertHasNoErrors()
        ->assertRedirect(route('homepage'));

    $this->assertGuest();
});
