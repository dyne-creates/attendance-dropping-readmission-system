<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyAgreementTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_roles_are_shown_the_agreement_until_they_accept(): void
    {
        foreach (['student', 'faculty', 'osa_staff'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('profile.edit'))
                ->assertSee('Attendance and readmission policy')
                ->assertSee('name="accepted"', false)
                ->assertSee('Agree and continue');
        }
    }

    public function test_agreement_is_saved_after_the_user_accepts(): void
    {
        $user = User::factory()->create(['role' => 'faculty']);

        $response = $this->actingAs($user)->post(route('policy.accept'), [
            'accepted' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertNotNull($user->fresh()->policy_accepted_at);
    }

    public function test_agreement_cannot_be_accepted_without_checking_the_box(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->post(route('policy.accept'))
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('accepted');

        $this->assertNull($user->fresh()->policy_accepted_at);
    }
}
