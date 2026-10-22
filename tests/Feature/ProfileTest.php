<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_read_only_account_details(): void
    {
        $user = User::factory()->create([
            'name' => 'Dwayne Hinnong',
            'email' => '20254632@s.ubaguio.edu',
            'role' => 'student',
            'avatar_url' => 'https://example.test/avatar.png',
        ]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Dwayne Hinnong')
            ->assertSee('20254632@s.ubaguio.edu')
            ->assertSee('Student')
            ->assertSee('https://example.test/avatar.png')
            ->assertDontSee('Update Password')
            ->assertDontSee('Delete Account')
            ->assertDontSee('profile.update')
            ->assertDontSee('profile.destroy');
    }

    public function test_profile_is_protected_for_guests(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
    }
}
