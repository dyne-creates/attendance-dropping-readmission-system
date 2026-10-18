<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_is_not_available(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_registration_submission_is_not_available(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'role' => 'student',
            'email' => 'test@s.ubaguio.edu',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'test@s.ubaguio.edu']);
        $this->assertGuest();
    }
}
