<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_registration_submission_is_not_available(): void
    {
        $this->post(route('register'), [
            'name' => 'Test User',
            'role' => 'osa_staff',
            'email' => 'osa@e.ubaguio.edu',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertMethodNotAllowed();

        $this->assertDatabaseMissing('users', ['email' => 'osa@e.ubaguio.edu']);
        $this->assertGuest();
    }
}
