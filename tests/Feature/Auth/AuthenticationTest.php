<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_shows_google_login_and_signup_links(): void
    {
        $response = $this->get(route('login'));

        $response
            ->assertOk()
            ->assertSee('University email')
            ->assertSee('Password')
            ->assertSee('Remember me')
            ->assertSee('Log in')
            ->assertSee('Sign in with Google')
            ->assertSee('Sign up with Google')
            ->assertSee(route('google.redirect', ['intent' => 'login']), false)
            ->assertSee(route('register'), false);

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertNotNull($cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
    }

    public function test_session_cookie_is_secure_http_only_and_expires_after_fifteen_minutes(): void
    {
        $this->freezeTime();
        config(['session.secure' => true]);

        $response = $this->get(route('login'));
        $cookie = $response->getCookie(config('session.cookie'), false);

        $this->assertNotNull($cookie);
        $this->assertSame(true, $cookie->isSecure());
        $this->assertSame(true, $cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
        $this->assertSame(now()->addMinutes(15)->timestamp, $cookie->getExpiresTime());
    }

    public function test_unauthenticated_users_are_redirected_to_login_from_protected_pages(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('student.dashboard'))->assertRedirect(route('login'));
        $this->get(route('faculty.dashboard'))->assertRedirect(route('login'));
        $this->get(route('osa.dashboard'))->assertRedirect(route('login'));
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
    }

    public function test_authenticated_student_is_redirected_from_guest_auth_pages(): void
    {
        $student = User::factory()->create();
        $student->student()->create([
            'student_number' => '20254611',
            'first_name' => 'Dwayne',
            'last_name' => 'Hinnong',
        ]);

        $this->actingAs($student)
            ->get(route('login'))
            ->assertRedirect(route('student.dashboard'));

        $this->get(route('register'))
            ->assertRedirect(route('student.dashboard'));
    }

    public function test_student_cannot_access_faculty_or_osa_routes(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)
            ->get(route('faculty.dashboard'))
            ->assertRedirect(route('student.dashboard'));

        $this->get(route('osa.dashboard'))
            ->assertRedirect(route('student.dashboard'));
    }

    public function test_faculty_and_osa_accounts_reach_their_own_dashboards(): void
    {
        $faculty = User::factory()->faculty()->create();
        $faculty->facultyMember()->create(['full_name' => $faculty->name]);

        $this->actingAs($faculty)
            ->get(route('dashboard'))
            ->assertRedirect(route('faculty.dashboard'));

        $osaStaff = User::factory()->osaStaff()->create();
        $osaStaff->osaStaff()->create();

        $this->actingAs($osaStaff)
            ->get(route('dashboard'))
            ->assertRedirect(route('osa.dashboard'));
    }

    public function test_seeded_demo_faculty_and_osa_accounts_can_use_temporary_password_login(): void
    {
        $faculty = User::factory()->faculty()->create([
            'email' => 'faculty@e.ubaguio.edu',
            'password' => Hash::make('demo-password'),
        ]);
        $faculty->facultyMember()->create(['full_name' => $faculty->name]);

        $this->post(route('login'), [
            'email' => $faculty->email,
            'password' => 'demo-password',
        ])->assertRedirect(route('faculty.dashboard'));

        $this->assertAuthenticatedAs($faculty);
        auth()->logout();

        $osaStaff = User::factory()->osaStaff()->create([
            'password' => Hash::make('demo-password'),
        ]);
        $osaStaff->osaStaff()->create();

        $this->post(route('login'), [
            'email' => $osaStaff->email,
            'password' => 'demo-password',
        ])->assertRedirect(route('osa.dashboard'));

        $this->assertAuthenticatedAs($osaStaff);
    }

    public function test_login_regenerates_the_session_identifier(): void
    {
        $user = User::factory()->faculty()->create([
            'email' => 'faculty@e.ubaguio.edu',
            'password' => Hash::make('demo-password'),
        ]);
        $user->facultyMember()->create(['full_name' => $user->name]);
        $loginPage = $this->get(route('login'));
        $sessionCookieName = config('session.cookie');
        $previousSessionId = $loginPage->getCookie($sessionCookieName)->getValue();

        $response = $this->withCookie($sessionCookieName, $previousSessionId)
            ->post(route('login'), [
                'email' => $user->email,
                'password' => 'demo-password',
            ]);

        $response->assertRedirect(route('faculty.dashboard'));
        $this->assertNotSame(
            $previousSessionId,
            $response->getCookie($sessionCookieName)->getValue(),
        );
        $this->assertAuthenticatedAs($user);
    }

    public function test_manual_login_rejects_student_and_unconfigured_employee_accounts(): void
    {
        $student = User::factory()->create([
            'email' => '20254611@s.ubaguio.edu',
            'password' => Hash::make('demo-password'),
        ]);

        $this->post(route('login'), [
            'email' => $student->email,
            'password' => 'demo-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();

        $faculty = User::factory()->faculty()->create([
            'email' => 'other.faculty@e.ubaguio.edu',
            'password' => Hash::make('demo-password'),
        ]);

        $this->post(route('login'), [
            'email' => $faculty->email,
            'password' => 'demo-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_failed_login_audit_does_not_record_credentials_or_email(): void
    {
        $auditChannel = \Mockery::mock();
        $auditChannel->shouldReceive('info')
            ->once()
            ->with('audit.event', [
                'event' => 'auth.password_login.failed',
                'result' => 'failure',
            ]);
        Log::shouldReceive('channel')->once()->with('audit')->andReturn($auditChannel);

        $response = $this->post(route('login'), [
            'email' => 'private.user@example.test',
            'password' => 'do-not-log-this-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_rejects_an_invalid_remember_me_value(): void
    {
        $user = User::factory()->faculty()->create([
            'email' => 'faculty@e.ubaguio.edu',
            'password' => Hash::make('demo-password'),
        ]);
        $user->facultyMember()->create(['full_name' => $user->name]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'demo-password',
            'remember' => 'unexpected',
        ])->assertSessionHasErrors('remember');

        $this->assertGuest();
    }

    public function test_login_endpoint_rejects_more_than_thirty_requests_per_minute_from_one_ip(): void
    {
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->postJson(route('login'), [
                'email' => 'unknown@example.test',
                'password' => 'wrong-password',
            ])->assertRedirect();
        }

        $this->postJson(route('login'), [
            'email' => 'unknown@example.test',
            'password' => 'wrong-password',
        ])->assertTooManyRequests();

        $this->assertGuest();
    }

    public function test_users_can_logout_and_cannot_reopen_dashboard_routes(): void
    {
        $user = User::factory()->create();
        $user->student()->create([
            'student_number' => '20254611',
            'first_name' => 'Dwayne',
            'last_name' => 'Hinnong',
        ]);

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $this->get(route('student.dashboard'))->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk();
    }
}
