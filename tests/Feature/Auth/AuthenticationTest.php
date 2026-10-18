<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_unauthenticated_users_are_redirected_to_login_from_dashboards(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('student.dashboard'))->assertRedirect(route('login'));
        $this->get(route('faculty.dashboard'))->assertRedirect(route('login'));
        $this->get(route('osa.dashboard'))->assertRedirect(route('login'));
    }

    public static function dashboardRoles(): array
    {
        return [
            'student' => ['student', 'student.dashboard', 'student.dashboard'],
            'faculty' => ['faculty', 'faculty.dashboard', 'faculty.dashboard'],
            'osa staff' => ['osa_staff', 'osa.dashboard', 'osa-staff.dashboard'],
        ];
    }

    #[DataProvider('dashboardRoles')]
    public function test_login_redirects_each_role_to_its_own_dashboard(string $role, string $dashboardRoute, string $view): void
    {
        $user = match ($role) {
            'faculty' => User::factory()->faculty()->create(),
            'osa_staff' => User::factory()->osaStaff()->create(),
            default => User::factory()->create(),
        };

        $loginResponse = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $loginResponse->assertRedirect(route('dashboard', absolute: false));

        $this->get(route('dashboard'))
            ->assertRedirect(route($dashboardRoute));

        $this->get(route($dashboardRoute))
            ->assertOk()
            ->assertViewIs($view);

        $this->get(route('login'))
            ->assertRedirect(route($dashboardRoute));

        $this->get('/')
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_student_dashboard_does_not_show_the_osa_navigation(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('student.dashboard'));

        $response->assertViewIs('student.dashboard');
        $response->assertSee('My attendance');
        $response->assertSee('Profile');
        $response->assertDontSee('Readmissions');
    }

    public function test_faculty_dashboard_shows_faculty_navigation_and_heading(): void
    {
        $response = $this->actingAs(User::factory()->faculty()->create())
            ->get(route('faculty.dashboard'));

        $response->assertViewIs('faculty.dashboard');
        $response->assertSee('Profile');
        $response->assertSee('Attendance');
        $response->assertDontSee('Readmissions');
    }

    public function test_osa_dashboard_shows_osa_navigation(): void
    {
        $response = $this->actingAs(User::factory()->osaStaff()->create())
            ->get(route('osa.dashboard'));

        $response->assertViewIs('osa-staff.dashboard');
        $response->assertSee('Readmissions');
        $response->assertSee('Profile');
    }

    public function test_student_cannot_access_osa_routes(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('osa.dashboard'))
            ->assertRedirect(route('student.dashboard'));
    }

    public function test_faculty_cannot_access_student_or_osa_dashboards(): void
    {
        $this->actingAs(User::factory()->faculty()->create());

        $this->get(route('student.dashboard'))->assertRedirect(route('faculty.dashboard'));
        $this->get(route('osa.dashboard'))->assertRedirect(route('faculty.dashboard'));
    }

    public function test_osa_staff_cannot_access_student_or_faculty_dashboards(): void
    {
        $this->actingAs(User::factory()->osaStaff()->create());

        $this->get(route('student.dashboard'))->assertRedirect(route('osa.dashboard'));
        $this->get(route('faculty.dashboard'))->assertRedirect(route('osa.dashboard'));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email' => trans('auth.failed')]);
    }

    public function test_login_rejects_credentials_with_a_domain_that_does_not_match_the_account_role(): void
    {
        $user = User::factory()->create([
            'email' => 'student@e.ubaguio.edu',
            'role' => 'student',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email' => trans('auth.failed')]);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));

        $this->get(route('student.dashboard'))
            ->assertRedirect(route('login'));

        $this->get(route('login'))
            ->assertOk();
    }

    public function test_authenticated_dashboard_responses_are_not_cached(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('student.dashboard'));

        $response->assertOk();
        $response->assertHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
