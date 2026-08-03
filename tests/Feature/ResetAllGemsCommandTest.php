<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

use Illuminate\Foundation\Testing\RefreshDatabase;

class ResetAllGemsCommandTest extends TestCase
{
    use RefreshDatabase;
    public function test_user_reset_gems_command_zeroes_all_user_gems(): void
    {
        $user1 = User::factory()->create(['gems' => 500]);
        $user2 = User::factory()->create(['gems' => 1200]);
        $user3 = User::factory()->create(['gems' => 0]);

        $this->artisan('user:reset-gems', ['--force' => true])
            ->assertExitCode(0);

        $this->assertEquals(0, $user1->fresh()->gems);
        $this->assertEquals(0, $user2->fresh()->gems);
        $this->assertEquals(0, $user3->fresh()->gems);
    }
}
