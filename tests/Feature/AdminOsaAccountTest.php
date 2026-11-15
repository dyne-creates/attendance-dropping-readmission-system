<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminOsaAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_an_osa_account_with_a_staff_profile(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.osa-accounts.store'), [
            'name' => 'OSA Staff Two',
            'email' => 'osa.two@e.ubaguio.edu',
            'password' => 'temporary-password',
            'password_confirmation' => 'temporary-password',
        ]);

        $response->assertRedirect(route('admin.osa-accounts.index'));
        $this->assertDatabaseHas('users', [
            'name' => 'OSA Staff Two',
            'email' => 'osa.two@e.ubaguio.edu',
            'role' => 'osa_staff',
        ]);

        $account = User::query()->where('email', 'osa.two@e.ubaguio.edu')->firstOrFail();
        $this->assertTrue(Hash::check('temporary-password', $account->password));
        $this->assertDatabaseHas('osa_staff', ['user_id' => $account->user_id]);
    }

    public function test_osa_staff_cannot_access_administration(): void
    {
        $osaStaff = User::factory()->osaStaff()->create();
        $osaStaff->osaStaff()->create();

        $this->actingAs($osaStaff)
            ->get(route('admin.osa-accounts.index'))
            ->assertRedirect(route('osa.dashboard'));
    }
}
