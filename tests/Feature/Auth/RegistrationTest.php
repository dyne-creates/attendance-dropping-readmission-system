<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public static function registrationRoles(): array
    {
        return [
            'student' => ['student', 'student@s.ubaguio.edu', 'student.dashboard'],
            'faculty' => ['faculty', 'faculty@e.ubaguio.edu', 'faculty.dashboard'],
            'osa staff' => ['osa_staff', 'osa@e.ubaguio.edu', 'osa.dashboard'],
        ];
    }

    #[DataProvider('registrationRoles')]
    public function test_users_can_register_with_their_role_email_domain(string $role, string $email, string $dashboardRoute): void
    {
        $registrationData = [
            'name' => 'Test User',
            'role' => $role,
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        if ($role === 'student') {
            $registrationData['student_number'] = '20254754';
        }

        $response = $this->post('/register', $registrationData);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('users', ['email' => $email, 'role' => $role]);

        $user = User::where('email', $email)->firstOrFail();

        if ($role === 'student') {
            $this->assertDatabaseHas('students', [
                'user_id' => $user->getKey(),
                'student_number' => '20254754',
                'first_name' => 'Test',
                'last_name' => 'User',
            ]);
        } elseif ($role === 'faculty') {
            $this->assertDatabaseHas('faculty_members', [
                'user_id' => $user->getKey(),
                'full_name' => 'Test User',
            ]);
        } else {
            $this->assertDatabaseHas('osa_staff', [
                'user_id' => $user->getKey(),
            ]);
        }

        $this->get(route('dashboard'))
            ->assertRedirect(route($dashboardRoute));
    }

    public static function invalidRegistrationRoleDomains(): array
    {
        return [
            'student using an employee email' => [
                'student',
                'student@e.ubaguio.edu',
                'Students must register with an @s.ubaguio.edu email address.',
            ],
            'faculty using a student email' => [
                'faculty',
                'faculty@s.ubaguio.edu',
                'Faculty and OSA staff must register with an @e.ubaguio.edu email address.',
            ],
            'osa staff using a student email' => [
                'osa_staff',
                'osa-staff@s.ubaguio.edu',
                'Faculty and OSA staff must register with an @e.ubaguio.edu email address.',
            ],
        ];
    }

    #[DataProvider('invalidRegistrationRoleDomains')]
    public function test_registration_rejects_email_domains_that_do_not_match_the_role(string $role, string $email, string $errorMessage): void
    {
        $response = $this->post('/register', [
            'name' => 'Test Student',
            'role' => $role,
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['email' => $errorMessage]);
        $this->assertDatabaseMissing('users', ['email' => $email]);
        $this->assertGuest();
    }

    public function test_student_registration_requires_a_student_number(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test Student',
            'role' => 'student',
            'email' => 'student@s.ubaguio.edu',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('student_number');
        $this->assertDatabaseMissing('users', ['email' => 'student@s.ubaguio.edu']);
        $this->assertGuest();
    }
}
