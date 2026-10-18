<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_offers_google_sign_in_without_registration(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sign in with Google')
            ->assertDontSee('Register');
    }

    public function test_guest_can_be_redirected_to_google(): void
    {
        Socialite::fake('google');

        $this->get(route('google.redirect'))
            ->assertRedirect('https://socialite.fake/google/authorize');
    }

    public static function universityAccounts(): array
    {
        return [
            'student' => ['student', 'student@s.ubaguio.edu', 'student.dashboard'],
            'faculty' => ['faculty', 'faculty@e.ubaguio.edu', 'faculty.dashboard'],
            'OSA staff' => ['osa_staff', 'osa-staff@e.ubaguio.edu', 'osa.dashboard'],
        ];
    }

    #[DataProvider('universityAccounts')]
    public function test_verified_university_google_accounts_sign_in_to_their_own_dashboard(
        string $role,
        string $email,
        string $dashboardRoute
    ): void {
        $user = $this->createAccountWithRoleProfile($role, $email);
        Socialite::fake('google', $this->googleAccount($email));

        $response = $this->get(route('google.callback'));

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route($dashboardRoute));
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_google_callback_rejects_an_unverified_email(): void
    {
        $email = 'student@s.ubaguio.edu';
        $user = $this->createAccountWithRoleProfile('student', $email)->forceFill([
            'email_verified_at' => null,
        ]);
        $user->save();
        Socialite::fake('google', $this->googleAccount($email, false));

        $this->get(route('google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'google' => 'This Google account is not eligible to sign in. Use an existing UB student or employee account.',
            ]);

        $this->assertGuest();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_google_callback_does_not_create_an_account_for_an_unregistered_university_email(): void
    {
        $email = 'new-student@s.ubaguio.edu';
        Socialite::fake('google', $this->googleAccount($email));

        $this->get(route('google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    public function test_google_callback_rejects_a_student_account_using_an_employee_email(): void
    {
        $email = 'student@e.ubaguio.edu';
        $user = $this->createAccountWithRoleProfile('student', $email);
        Socialite::fake('google', $this->googleAccount($email));

        $this->get(route('google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertModelExists($user);
    }

    public function test_google_callback_rejects_an_account_using_a_non_university_email(): void
    {
        $email = 'student@gmail.com';
        $this->createAccountWithRoleProfile('student', $email);
        Socialite::fake('google', $this->googleAccount($email));

        $this->get(route('google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
    }

    public function test_google_callback_rejects_accounts_without_their_role_profile(): void
    {
        $email = 'student@s.ubaguio.edu';
        User::factory()->create(['email' => $email]);
        Socialite::fake('google', $this->googleAccount($email));

        $this->get(route('google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
    }

    public function test_google_callback_handles_a_user_canceled_sign_in(): void
    {
        $this->get(route('google.callback', ['error' => 'access_denied']))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'google' => 'Google sign-in was canceled. You can try again.',
            ]);

        $this->assertGuest();
    }

    public function test_authenticated_users_are_redirected_from_google_auth_routes(): void
    {
        $user = $this->createAccountWithRoleProfile('student', 'student@s.ubaguio.edu');

        $this->actingAs($user)
            ->get(route('google.redirect'))
            ->assertRedirect(route('student.dashboard'));

        $this->get(route('google.callback'))
            ->assertRedirect(route('student.dashboard'));
    }

    private function googleAccount(string $email, bool $isVerified = true): GoogleUser
    {
        return GoogleUser::fake([
            'id' => 'google-user-123',
            'email' => $email,
            'email_verified' => $isVerified,
        ]);
    }

    private function createAccountWithRoleProfile(string $role, string $email): User
    {
        $user = match ($role) {
            'faculty' => User::factory()->faculty()->create(['email' => $email]),
            'osa_staff' => User::factory()->osaStaff()->create(['email' => $email]),
            default => User::factory()->create(['email' => $email]),
        };

        match ($role) {
            'student' => $user->student()->create([
                'student_number' => '2025-0001',
                'first_name' => 'Test',
                'last_name' => 'Student',
            ]),
            'faculty' => $user->facultyMember()->create([
                'full_name' => $user->name,
            ]),
            'osa_staff' => $user->osaStaff()->create(),
        };

        return $user;
    }
}
