<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Password;

test('password reset token can be generated for user', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $this->assertNotEmpty($token);
    $this->assertTrue(Password::tokenExists($user, $token));
});
