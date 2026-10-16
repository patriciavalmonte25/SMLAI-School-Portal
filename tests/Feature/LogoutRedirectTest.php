<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class LogoutRedirectTest extends TestCase
{
    use WithoutMiddleware;

    public function test_user_is_redirected_to_public_home_after_logout()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/');
    }
}
