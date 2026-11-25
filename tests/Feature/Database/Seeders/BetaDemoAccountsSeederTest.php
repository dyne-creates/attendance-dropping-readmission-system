<?php

namespace Tests\Feature\Database\Seeders;

use App\Models\User;
use Database\Seeders\BetaDemoAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BetaDemoAccountsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_enabled_seeder_creates_configured_staff_accounts_and_profiles_idempotently(): void
    {
        config([
            'ub.beta_password_login_enabled' => true,
            'ub.beta_password_accounts' => [
                [
                    'username' => 'faculty-beta',
                    'email' => 'faculty.beta@example.test',
                    'name' => 'Beta Faculty',
                    'password' => 'faculty-beta-test-password',
                    'role' => 'faculty',
                ],
                [
                    'username' => 'osa-beta',
                    'email' => 'osa.beta@example.test',
                    'name' => 'Beta OSA Staff',
                    'password' => 'osa-beta-test-password',
                    'role' => 'osa_staff',
                ],
            ],
        ]);

        $seeder = app(BetaDemoAccountsSeeder::class);
        $seeder->run();
        $seeder->run();

        $faculty = User::query()->where('email', 'faculty.beta@example.test')->firstOrFail();
        $osaStaff = User::query()->where('email', 'osa.beta@example.test')->firstOrFail();

        $this->assertSame('faculty', $faculty->role);
        $this->assertSame('osa_staff', $osaStaff->role);
        $this->assertSame('faculty-beta', $faculty->username);
        $this->assertSame('osa-beta', $osaStaff->username);
        $this->assertTrue(Hash::check('faculty-beta-test-password', $faculty->password));
        $this->assertTrue(Hash::check('osa-beta-test-password', $osaStaff->password));
        $this->assertDatabaseHas('faculty_members', ['user_id' => $faculty->user_id]);
        $this->assertDatabaseHas('osa_staff', ['user_id' => $osaStaff->user_id]);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_seeder_does_not_create_beta_accounts_when_feature_is_disabled(): void
    {
        config(['ub.beta_password_login_enabled' => false]);

        app(BetaDemoAccountsSeeder::class)->run();

        $this->assertDatabaseCount('users', 0);
    }
}
