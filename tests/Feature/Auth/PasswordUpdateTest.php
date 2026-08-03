<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('user password can be updated and verified', function () {
    $user = User::factory()->create(['password' => Hash::make('old-password')]);

    $this->assertTrue(Hash::check('old-password', $user->password));

    $user->update(['password' => Hash::make('new-password')]);

    $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
});
