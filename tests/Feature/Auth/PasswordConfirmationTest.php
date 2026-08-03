<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('user password confirmation works correctly', function () {
    $user = User::factory()->create(['password' => Hash::make('password')]);

    $this->assertTrue(Hash::check('password', $user->password));
    $this->assertFalse(Hash::check('wrong-password', $user->password));
});
