<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GoogleSignupTest extends TestCase
{
    use RefreshDatabase;

    public function test_signup_page_offers_google_signup_and_login_link(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Sign up with Google')
            ->assertSee(route('google.redirect', ['intent' => 'signup']), false)
            ->assertSee(route('login'), false);
    }

    public function test_google_redirect_remembers_signup_intent(): void
    {
        Socialite::fake('google');

        $this->get(route('google.redirect', ['intent' => 'signup']))
            ->assertRedirect('https://socialite.fake/google/authorize')
            ->assertSessionHas('google_auth_intent', 'signup');
    }

    public function test_student_signup_creates_user_and_student_profile(): void
    {
        $email = '20254611@s.ubaguio.edu';
        $this->fakeGoogleAccount($email);

        $response = $this->withSession(['google_auth_intent' => 'signup'])
            ->get(route('google.callback'));

        $user = User::query()->where('email', $email)->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('student', $user->role);
        $this->assertNull($user->password);
        $this->assertSame('google-user-123', $user->google_id);
        $this->assertSame('https://example.test/avatar.png', $user->avatar_url);
        $response->assertRedirect(route('student.dashboard'));
        $this->assertDatabaseHas('students', [
            'user_id' => $user->user_id,
            'student_number' => '20254611',
            'first_name' => 'Dwayne',
            'last_name' => 'Hinnong',
        ]);
    }

    public function test_student_signup_claims_unlinked_class_list_record(): void
    {
        $student = Student::query()->create([
            'user_id' => null,
            'student_number' => '20254611',
            'first_name' => '',
            'last_name' => '',
        ]);
        $this->fakeGoogleAccount('20254611@s.ubaguio.edu');

        $response = $this->withSession(['google_auth_intent' => 'signup'])
            ->get(route('google.callback'));

        $user = User::query()->where('email', '20254611@s.ubaguio.edu')->firstOrFail();
        $response->assertRedirect(route('student.dashboard'));
        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseHas('students', [
            'student_id' => $student->student_id,
            'user_id' => $user->user_id,
            'first_name' => 'Dwayne',
            'last_name' => 'Hinnong',
        ]);
    }

    public static function employeeAccounts(): array
    {
        return [
            'faculty' => ['divine@e.ubaguio.edu', 'faculty', 'faculty.dashboard', 'faculty_members'],
            'OSA account' => ['osa@e.ubaguio.edu', 'osa_staff', 'osa.dashboard', 'osa_staff'],
            'other employee' => ['other.staff@e.ubaguio.edu', 'faculty', 'faculty.dashboard', 'faculty_members'],
        ];
    }

    #[DataProvider('employeeAccounts')]
    public function test_employee_signup_creates_email_assigned_profile_and_dashboard(
        string $email,
        string $role,
        string $dashboardRoute,
        string $profileTable
    ): void {
        $this->fakeGoogleAccount($email);

        $response = $this->withSession(['google_auth_intent' => 'signup'])
            ->get(route('google.callback'));

        $user = User::query()->where('email', $email)->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame($role, $user->role);
        $response->assertRedirect(route($dashboardRoute));
        $this->assertDatabaseHas($profileTable, ['user_id' => $user->user_id]);
    }

    public static function rejectedSignupAccounts(): array
    {
        return [
            'external domain' => ['person@gmail.com', 'Only University of Baguio accounts are allowed.'],
            'lookalike domain' => ['20254611@s.ubaguio.edu.evil.com', 'Only University of Baguio accounts are allowed.'],
            'invalid student local part' => ['osa@s.ubaguio.edu', 'Student accounts must use their valid 8-digit student ID before @s.ubaguio.edu.'],
        ];
    }

    #[DataProvider('rejectedSignupAccounts')]
    public function test_signup_rejects_unapproved_email_without_creating_an_account(string $email, string $message): void
    {
        $this->fakeGoogleAccount($email);

        $this->withSession(['google_auth_intent' => 'signup'])
            ->get(route('google.callback'))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors(['google' => $message]);

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    public function test_signup_rejects_unverified_google_email_without_creating_an_account(): void
    {
        $this->fakeGoogleAccount('20254611@s.ubaguio.edu', false);

        $this->withSession(['google_auth_intent' => 'signup'])
            ->get(route('google.callback'))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors(['google' => 'Your Google email must be verified before you can continue.']);

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_signup_redirects_existing_email_to_login_without_authenticating(): void
    {
        User::factory()->create(['email' => '20254611@s.ubaguio.edu']);
        $this->fakeGoogleAccount('20254611@s.ubaguio.edu');

        $this->withSession(['google_auth_intent' => 'signup'])
            ->get(route('google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'An account already exists for this email. Please log in instead.');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_signup_refuses_to_claim_student_record_linked_to_another_user(): void
    {
        $owner = User::factory()->create(['email' => 'owner@s.ubaguio.edu']);
        Student::query()->create([
            'user_id' => $owner->user_id,
            'student_number' => '20254611',
            'first_name' => 'Existing',
            'last_name' => 'Student',
        ]);
        $this->fakeGoogleAccount('20254611@s.ubaguio.edu', true, 'different-google-id');

        $this->withSession(['google_auth_intent' => 'signup'])
            ->get(route('google.callback'))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('google');

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($owner->user_id, Student::query()->where('student_number', '20254611')->value('user_id'));
        $this->assertGuest();
    }

    public function test_google_login_is_lookup_only_and_reports_account_not_found(): void
    {
        $this->fakeGoogleAccount('20254611@s.ubaguio.edu');

        $this->withSession(['google_auth_intent' => 'login'])
            ->get(route('google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['google' => 'No account was found for 20254611@s.ubaguio.edu. Please sign up first.']);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_google_login_links_a_matching_legacy_account_without_overwriting_name(): void
    {
        $user = User::factory()->create([
            'name' => 'Admin Corrected Name',
            'email' => '20254611@s.ubaguio.edu',
        ]);
        $user->student()->create([
            'student_number' => '20254611',
            'first_name' => 'Dwayne',
            'last_name' => 'Hinnong',
        ]);
        $this->fakeGoogleAccount('20254611@s.ubaguio.edu');

        $response = $this->withSession(['google_auth_intent' => 'login'])
            ->get(route('google.callback'));

        $response->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('Admin Corrected Name', $user->fresh()->name);
        $this->assertSame('google-user-123', $user->fresh()->google_id);
    }

    public function test_google_login_rejects_account_linked_to_a_different_google_id(): void
    {
        $user = User::factory()->create([
            'email' => '20254611@s.ubaguio.edu',
            'google_id' => 'existing-google-id',
        ]);
        $user->student()->create([
            'student_number' => '20254611',
            'first_name' => 'Dwayne',
            'last_name' => 'Hinnong',
        ]);
        $this->fakeGoogleAccount('20254611@s.ubaguio.edu', true, 'different-google-id');

        $this->withSession(['google_auth_intent' => 'login'])
            ->get(route('google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertSame('existing-google-id', $user->fresh()->google_id);
    }

    public function test_signup_rejects_canceled_google_consent_and_provider_failure(): void
    {
        $this->withSession(['google_auth_intent' => 'signup'])
            ->get(route('google.callback', ['error' => 'access_denied']))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors(['google' => 'Google sign-in was canceled. You can try again.']);

        Socialite::fake('google', static function (): never {
            throw new \RuntimeException('Provider failure');
        });

        $this->withSession(['google_auth_intent' => 'signup'])
            ->get(route('google.callback'))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors(['google' => 'Google sign-in could not be completed. Please try again.']);

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_password_based_signup_and_account_management_routes_are_not_available(): void
    {
        $this->post('/register')->assertMethodNotAllowed();
        $this->get('/forgot-password')->assertNotFound();
        $this->get('/confirm-password')->assertNotFound();
        $this->get('/verify-email')->assertNotFound();
        $this->put('/password')->assertNotFound();
        $this->patch('/profile')->assertMethodNotAllowed();
        $this->delete('/profile')->assertMethodNotAllowed();
    }

    private function fakeGoogleAccount(
        string $email,
        bool $verified = true,
        string $googleId = 'google-user-123',
        array $overrides = []
    ): void {
        $raw = array_merge([
            'sub' => $googleId,
            'email' => $email,
            'email_verified' => $verified,
            'given_name' => 'Dwayne',
            'family_name' => 'Hinnong',
            'name' => 'Dwayne Hinnong',
            'picture' => 'https://example.test/avatar.png',
        ], $overrides);

        $googleUser = (new GoogleUser)
            ->setRaw($raw)
            ->map([
                'id' => $googleId,
                'email' => $email,
                'name' => $raw['name'],
                'avatar' => $raw['picture'],
            ]);

        Socialite::fake('google', $googleUser);
    }
}
