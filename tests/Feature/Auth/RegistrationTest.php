<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_offers_username_password_and_google_signup(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('name="username"', false)
            ->assertSee('name="password"', false)
            ->assertSee('Sign up with Google')
            ->assertSee(route('google.redirect', ['intent' => 'signup']), false);
    }

    public function test_student_can_register_and_log_in_without_email_verification(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Test Student',
            'username' => 'Test.Student',
            'email' => '20254611@s.ubaguio.edu',
            'password' => 'strong-test-password',
            'password_confirmation' => 'strong-test-password',
        ]);

        $user = User::query()->where('email', '20254611@s.ubaguio.edu')->firstOrFail();

        $response->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('test.student', $user->username);
        $this->assertSame('student', $user->role);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(Hash::check('strong-test-password', $user->password));
        $this->assertDatabaseHas('students', [
            'user_id' => $user->user_id,
            'student_number' => '20254611',
            'first_name' => 'Test',
            'last_name' => 'Student',
        ]);
        $this->post(route('logout'));

        $this->post(route('login'), [
            'username' => 'test.student',
            'password' => 'strong-test-password',
        ])->assertRedirect(route('student.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_employee_signup_creates_the_role_profile_assigned_to_the_university_email(): void
    {
        $this->post(route('register'), [
            'name' => 'Faculty Member',
            'username' => 'faculty-member',
            'email' => 'faculty.member@e.ubaguio.edu',
            'password' => 'strong-test-password',
            'password_confirmation' => 'strong-test-password',
        ])->assertRedirect(route('faculty.dashboard'));

        $faculty = User::query()->where('email', 'faculty.member@e.ubaguio.edu')->firstOrFail();
        $this->assertSame('faculty', $faculty->role);
        $this->assertDatabaseHas('faculty_members', [
            'user_id' => $faculty->user_id,
            'full_name' => 'Faculty Member',
        ]);

        $this->post(route('logout'));

        $this->post(route('register'), [
            'name' => 'OSA Member',
            'username' => 'osa-member',
            'email' => 'osa@e.ubaguio.edu',
            'password' => 'strong-test-password',
            'password_confirmation' => 'strong-test-password',
        ])->assertRedirect(route('osa.dashboard'));

        $osaStaff = User::query()->where('email', 'osa@e.ubaguio.edu')->firstOrFail();
        $this->assertSame('osa_staff', $osaStaff->role);
        $this->assertDatabaseHas('osa_staff', ['user_id' => $osaStaff->user_id]);
    }

    public function test_registration_rejects_non_university_email_addresses(): void
    {
        $this->from(route('register'))->post(route('register'), [
            'name' => 'Outside User',
            'username' => 'outside-user',
            'email' => 'outside@example.com',
            'password' => 'strong-test-password',
            'password_confirmation' => 'strong-test-password',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_student_cannot_register_again_when_the_roster_record_is_already_linked(): void
    {
        $existingUser = User::factory()->create(['email' => 'existing@e.ubaguio.edu']);
        $existingUser->student()->create([
            'student_number' => '20254611',
            'first_name' => 'Existing',
            'last_name' => 'Student',
        ]);

        $this->from(route('register'))->post(route('register'), [
            'name' => 'Test Student',
            'username' => 'test-student',
            'email' => '20254611@s.ubaguio.edu',
            'password' => 'strong-test-password',
            'password_confirmation' => 'strong-test-password',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }
}
